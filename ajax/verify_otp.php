<?php
session_start();
include('../database.inc.php');
include('../function.inc.php');

header('Content-Type: application/json');

$mobile = isset($_POST['mobile']) ? get_safe_value($_POST['mobile']) : '';
$otp    = isset($_POST['otp']) ? get_safe_value($_POST['otp']) : '';
$name   = isset($_POST['full_name']) ? get_safe_value($_POST['full_name']) : '';

if(!preg_match('/^[0-9]{10}$/',$mobile)){
  echo json_encode(['ok'=>false,'message'=>'Invalid mobile']); exit;
}
if(!preg_match('/^[0-9]{6}$/',$otp)){
  echo json_encode(['ok'=>false,'message'=>'Invalid OTP']); exit;
}

$otp_key = "OTP_CODE_$mobile";
$exp_key = "OTP_EXP_$mobile";
if(!isset($_SESSION[$otp_key]) || !isset($_SESSION[$exp_key])){
  echo json_encode(['ok'=>false,'message'=>'OTP not requested']); exit;
}
if(time() > $_SESSION[$exp_key]){
  echo json_encode(['ok'=>false,'message'=>'OTP expired']); exit;
}
if($otp != $_SESSION[$otp_key]){
  echo json_encode(['ok'=>false,'message'=>'Incorrect OTP']); exit;
}

/* ---------- LOGIN or REGISTER in `user` table ---------- */
$user_q = mysqli_query($con,"SELECT * FROM `user` WHERE mobile='$mobile' LIMIT 1");
if(mysqli_num_rows($user_q)==0){
  // First-time signup
  if($name==''){ $name = 'User'.substr($mobile,6); }

  // Optional referral support
  $from_ref = isset($_SESSION['FROM_REFERRAL_CODE']) ? get_safe_value($_SESSION['FROM_REFERRAL_CODE']) : '';
  $my_ref   = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 15); // 15-char code

  $added_on = date('Y-m-d H:i:s');
  // Note: table `user` columns from your dump:
  // (name, email, mobile, password, status, email_verify, rand_str, referral_code, from_referral_code, added_on)
  $sql = "INSERT INTO `user`
          (name, email, mobile, password, status, email_verify, rand_str, referral_code, from_referral_code, added_on)
          VALUES
          ('$name', '', '$mobile', '', 1, 1, '', '$my_ref', '$from_ref', '$added_on')";
  if(!mysqli_query($con,$sql)){
    echo json_encode(['ok'=>false,'message'=>'DB insert failed: '.mysqli_error($con)]); exit;
  }
  $uid = mysqli_insert_id($con);

  // (Optional) Wallet credit for referral, etc. — yaha apni policy add kar sakte ho.

} else {
  $row = mysqli_fetch_assoc($user_q);
  $uid = $row['id'];
  if($name==''){ $name = $row['name']; }
}

/* ---------- Set session + cleanup OTP ---------- */
$_SESSION['FOOD_USER_ID']   = $uid;
$_SESSION['FOOD_USER_NAME'] = $name;

unset($_SESSION[$otp_key], $_SESSION[$exp_key]);

echo json_encode(['ok'=>true]);
