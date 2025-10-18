<?php
require_once __DIR__ . '/../constant.inc.php';
require_once __DIR__ . '/../database.inc.php';
require_once __DIR__ . '/../function.inc.php';

/* ---- Harden output: JSON only, no notices/warnings on screen ---- */
ini_set('display_errors', '0');
error_reporting(E_ALL);

/* Clean any previous output */
if (function_exists('ob_get_level')) {
  while (ob_get_level() > 0) { ob_end_clean(); }
}
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); echo '{}'; exit; }

/* ---- Input ---- */
$order_id = isset($_GET['order_id']) ? preg_replace('/[^A-Za-z0-9_\-]/','', $_GET['order_id']) : '';
$doCredit = isset($_GET['credit']) ? (int)$_GET['credit'] : 0;
if ($order_id === '') {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'order_id missing']); exit;
}

/* ---- Cashfree setup ---- */
$env  = defined('CASHFREE_ENV') ? CASHFREE_ENV : 'production';
$base = ($env === 'production') ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';
$app  = trim((string)CASHFREE_APP_ID);
$sec  = trim((string)CASHFREE_SECRET_KEY);
if ($app === '' || $sec === '') {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Cashfree keys not configured']); exit;
}

/* ---- Helper: GET to Cashfree ---- */
$cfGet = function(string $url) use ($app,$sec) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
      'x-client-id: '.$app,
      'x-client-secret: '.$sec,
      'x-api-version: 2022-09-01',
      'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT        => 25,
  ]);
  $body = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);
  return [$http, $err, $body];
};

/* ---- 1) Try /orders/{id} (new API) ---- */
list($http, $err, $body) = $cfGet($base.'/orders/'.$order_id);
if ($err || $http >= 400 || !$body) {
  // ---- 2) Fallback to /orders/{id}/payments ----
  list($http2, $err2, $body2) = $cfGet($base.'/orders/'.$order_id.'/payments');
  if ($err2 || $http2 >= 400 || !$body2) {
    http_response_code(502);
    echo json_encode([
      'success'=>false,
      'message'=>'cashfree_fetch_error',
      'http'=>$http, 'err'=>$err,
      'http_fallback'=>$http2, 'err_fallback'=>$err2
    ]);
    exit;
  }

  // payments array response
  $payments = json_decode($body2, true);
  if (!is_array($payments) || count($payments) === 0) {
    echo json_encode(['success'=>false,'message'=>'no_payments_found']); exit;
  }
  $p = $payments[0];
  $status = $p['payment_status'] ?? '';
  $amount = (float)($p['payment_amount'] ?? 0);
  $payment_id = $p['cf_payment_id'] ?? ($p['payment_id'] ?? '');

  $order_status_success = in_array($status, ['SUCCESS','SUCCESSFUL','CAPTURED'], true);
  $order_amount = $amount;

} else {
  // /orders/{id} response
  $data = json_decode($body, true);
  $status_field = $data['order_status'] ?? $data['status'] ?? '';
  $order_status_success = in_array($status_field, ['PAID','COMPLETED','SUCCESS','SUCCESSFUL'], true);
  $order_amount = (float)($data['order_amount'] ?? 0);
  $payment_id = '';
  // try to pull a success payment id if present
  if (isset($data['payments']) && is_array($data['payments'])) {
    foreach ($data['payments'] as $p) {
      if (in_array(($p['payment_status'] ?? ''), ['SUCCESS','SUCCESSFUL','CAPTURED'], true)) {
        $payment_id = $p['cf_payment_id'] ?? ($p['payment_id'] ?? '');
        break;
      }
    }
  }
  $status = $status_field;
}

/* ---- Build base output ---- */
$uniqueId = $payment_id ?: ('CFORDER_'.$order_id);
$out = [
  'success'    => true,
  'order_id'   => $order_id,
  'status'     => $status ?? '',
  'amount'     => $order_amount,
  'payment_id' => $uniqueId
];

/* ---- Wallet credit if requested & looks like wallet order ---- */
$isWallet = (strpos($order_id, 'SDWALLET_') === 0);
if ($doCredit && $isWallet && $order_status_success && $order_amount > 0) {
  // Resolve user from order_id SDWALLET_<ts>_<uid>
  $user_id = 0;
  if (preg_match('/SDWALLET_\d+_(\d+)/', $order_id, $m)) $user_id = (int)$m[1];

  if ($user_id > 0) {
    // idempotent insert on payment_id
    $stmt = mysqli_prepare($con, "SELECT id FROM wallet WHERE payment_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $uniqueId);
    mysqli_stmt_execute($stmt);
    $dup = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($dup) === 0) {
      $msg = 'Wallet top-up via Cashfree (Verify)';
      $now = date('Y-m-d H:i:s');
      $type = 'in';
      $stmt = mysqli_prepare($con,
        "INSERT INTO wallet (user_id, amt, msg, type, payment_id, added_on)
         VALUES (?,?,?,?,?,?)");
      mysqli_stmt_bind_param($stmt, 'idssss', $user_id, $order_amount, $msg, $type, $uniqueId, $now);
      mysqli_stmt_execute($stmt);
      $out['credited'] = true;
    } else {
      $out['credited'] = 'already';
    }
  } else {
    $out['credited'] = false;
    $out['credit_reason'] = 'user_not_resolved';
  }
}

/* ---- Final JSON ---- */
echo json_encode($out);
