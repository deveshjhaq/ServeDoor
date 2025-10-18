<?php
// Start session to get the ID, but it's safer if the ID is passed from the frontend
session_start();
include('../database.inc.php');

// Get data from POST request
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;
$delivery_boy_id = $_POST['delivery_boy_id'] ?? null;

// Basic validation
if ($lat && $lng && $delivery_boy_id) {
    
    // Security check: Ensure the ID from POST matches the one in the session
    if ($delivery_boy_id == $_SESSION['DELIVERY_BOY_ID']) {
        
        $stmt = $con->prepare("UPDATE delivery_boy SET current_lat = ?, current_lng = ?, last_seen_at = NOW() WHERE id = ?");
        $stmt->bind_param("ddi", $lat, $lng, $delivery_boy_id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Location updated.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Authorization failed.']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
}
?>