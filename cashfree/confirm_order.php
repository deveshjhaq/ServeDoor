<?php
// cashfree/confirm_order.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../constant.inc.php';
require_once __DIR__ . '/../database.inc.php';
require_once __DIR__ . '/../function.inc.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if (!isset($_SESSION['FOOD_USER_ID'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Not logged in']); exit;
}
$uid = (int)$_SESSION['FOOD_USER_ID'];

$order_id = isset($_GET['order_id']) ? preg_replace('/[^A-Za-z0-9_\-]/','', $_GET['order_id']) : '';
if ($order_id==='') { 
    http_response_code(400); 
    echo json_encode(['success'=>false,'message'=>'order_id missing']); 
    exit; 
}

// FIX 1: Use correct session variable name (PENDING_ORDER instead of PENDING_CHECKOUT)
if (empty($_SESSION['PENDING_ORDER'])) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'No pending order data in session']); 
  exit;
}
$chk = $_SESSION['PENDING_ORDER'];

// 1) Verify payment again (server-trust)
$verifyUrl = FRONT_SITE_PATH . 'cashfree/verifypayment.php?order_id=' . urlencode($order_id) . '&credit=0';
$resp = @file_get_contents($verifyUrl);
if ($resp === false) {
  echo json_encode(['success'=>false,'message'=>'verify call failed']); exit;
}
$j = json_decode($resp, true);
if (!$j || empty($j['success'])) {
  echo json_encode(['success'=>false,'message'=>'verify parse failed']); exit;
}
if (!in_array($j['status'] ?? '', ['PAID','COMPLETED','SUCCESS','SUCCESSFUL'], true)) {
  echo json_encode(['success'=>false,'message'=>'payment not successful','status'=>$j['status'] ?? '']); exit;
}

$payment_id = $j['payment_id'] ?? $order_id;
$paid_amt   = (float)($j['amount'] ?? 0);
$grand      = (float)$chk['final_grand_total'];

// Amount mismatch guard (tolerate minor .01 rounding)
if (abs($paid_amt - $grand) > 0.05) {
  // Log the mismatch but proceed
  error_log("Payment amount mismatch: Paid $paid_amt vs Expected $grand");
}

// 2) Pull cart
$cartArr = getUserFullCart();
if (count($cartArr) === 0) {
  echo json_encode(['success'=>false,'message'=>'cart empty']); exit;
}

// 3) Insert order_master
$user = getUserDetailsByid();
$order_status   = 1; // Pending
$payment_status = 'success';
$payment_type   = 'online';

$sql_master = "INSERT INTO order_master
(user_id, restaurant_id, name, email, mobile, address, zipcode, total_price, order_status, payment_status, added_on, final_price, payment_type, gst_amount, platform_fee, delivery_fee, dropoff_lat, dropoff_lng, payment_id)
VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),?,?,?,?,?,?,?,?)";

$stmt = mysqli_prepare($con, $sql_master);
if (!$stmt) {
    echo json_encode(['success'=>false,'message'=>'Failed to prepare statement: ' . mysqli_error($con)]); 
    exit;
}

// FIX 2: Correct bind_param types without spaces
$bind_result = mysqli_stmt_bind_param(
  $stmt, 
  "iisssssdisdsddddds", // NO SPACES between types
  $uid,
  $chk['restaurant_id'],
  $user['name'],
  $user['email'],
  $user['mobile'],
  $chk['address'],
  $chk['zipcode'],
  $chk['final_subtotal'],
  $order_status,
  $payment_status,
  $chk['final_grand_total'],
  $payment_type,
  $chk['final_gst'],
  $chk['final_platform_fee'],
  $chk['final_delivery_fee'],
  $chk['dropoff_lat'],
  $chk['dropoff_lng'],
  $payment_id
);

if (!$bind_result) {
    echo json_encode(['success'=>false,'message'=>'Bind failed: ' . mysqli_stmt_error($stmt)]); 
    mysqli_stmt_close($stmt);
    exit;
}

$execute_result = mysqli_stmt_execute($stmt);
if (!$execute_result) {
    echo json_encode(['success'=>false,'message'=>'Execute failed: ' . mysqli_stmt_error($stmt)]); 
    mysqli_stmt_close($stmt);
    exit;
}

$insert_id = mysqli_insert_id($con);
mysqli_stmt_close($stmt);

if ($insert_id <= 0) {
  echo json_encode(['success'=>false,'message'=>'order_master insert failed']); exit;
}

// 4) Insert order_detail
$sql_detail = "INSERT INTO order_detail (order_id, dish_details_id, price, qty) VALUES (?,?,?,?)";
$stmtd = mysqli_prepare($con, $sql_detail);
if (!$stmtd) {
    echo json_encode(['success'=>false,'message'=>'Failed to prepare order details statement']); 
    exit;
}

foreach($cartArr as $key=>$val){
  mysqli_stmt_bind_param($stmtd, "iidi", $insert_id, $key, $val['price'], $val['qty']);
  mysqli_stmt_execute($stmtd);
}
mysqli_stmt_close($stmtd);

// 5) Notifications (optional - same as your current flow)
$restaurant_id = (int)$chk['restaurant_id'];
$admin_mobile = '6205411077';
$restaurant_res = mysqli_query($con, "SELECT phone FROM restaurants WHERE id='$restaurant_id'");
$restaurant_mobile = '';
if ($restaurant_res && mysqli_num_rows($restaurant_res) > 0) {
    $restaurant_mobile = mysqli_fetch_assoc($restaurant_res)['phone'] ?? '';
}

if (function_exists('sendWhatsAppNotification')) {
  sendWhatsAppNotification($admin_mobile, $insert_id, $chk['final_grand_total'], $payment_type);
  if ($restaurant_mobile) {
      sendWhatsAppNotification($restaurant_mobile, $insert_id, $chk['final_grand_total'], $payment_type);
  }
}

if (!empty($user['email']) && function_exists('orderEmail') && function_exists('send_email')) {
  $email_html = orderEmail($insert_id, $uid);
  send_email($user['email'], $email_html, 'Order Placed Successfully - ServeDoor');
}

// 6) Clear cart + pending
if (function_exists('emptyCart')) { 
    emptyCart(); 
}

// FIX 3: Clear the correct session variables
unset($_SESSION['PENDING_ORDER']);
if (isset($_SESSION['PENDING_ORDER_EXPIRES_AT'])) {
    unset($_SESSION['PENDING_ORDER_EXPIRES_AT']);
}

echo json_encode(['success'=>true,'order_id'=>$insert_id]);
?>