<?php
// cashfree/createorder.php — FIXED (Production, CORS + v2022-09-01 API)
session_start();
require_once __DIR__ . '/../constant.inc.php';
require_once __DIR__ . '/../database.inc.php';
require_once __DIR__ . '/../function.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Read input (JSON or form)
$input = [];
if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
  $raw = file_get_contents('php://input');
  $input = json_decode($raw, true) ?: [];
} else {
  $input = $_POST;
}

$amount      = isset($input['amount']) ? (float)$input['amount'] : 0;
$customer_id = isset($input['customer_id']) ? (int)$input['customer_id'] : (int)($_SESSION['FOOD_USER_ID'] ?? 0);
$purpose     = isset($input['purpose']) ? strtolower(trim($input['purpose'])) : 'wallet'; // 'wallet'|'order'
$order_note  = isset($input['order_note']) ? trim($input['order_note']) : '';

if ($amount < 10) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Minimum amount is ₹10']); exit; }
if ($customer_id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid customer']); exit; }

$env  = (defined('CASHFREE_ENV') ? CASHFREE_ENV : 'production');
$base = ($env === 'production') ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';
$app  = trim((string)CASHFREE_APP_ID);
$sec  = trim((string)CASHFREE_SECRET_KEY);
if ($app === '' || $sec === '') { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Cashfree keys not configured']); exit; }

$isWallet   = ($purpose === 'wallet');
$orderPref  = $isWallet ? 'SDWALLET' : 'SDORDER';

// ✅ FIXED: Generate truly unique order_id with microtime and random string
$microtime = str_replace('.', '', microtime(true)); // Remove decimal point
$random_suffix = substr(md5(uniqid(rand(), true)), 0, 6); // 6 char random string
$order_id   = $orderPref . '_' . $customer_id . '_' . $microtime . '_' . $random_suffix;

// Ensure order_id length is within Cashfree limits (45 chars max)
if (strlen($order_id) > 45) {
    $order_id = substr($order_id, 0, 45);
}

if ($order_note === '') $order_note = $isWallet ? (defined('CASHFREE_ORDER_NOTE') ? CASHFREE_ORDER_NOTE : 'Wallet Top-up') : 'Online Order Payment';

$return_url = $isWallet
  ? FRONT_SITE_PATH . 'wallet_success.php?order_id={order_id}'
  : FRONT_SITE_PATH . 'payment_success.php?order_id={order_id}';

$notify_url = FRONT_SITE_PATH . 'cashfree/cashfree_webhook.php';

// ✅ Cashfree v2022-09-01 payload format
$payload = [
  "order_id"       => $order_id,
  "order_amount"   => round($amount, 2),
  "order_currency" => "INR",
  "order_note"     => $order_note,
  "customer_details" => [
    "customer_id"    => (string)$customer_id,
    "customer_email" => $_SESSION['USER_EMAIL']  ?? ('user'.$customer_id.'@servedoor.com'),
    "customer_phone" => $_SESSION['USER_MOBILE'] ?? '9999999999',
  ],
  "order_meta" => [
    "return_url" => $return_url,
    "notify_url" => $notify_url,
  ],
];

// Log the order creation attempt
error_log("Cashfree Order Creation: order_id=$order_id, amount=$amount, purpose=$purpose, customer_id=$customer_id");

$ch = curl_init($base . '/orders');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_HTTPHEADER     => [
    'Content-Type: application/json',
    'x-client-id: '     . $app,
    'x-client-secret: ' . $sec,
    'x-api-version: 2022-09-01',
  ],
  CURLOPT_POSTFIELDS     => json_encode($payload),
  CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

// Log the response for debugging
error_log("Cashfree Response: HTTP $http, Response: " . substr($response, 0, 500));

if ($error) {
  http_response_code(502);
  echo json_encode(['success'=>false,'message'=>'cURL error','error'=>$error]); exit;
}
if ($http >= 400) {
  http_response_code($http);
  echo json_encode(['success'=>false,'message'=>'Cashfree error '.$http,'debug'=>substr((string)$response,0,800)]); exit;
}

$data = json_decode($response, true);
$out  = ['success'=>true, 'order_id'=>$order_id];

if (!empty($data['payment_link'])) {
  $out['payment_link'] = $data['payment_link'];
} elseif (!empty($data['payment_session_id'])) {
  $out['payment_session_id'] = $data['payment_session_id'];
} else {
  http_response_code(502);
  $out = ['success'=>false,'message'=>'Unexpected Cashfree response','debug'=>substr((string)$response,0,800)];
}
echo json_encode($out);
?>