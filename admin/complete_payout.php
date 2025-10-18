<?php
include_once('../database.inc.php');
include_once('../function.inc.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['IS_LOGIN'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payout_id = get_safe_value($_POST['payout_id']);
    $transaction_details = get_safe_value($_POST['transaction_details']);
    $payout_date = date('Y-m-d H:i:s');

    if (empty($payout_id) || empty($transaction_details)) {
        // Handle error - maybe redirect back with an error message
        redirect('payouts.php');
        exit();
    }

    $stmt = mysqli_prepare($con, "UPDATE delivery_payouts SET status = 'completed', payout_date = ?, transaction_details = ? WHERE id = ? AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, "ssi", $payout_date, $transaction_details, $payout_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_msg'] = "Payout #" . $payout_id . " has been successfully marked as completed.";
    } else {
        // Handle potential error
    }
    
    redirect('payouts.php');
} else {
    redirect('payouts.php');
}
?>