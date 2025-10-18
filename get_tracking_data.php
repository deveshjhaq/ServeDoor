<?php
// Start session safely and include database
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once('database.inc.php');
include_once('function.inc.php'); // Include if get_safe_value is needed

// Set the content type to JSON
header('Content-Type: application/json');

// User must be logged in to track an order
if (!isset($_SESSION['FOOD_USER_ID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed. Please log in.']);
    exit();
}
$user_id = $_SESSION['FOOD_USER_ID'];

// Get the order ID from the request
$order_id = isset($_GET['order_id']) ? get_safe_value($_GET['order_id']) : null;
if(!$order_id){
    echo json_encode(['status' => 'error', 'message' => 'Order ID not provided.']);
    exit();
}

// Fetch order, restaurant, and delivery boy details in one secure query
// This query ensures the user can only fetch data for their own order
$sql = "SELECT 
            om.dropoff_lat, om.dropoff_lng,
            r.lat as pickup_lat, r.lng as pickup_lng,
            db.current_lat, db.current_lng, db.last_seen_at
        FROM order_master om
        JOIN restaurants r ON om.restaurant_id = r.id
        LEFT JOIN delivery_boy db ON om.delivery_boy_id = db.id
        WHERE om.id = ? AND om.user_id = ?";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($res) > 0){
    $data = mysqli_fetch_assoc($res);
    
    // Check if delivery boy was seen recently to determine online status
    $is_online = false;
    if (isset($data['last_seen_at'])) {
        $last_seen_at = strtotime($data['last_seen_at']);
        // Check if seen in the last 3 minutes
        if ((time() - $last_seen_at) < 180) { 
            $is_online = true;
        }
    }
    
    // Send all the required coordinates and status in a JSON format
    echo json_encode([
        'status' => 'success',
        'delivery_boy' => [
            'lat' => $data['current_lat'],
            'lng' => $data['current_lng'],
            'is_online' => $is_online
        ],
        'pickup' => [
            'lat' => $data['pickup_lat'],
            'lng' => $data['pickup_lng']
        ],
        'dropoff' => [
            'lat' => $data['dropoff_lat'],
            'lng' => $data['dropoff_lng']
        ]
    ]);
} else {
    // If no order is found, it's either an invalid ID or not the user's order
    echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied.']);
}
mysqli_stmt_close($stmt);
?>