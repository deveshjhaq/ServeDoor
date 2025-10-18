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
$address_id = (int)$_POST['address_id'];
$address_type = mysqli_real_escape_string($con, $_POST['address_type']);
$address = mysqli_real_escape_string($con, $_POST['address']);
$city = mysqli_real_escape_string($con, $_POST['city']);
$pincode = mysqli_real_escape_string($con, $_POST['pincode']);
$state = mysqli_real_escape_string($con, $_POST['state']);

// Validate pincode
if (!preg_match('/^\d{6}$/', $pincode)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 6-digit pincode.']);
    exit();
}

$stmt = mysqli_prepare($con, 
    "UPDATE user_addresses SET 
    address_type = ?, address = ?, city = ?, pincode = ?, state = ? 
    WHERE id = ? AND user_id = ?"
);

mysqli_stmt_bind_param($stmt, "sssssii", $address_type, $address, $city, $pincode, $state, $address_id, $uid);

if(mysqli_stmt_execute($stmt)){
    echo json_encode(['status' => 'success', 'message' => 'Address updated successfully!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update address.']);
}

mysqli_stmt_close($stmt);
?>