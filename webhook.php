<?php
/**
 * Cashfree Webhook (webhook.php)
 * - Verifies HMAC signature (x-webhook-signature) using CF_WEBHOOK_SECRET from constant.inc.php
 * - Idempotent: safely updates once
 * - Maps Cashfree order -> local order via `order_master.cf_order_id` (preferred)
 *   or fallback pattern: order_{LOCAL_ID}_timestamp
 */

declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- Bootstrap ---
require_once __DIR__ . '/constant.inc.php';   // defines CF_WEBHOOK_SECRET, CASHFREE_API_ENDPOINT, etc.
require_once __DIR__ . '/database.inc.php';
require_once __DIR__ . '/function.inc.php';

// Require POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// Read payload + headers
$raw = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];
$signature = $headers['x-webhook-signature'] ?? $headers['X-Webhook-Signature'] ?? '';
$ts        = $headers['x-webhook-timestamp'] ?? $headers['X-Webhook-Timestamp'] ?? ''; // optional

// Verify signature (HMAC-SHA256 base64)
if (!defined('CF_WEBHOOK_SECRET') || !CF_WEBHOOK_SECRET) {
    error_log('[CF WEBHOOK] CF_WEBHOOK_SECRET not set');
    http_response_code(500);
    echo 'Server Misconfigured';
    exit;
}
$calc = base64_encode(hash_hmac('sha256', $raw, CF_WEBHOOK_SECRET, true));
if (!hash_equals($calc, (string)$signature)) {
    error_log('[CF WEBHOOK] Bad signature');
    http_response_code(400);
    echo 'Bad signature';
    exit;
}

// (Optional) Basic replay protection (5 min window if header present)
if ($ts !== '' && abs(time() - (int)$ts) > 300) {
    error_log('[CF WEBHOOK] Timestamp skew too large');
    // Not fatal; comment the next two lines if you want to allow
    // http_response_code(400);
    // echo 'Stale';
    // exit;
}

// Decode JSON
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo 'Bad payload';
    exit;
}

// Extract Cashfree order id (cf_order_id)
$cf_order_id = '';
$cf_order_id = $data['order']['order_id']         ?? $cf_order_id;
$cf_order_id = $data['data']['order']['order_id'] ?? $cf_order_id;
$cf_order_id = $data['order_id']                  ?? $cf_order_id;
if ($cf_order_id === '') {
    error_log('[CF WEBHOOK] Missing cf_order_id');
    http_response_code(422);
    echo 'Unprocessable';
    exit;
}

// Determine paid?
$event = strtolower((string)($data['type'] ?? $data['event'] ?? ''));
$paid  = false;
$paid  = $paid || (isset($data['order_status']) && strtoupper($data['order_status']) === 'PAID');
$paid  = $paid || (isset($data['data']['order']['order_status']) && strtoupper($data['data']['order']['order_status']) === 'PAID');
$paid  = $paid || (isset($data['data']['payment']['payment_status']) && strtoupper($data['data']['payment']['payment_status']) === 'SUCCESS');
$paid  = $paid || in_array($event, ['order.paid','payment.success','payment.captured'], true);

// Amount (optional check)
$paid_amount = null;
if (isset($data['data']['order']['order_amount'])) {
    $paid_amount = (float)$data['data']['order']['order_amount'];
} elseif (isset($data['order_amount'])) {
    $paid_amount = (float)$data['order_amount'];
}

// Map cf_order_id -> local order id
function find_local_order_id(mysqli $con, string $cf): ?int {
    // Preferred: stored mapping
    $q = mysqli_prepare($con, "SELECT id FROM order_master WHERE cf_order_id=? LIMIT 1");
    if ($q) {
        mysqli_stmt_bind_param($q, "s", $cf);
        mysqli_stmt_execute($q);
        $rs = mysqli_stmt_get_result($q);
        if ($row = mysqli_fetch_assoc($rs)) {
            mysqli_stmt_close($q);
            return (int)$row['id'];
        }
        mysqli_stmt_close($q);
    }
    // Fallback: parse pattern order_{LOCALID}_...
    if (preg_match('/^order_(\d+)_/i', $cf, $m)) return (int)$m[1];
    return null;
}

$local_id = find_local_order_id($con, $cf_order_id);
if ($local_id === null) {
    error_log("[CF WEBHOOK] Map fail cf_order_id=$cf_order_id");
    http_response_code(200);
    echo 'OK';
    exit;
}

// Load order + idempotency
$stmt = mysqli_prepare($con, "SELECT id, final_price, payment_status FROM order_master WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $local_id);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$order) {
    error_log("[CF WEBHOOK] Local order not found id=$local_id");
    http_response_code(200);
    echo 'OK';
    exit;
}
if (strtolower($order['payment_status']) === 'success') {
    http_response_code(200);
    echo 'OK';
    exit;
}

// Update atomically
mysqli_begin_transaction($con);
try {
    if ($paid) {
        // Optional amount check
        if ($paid_amount !== null && abs((float)$order['final_price'] - $paid_amount) > 0.99) {
            error_log("[CF WEBHOOK] Amount mismatch local={$order['final_price']} cf={$paid_amount} id=$local_id");
        }

        // Mark success + ensure type is cashfree; bump order_status 1->2
        $u1 = mysqli_prepare($con, "UPDATE order_master SET payment_status='success', payment_type='cashfree' WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($u1, "i", $local_id);
        mysqli_stmt_execute($u1);
        mysqli_stmt_close($u1);

        $u2 = mysqli_prepare($con, "UPDATE order_master SET order_status=CASE WHEN order_status=1 THEN 2 ELSE order_status END WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($u2, "i", $local_id);
        mysqli_stmt_execute($u2);
        mysqli_stmt_close($u2);

        // (Optional) notify user/restaurant/admin here

    } else {
        // Mark failure if explicitly failed; otherwise keep pending
        $failed = false;
        $failed = $failed || (isset($data['order_status']) && strtoupper($data['order_status']) === 'FAILED');
        $failed = $failed || (isset($data['data']['payment']['payment_status']) && strtoupper($data['data']['payment']['payment_status']) === 'FAILED');
        $failed = $failed || in_array($event, ['payment.failed','order.failed'], true);

        if ($failed) {
            $u3 = mysqli_prepare($con, "UPDATE order_master SET payment_status='failed' WHERE id=? LIMIT 1");
            mysqli_stmt_bind_param($u3, "i", $local_id);
            mysqli_stmt_execute($u3);
            mysqli_stmt_close($u3);
        }
    }

    mysqli_commit($con);
} catch (Throwable $e) {
    mysqli_rollback($con);
    error_log('[CF WEBHOOK] DB error: '.$e->getMessage());
    http_response_code(500);
    echo 'ERR';
    exit;
}

http_response_code(200);
echo 'OK';
