<?php
session_start();
include('../database.inc.php');
include('../function.inc.php');
include('../constant.inc.php');

header('Content-Type: application/json');

$mobile = isset($_POST['mobile']) ? get_safe_value($_POST['mobile']) : '';
if(!preg_match('/^[0-9]{10}$/',$mobile)){
  echo json_encode(['ok'=>false,'message'=>'Invalid mobile']); exit;
}

$cool = defined('OTP_RESEND_SECONDS') ? OTP_RESEND_SECONDS : 30;
$expS = defined('OTP_EXPIRY_SECONDS') ? OTP_EXPIRY_SECONDS : 300;

/* Rate-limit */
$key = "OTP_LAST_TS_$mobile";
$now = time();
if(isset($_SESSION[$key]) && ($now - $_SESSION[$key]) < $cool){
  echo json_encode(['ok'=>false,'message'=>'Please wait before requesting OTP again']); exit;
}

/* Existing user? (table `user`) */
$user_q = mysqli_query($con,"SELECT id,name FROM `user` WHERE mobile='$mobile' LIMIT 1");
$need_name = (mysqli_num_rows($user_q)==0);

/* Generate + store OTP */
$otp = rand(100000,999999);
$_SESSION["OTP_CODE_$mobile"] = $otp;
$_SESSION["OTP_EXP_$mobile"]  = $now + $expS;
$_SESSION[$key] = $now;

/* Send via Fast2SMS */
$api = "https://www.fast2sms.com/dev/bulkV2?authorization=".rawurlencode(FAST2SMS_AUTH).
       "&route=dlt&sender_id=".rawurlencode(FAST2SMS_SENDER).
       "&message=".rawurlencode(FAST2SMS_TEMPLATE_ID).
       "&variables_values=".rawurlencode($otp).
       "&flash=0&numbers=".rawurlencode("91$mobile");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if($err){
  echo json_encode(['ok'=>false,'message'=>'SMS gateway error']); exit;
}

// (Optional) Fast2SMS response validation
echo json_encode(['ok'=>true,'need_name'=>$need_name]);
