<?php
require_once __DIR__ . '/../constant.inc.php';

$raw = file_get_contents("php://input");
$headers = getallheaders();
$sig = $headers['x-webhook-signature'] ?? '';
$calc = hash_hmac('sha256',$raw,CASHFREE_WEBHOOK_SECRET);

if ($sig !== $calc) {
  http_response_code(400); echo "Invalid signature"; exit;
}

$data = json_decode($raw,true);
$order_id = $data['data']['order']['order_id'] ?? '';
$status = $data['data']['order']['order_status'] ?? '';
$amount = $data['data']['order']['order_amount'] ?? 0;

if ($status==='PAID' && $order_id) {
  if (strpos($order_id,'SDWALLET_')===0) {
    $uid = explode("_",$order_id)[2] ?? 0;
    if ($uid > 0) { manageWallet($uid,$amount,'in','Wallet Recharge via Webhook'); }
  } elseif (strpos($order_id,'SDORDER_')===0) {
    // Only mark payment success, actual order creation via session
    $oid = str_replace("SDORDER_","",$order_id);
    mysqli_query($con,"UPDATE order_master SET payment_status='success' WHERE id='$oid'");
  }
}
echo json_encode(['success'=>true]);
