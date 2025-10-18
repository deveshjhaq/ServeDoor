<?php
session_start();
include('database.inc.php');
include('function.inc.php');
include('constant.inc.php');
include('smtp/PHPMailerAutoload.php');

header('Content-Type: application/json; charset=utf-8');

$type = isset($_POST['type']) ? get_safe_value($_POST['type']) : '';
$added_on = date('Y-m-d H:i:s');

/* ===== Helpers ===== */
function json_error($msg, $extra = []) {
    echo json_encode(array_merge(['status'=>'error','msg'=>$msg], $extra));
    exit;
}
function json_ok($data = []) {
    echo json_encode(array_merge(['status'=>'success'], $data));
    exit;
}

/* ====== Only OTP flows allowed ====== */
/* Disable old email/password flows hard */
if (in_array($type, ['register','login','forgot'])) {
    json_error('Email/Password login is disabled. Use mobile OTP.');
}

/* ====== SEND OTP ====== */
if ($type === 'send_otp') {
    $mobile = isset($_POST['mobile']) ? get_safe_value($_POST['mobile']) : '';
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        json_error('Invalid mobile number');
    }

    // Rate-limit
    $cooldown = defined('OTP_RESEND_SECONDS') ? (int)OTP_RESEND_SECONDS : 30;
    $last_key = "OTP_LAST_TS_$mobile";
    $now = time();
    if (isset($_SESSION[$last_key]) && ($now - $_SESSION[$last_key]) < $cooldown) {
        json_error('Please wait before requesting OTP again');
    }

    // New user?
    $uq = mysqli_query($con, "SELECT id,name FROM `user` WHERE mobile='$mobile' LIMIT 1");
    $need_name = (mysqli_num_rows($uq) == 0);

    // Generate + store OTP (5 min default)
    $otp = rand(100000, 999999);
    $expSec = defined('OTP_EXPIRY_SECONDS') ? (int)OTP_EXPIRY_SECONDS : 300;
    $_SESSION["OTP_CODE_$mobile"] = $otp;
    $_SESSION["OTP_EXP_$mobile"]  = $now + $expSec;
    $_SESSION[$last_key] = $now;

    // Send via Fast2SMS (DLT)
    $api = "https://www.fast2sms.com/dev/bulkV2?authorization=" . rawurlencode(FAST2SMS_AUTH)
         . "&route=dlt&sender_id=" . rawurlencode(FAST2SMS_SENDER)
         . "&message=" . rawurlencode(FAST2SMS_TEMPLATE_ID)
         . "&variables_values=" . rawurlencode($otp)
         . "&flash=0&numbers=" . rawurlencode("91$mobile");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        json_error('SMS gateway error');
    }

    json_ok(['need_name' => $need_name]);
}

/* ====== VERIFY OTP ====== */
if ($type === 'verify_otp') {
    $mobile    = isset($_POST['mobile']) ? get_safe_value($_POST['mobile']) : '';
    $otp       = isset($_POST['otp']) ? get_safe_value($_POST['otp']) : '';
    $full_name = isset($_POST['full_name']) ? get_safe_value($_POST['full_name']) : '';

    if (!preg_match('/^[0-9]{10}$/', $mobile)) { json_error('Invalid mobile number'); }
    if (!preg_match('/^[0-9]{6}$/', $otp)) { json_error('Invalid OTP'); }

    $otp_key = "OTP_CODE_$mobile";
    $exp_key = "OTP_EXP_$mobile";
    if (!isset($_SESSION[$otp_key]) || !isset($_SESSION[$exp_key])) {
        json_error('OTP not requested');
    }
    if (time() > $_SESSION[$exp_key]) {
        json_error('OTP expired');
    }
    if ($otp != $_SESSION[$otp_key]) {
        json_error('Incorrect OTP');
    }

    // Login or create in table `user`
    $uq = mysqli_query($con, "SELECT * FROM `user` WHERE mobile='$mobile' LIMIT 1");
    if (mysqli_num_rows($uq) == 0) {
        // First-time signup
        if ($full_name == '') { $full_name = 'User' . substr($mobile, 6); }

        // Referral
        $from_ref = isset($_SESSION['FROM_REFERRAL_CODE']) ? get_safe_value($_SESSION['FROM_REFERRAL_CODE']) : '';
        $my_ref   = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 15);

        // Create user (email/password blank, email_verify=1)
        $sql = "INSERT INTO `user`
                (name, email, mobile, password, status, email_verify, rand_str, referral_code, from_referral_code, added_on)
                VALUES
                ('$full_name', '', '$mobile', '', 1, 1, '', '$my_ref', '$from_ref', '$added_on')";
        if (!mysqli_query($con, $sql)) {
            json_error('DB insert failed: ' . mysqli_error($con));
        }
        $uid = mysqli_insert_id($con);

        // Optional: welcome wallet credit (if configured)
        if (function_exists('getSetting') && function_exists('manageWallet')) {
            $getSetting = getSetting();
            if (!empty($getSetting['wallet_amt']) && (int)$getSetting['wallet_amt'] > 0) {
                manageWallet($uid, (int)$getSetting['wallet_amt'], 'in', 'Register');
            }
        }
        // Referral bonus etc. yahan add kar sakte hain

        $_SESSION['FOOD_USER_NAME'] = $full_name;
    } else {
        $row = mysqli_fetch_assoc($uq);
        $uid = $row['id'];
        $_SESSION['FOOD_USER_NAME'] = ($row['name'] ?: $full_name);
    }

    // Set login session
    $_SESSION['FOOD_USER_ID'] = $uid;
    $_SESSION['FOOD_USER_EMAIL'] = ''; // email not used in OTP flow

    // Cleanup OTP
    unset($_SESSION[$otp_key], $_SESSION[$exp_key]);

    // Merge guest cart to user cart (like your old login block)
    if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0 && function_exists('manageUserCart')) {
        foreach ($_SESSION['cart'] as $key => $val) {
            manageUserCart($_SESSION['FOOD_USER_ID'], $val['qty'], $key);
        }
    }

    json_ok(); // {status:"success"}
}

/* Reached here with unknown type */
json_error('Invalid request type');
