<?php
// Added error reporting to find the exact problem
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('../database.inc.php');
include_once('../function.inc.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['DELIVERY_BOY_USER_LOGIN'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$delivery_boy_id = $_SESSION['DELIVERY_BOY_ID'];

// Check if a request was made in the last 7 days
$check_sql = "SELECT request_date FROM delivery_payouts WHERE delivery_boy_id = ? ORDER BY request_date DESC LIMIT 1";
$stmt_check = mysqli_prepare($con, $check_sql);
mysqli_stmt_bind_param($stmt_check, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
if(mysqli_num_rows($res_check) > 0){
    $last_request_date = strtotime(mysqli_fetch_assoc($res_check)['request_date']);
    if(time() - $last_request_date < (7 * 24 * 60 * 60)){ // 7 days in seconds
        echo json_encode(['status' => 'error', 'message' => 'You can only request a payout once every 7 days.']);
        exit();
    }
}

// Get current balance
$sql_earned = "SELECT SUM(delivery_commission) as total_earned FROM order_master WHERE delivery_boy_id = ? AND order_status = 4";
$stmt_earned = mysqli_prepare($con, $sql_earned);
mysqli_stmt_bind_param($stmt_earned, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_earned);
$total_earned = mysqli_stmt_get_result($stmt_earned)->fetch_assoc()['total_earned'] ?? 0;

$sql_paid = "SELECT SUM(amount) as total_paid FROM delivery_payouts WHERE delivery_boy_id = ? AND status='completed'";
$stmt_paid = mysqli_prepare($con, $sql_paid);
mysqli_stmt_bind_param($stmt_paid, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_paid);
$total_paid = mysqli_stmt_get_result($stmt_paid)->fetch_assoc()['total_paid'] ?? 0;

$current_balance = $total_earned - $total_paid;

if ($current_balance <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'You do not have any balance to withdraw.']);
    exit();
}

// Insert new payout request as 'pending'
$insert_sql = "INSERT INTO delivery_payouts (delivery_boy_id, amount, status, request_date) VALUES (?, ?, 'pending', NOW())";
$stmt_insert = mysqli_prepare($con, $insert_sql);
mysqli_stmt_bind_param($stmt_insert, "id", $delivery_boy_id, $current_balance);
if(mysqli_stmt_execute($stmt_insert)){
    echo json_encode(['status' => 'success', 'message' => 'Your payout request of ₹' . $current_balance . ' has been submitted successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Could not submit your request. Please try again.']);
}
?>