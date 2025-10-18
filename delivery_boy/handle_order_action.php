<?php
include_once('../database.inc.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['DELIVERY_BOY_USER_LOGIN'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$delivery_boy_id = $_SESSION['DELIVERY_BOY_ID'];
$order_id = $_POST['order_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$order_id || !$action) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit();
}

// Security Check: Verify this order is assigned to this delivery boy with status 6
$check_stmt = mysqli_prepare($con, "SELECT id FROM order_master WHERE id = ? AND delivery_boy_id = ? AND order_status = 6");
mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $delivery_boy_id);
mysqli_stmt_execute($check_stmt);
if (mysqli_stmt_get_result($check_stmt)->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'This order is not assigned to you or has already been actioned.']);
    exit();
}

if ($action == 'accept') {
    // Set order status to 3 ('On the Way')
    $update_stmt = mysqli_prepare($con, "UPDATE order_master SET order_status = 3 WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "i", $order_id);
    mysqli_stmt_execute($update_stmt);
    echo json_encode(['status' => 'success', 'message' => 'Order #' . $order_id . ' has been accepted!']);
} elseif ($action == 'reject') {
    // Set delivery_boy_id to NULL and order_status back to 1 ('Pending')
    $update_stmt = mysqli_prepare($con, "UPDATE order_master SET delivery_boy_id = NULL, order_status = 1 WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "i", $order_id);
    mysqli_stmt_execute($update_stmt);
    echo json_encode(['status' => 'success', 'message' => 'Order #' . $order_id . ' has been rejected.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>