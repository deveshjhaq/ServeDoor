<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

include_once('database.inc.php');
header('Content-Type: application/json');

if (!isset($_SESSION['FOOD_USER_ID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$uid = (int)$_SESSION['FOOD_USER_ID'];
$address_id = (int)$_GET['id'];

$stmt = mysqli_prepare($con, "SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $address_id, $uid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0){
    $address = mysqli_fetch_assoc($result);
    echo json_encode(['status' => 'success', 'address' => $address]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Address not found.']);
}

mysqli_stmt_close($stmt);
?>