<?php
// check_username.php
session_start();
include_once('../database.inc.php');
include_once('../function.inc.php');

header('Content-Type: application/json');

$username = isset($_GET['username']) ? get_safe_value($_GET['username']) : '';

if (empty($username)) {
    echo json_encode(['available' => false]);
    exit;
}

// Validate username format
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    echo json_encode(['available' => false]);
    exit;
}

// Check if username exists
$stmt = mysqli_prepare($con, "SELECT id FROM restaurants WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$available = (mysqli_num_rows($result) === 0);

echo json_encode(['available' => $available]);
?>