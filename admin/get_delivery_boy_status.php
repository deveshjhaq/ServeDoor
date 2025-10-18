<?php
include_once('../database.inc.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

if (!isset($_SESSION['IS_LOGIN'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$delivery_boy_id = $_GET['id'] ?? null;
if(!$delivery_boy_id){
    echo json_encode(['status' => 'error', 'message' => 'Delivery boy ID not provided.']);
    exit();
}

$stmt = mysqli_prepare($con, "SELECT current_lat, current_lng, last_seen_at FROM delivery_boy WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($res) > 0){
    $row = mysqli_fetch_assoc($res);
    $last_seen_at = strtotime($row['last_seen_at']);
    $is_online = (time() - $last_seen_at) < 180;

    echo json_encode([
        'status' => 'success',
        'lat' => $row['current_lat'],
        'lng' => $row['current_lng'],
        'online_status' => $is_online ? 'Online' : 'Offline'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Delivery boy not found.']);
}
?>