<?php
/**
 * File: cashfree/persist_checkout.php
 * Purpose: Online/UPI शुरू करने से पहले checkout details को session में सुरक्षित करना
 * Output: JSON (no HTML)
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

// ---- Includes (NO header.php; it prints HTML) ----
$baseDir  = dirname(__DIR__);                 // /public_html
$constPath= $baseDir . '/constant.inc.php';
$dbPath   = $baseDir . '/database.inc.php';   // Changed from connection.inc.php
$funcPath = $baseDir . '/function.inc.php';   // Fixed: functions.php → function.inc.php

if (file_exists($constPath)) { require_once $constPath; }
if (file_exists($dbPath))    { require_once $dbPath; }
if (file_exists($funcPath))  { require_once $funcPath; }

// ---- Helpers ----
function jc_out(array $arr, int $code=200){ http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function jc_fail(string $msg, int $code=400){ jc_out(['success'=>false,'message'=>$msg], $code); }
function p($k,$d=''){ return isset($_POST[$k]) ? trim($_POST[$k]) : $d; }
function ffloat($v){ return is_numeric($v) ? (float)$v : 0.0; }
function persist_log($line){
  $dir = __DIR__ . '/logs';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  @file_put_contents($dir.'/persist.log', date('c').' '.$line.PHP_EOL, FILE_APPEND);
}

// ---- Method/Auth guards ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jc_fail('POST required', 405); }
if (!isset($_SESSION['FOOD_USER_ID']) || (int)$_SESSION['FOOD_USER_ID']<=0) { 
    jc_fail('Login required', 401); 
}
$uid = (int)$_SESSION['FOOD_USER_ID'];

// ---- Read posted fields (checkout form से आए हुए) ----
$payment_type        = p('payment_type');          // expect "online" here
$address             = p('address');
$zipcode             = p('zipcode');               // OPTIONAL (Google कई बार नहीं देता)
$city                = p('city');

$final_subtotal      = ffloat(p('final_subtotal'));
$final_gst           = ffloat(p('final_gst'));
$final_platform_fee  = ffloat(p('final_platform_fee'));
$final_delivery_fee  = ffloat(p('final_delivery_fee'));
$final_grand_total   = ffloat(p('final_grand_total'));

$dropoff_lat         = p('dropoff_lat');
$dropoff_lng         = p('dropoff_lng');
$restaurant_id       = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0;

// ---- Validations ----
if ($payment_type !== 'online')              { jc_fail('Invalid payment_type (expected online)'); }
if ($address === '')                         { jc_fail('Address required', 422); }
// zipcode OPTIONAL: खाली हो तो भी चल जाएगा
if (!is_numeric($dropoff_lat) || !is_numeric($dropoff_lng)) { jc_fail('Valid lat/lng required', 422); }
if ($final_grand_total <= 0)                 { jc_fail('Invalid payable amount', 422); }
if ($restaurant_id <= 0)                     { jc_fail('Restaurant id missing', 422); }

// ---- Optional server-side subtotal sanity check (cart के आधार पर) ----
try{
  if (function_exists('getUserFullCart') && isset($con)) {
    $cartArr = getUserFullCart();
    
    // Check if cart is not empty
    if (empty($cartArr)) {
        jc_fail('Your cart is empty. Please add items to cart.', 422);
    }
    
    $serverSubtotal = 0.0;
    foreach ($cartArr as $row) {
      $qty   = isset($row['qty'])   ? (int)$row['qty']   : 0;
      $price = isset($row['price']) ? (float)$row['price'] : 0.0;
      $serverSubtotal += ($qty * $price);
    }
    
    // 2 रुपये से ज्यादा mismatch पर server subtotal मान लें
    if (abs($serverSubtotal - $final_subtotal) > 2) {
      $final_subtotal    = $serverSubtotal;
      $final_grand_total = $final_subtotal + $final_platform_fee + $final_gst + $final_delivery_fee;
      
      persist_log("subtotal_adjusted: client=$final_subtotal server=$serverSubtotal new_total=$final_grand_total");
    }
  }
}catch(\Throwable $e){
  persist_log('subtotal_check_error: '.$e->getMessage());
  // Continue anyway, don't fail the request
}

// ---- Additional security: Check restaurant single-order restriction ----
try {
    if (function_exists('getUserFullCart') && isset($con)) {
        $cartArr = getUserFullCart();
        $restaurant_ids = [];
        
        foreach($cartArr as $item) {
            if (isset($item['restaurant_id'])) {
                $restaurant_ids[] = (int)$item['restaurant_id'];
            }
        }
        
        $unique_restaurants = array_unique($restaurant_ids);
        if (count($unique_restaurants) > 1) {
            jc_fail('You can only order from one restaurant at a time.', 422);
        }
        
        // Verify the restaurant_id matches
        if (!in_array($restaurant_id, $unique_restaurants)) {
            jc_fail('Restaurant mismatch detected.', 422);
        }
    }
} catch(\Throwable $e) {
    persist_log('restaurant_check_error: '.$e->getMessage());
    // Continue anyway
}

// ---- Save to session (payment success के बाद यही से order बनाया जाएगा) ----
$_SESSION['PENDING_ORDER'] = [
  'uid'                => $uid,
  'restaurant_id'      => $restaurant_id,

  'address'            => $address,
  'zipcode'            => $zipcode,        // may be ''
  'city'               => $city,
  'dropoff_lat'        => (float)$dropoff_lat,
  'dropoff_lng'        => (float)$dropoff_lng,

  'final_subtotal'     => $final_subtotal,
  'final_gst'          => $final_gst,
  'final_platform_fee' => $final_platform_fee,
  'final_delivery_fee' => $final_delivery_fee,
  'final_grand_total'  => $final_grand_total,

  'payment_type'       => 'online',        // lock
  'purpose'            => 'order',         // createorder.php hint
  'ts'                 => time(),
  'cart_hash'          => md5(serialize(getUserFullCart())), // For cart verification later
];
$_SESSION['PENDING_ORDER_EXPIRES_AT'] = time() + 30*60; // 30 minutes

// Clear any old pending orders
if (isset($_SESSION['OLD_PENDING_ORDERS'])) {
    unset($_SESSION['OLD_PENDING_ORDERS']);
}

persist_log("OK uid=$uid rid=$restaurant_id amt=$final_grand_total addr=".substr($address,0,50)." pin=".( $zipcode!=='' ? $zipcode : 'MISSING' ));

jc_out([
  'success' => true,
  'message' => 'Checkout data saved successfully',
  'amount'  => $final_grand_total,
  'order_data' => [
      'restaurant_id' => $restaurant_id,
      'items_count' => count($cartArr ?? []),
      'expires_in' => '30 minutes'
  ]
]);
?>