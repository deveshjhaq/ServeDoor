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

// Get data from POST request
$ac_holder_name = $_POST['ac_holder_name'] ?? '';
$ac_no = $_POST['ac_no'] ?? '';
$ifsc_code = $_POST['ifsc_code'] ?? '';
$upi_id = $_POST['upi_id'] ?? '';

// Basic validation
if (empty($ac_holder_name) || empty($ac_no) || empty($ifsc_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill all required bank details.']);
    exit();
}

$stmt = $con->prepare("UPDATE delivery_boy SET ac_holder_name=?, ac_no=?, ifsc_code=?, upi_id=? WHERE id=?");
$stmt->bind_param("ssssi", $ac_holder_name, $ac_no, $ifsc_code, $upi_id, $delivery_boy_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Bank details updated successfully!',
        'data' => [
            'ac_holder_name' => $ac_holder_name,
            'ac_no' => $ac_no,
            'ifsc_code' => $ifsc_code,
            'upi_id' => $upi_id
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update details.']);
}
?>