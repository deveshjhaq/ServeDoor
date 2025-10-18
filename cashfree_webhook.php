<?php
/**
 * cashfree_webhook.php (Production-ready)
 * Verifies Cashfree webhook signature, prevents duplicate credits,
 * and updates wallet table on successful payments.
 */

require_once __DIR__ . '/database.inc.php';
require_once __DIR__ . '/function.inc.php';
require_once __DIR__ . '/constant.inc.php';

/* ---------- Raw payload + signature ---------- */
$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

/* ---------- Verify HMAC SHA256 base64 ---------- */
$calc = base64_encode(hash_hmac('sha256', $raw, CASHFREE_WEBHOOK_SECRET, true));
if (!hash_equals($calc, $sig)) {
    http_response_code(400);
    exit('Invalid signature');
}

/* ---------- Decode event ---------- */
$evt = json_decode($raw, true);

$order_status   = $evt['data']['order']['status']          ?? '';
$order_id       = $evt['data']['order']['order_id']        ?? '';
$payment_status = $evt['data']['payment']['payment_status']?? '';
$payment_id     = $evt['data']['payment']['cf_payment_id'] ?? ($evt['data']['payment']['payment_id'] ?? '');
$order_amount   = (float)($evt['data']['order']['order_amount'] ?? 0);

/* ---------- Process only successful payments ---------- */
if (!in_array($order_status, ['PAID','COMPLETED']) &&
    !in_array($payment_status, ['SUCCESS','SUCCESSFUL'])) {
    http_response_code(200);
    exit('Ignored (not success)');
}

/* ---------- Extract user_id from order_id ---------- */
// Order id pattern: SDWALLET_timestamp_userid
$user_id = 0;
if (preg_match('/SDWALLET_\d+_(\d+)/', $order_id, $m)) {
    $user_id = (int)$m[1];
}

if ($user_id <= 0 || $order_amount <= 0 || $payment_id == '') {
    http_response_code(200);
    exit('Bad payload');
}

/* ---------- Prevent duplicate credit ---------- */
$stmt = mysqli_prepare($con, "SELECT id FROM wallet WHERE payment_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $payment_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($res) > 0) {
    http_response_code(200);
    exit('Already credited');
}

/* ---------- Insert wallet credit ---------- */
$msg = 'Wallet top-up via Cashfree (Order '.$order_id.')';
$now = date('Y-m-d H:i:s');
$type = 'in';

$stmt = mysqli_prepare($con,
    "INSERT INTO wallet (user_id, amt, msg, type, payment_id, added_on)
     VALUES (?,?,?,?,?,?)");
mysqli_stmt_bind_param($stmt, 'idssss',
    $user_id, $order_amount, $msg, $type, $payment_id, $now);
mysqli_stmt_execute($stmt);

/* ---------- Done ---------- */
http_response_code(200);
echo 'OK';
