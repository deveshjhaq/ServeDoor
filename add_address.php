<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

include_once('database.inc.php');
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['FOOD_USER_ID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to save address.']);
    exit();
}

$uid = (int)$_SESSION['FOOD_USER_ID'];

// Get and sanitize input data
$address = mysqli_real_escape_string($con, $_POST['address'] ?? '');
$city = mysqli_real_escape_string($con, $_POST['city'] ?? '');
$pincode = mysqli_real_escape_string($con, $_POST['pincode'] ?? '');
$lat = $_POST['lat'] ?? '';
$lng = $_POST['lng'] ?? '';
$type = mysqli_real_escape_string($con, $_POST['type'] ?? 'Home');
$landmark = mysqli_real_escape_string($con, $_POST['landmark'] ?? '');
$flat_no = mysqli_real_escape_string($con, $_POST['flat_no'] ?? '');
$state = mysqli_real_escape_string($con, $_POST['state'] ?? '');

// Validate required fields
if (empty($address) || empty($city) || empty($pincode)) {
    echo json_encode(['status' => 'error', 'message' => 'Address, city and pincode are required.']);
    exit();
}

// Validate coordinates (they can be 0 but should be numeric)
if (!is_numeric($lat) || !is_numeric($lng)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid location coordinates.']);
    exit();
}

// Validate pincode (Indian pincode format - 6 digits)
if (!preg_match('/^\d{6}$/', $pincode)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 6-digit pincode.']);
    exit();
}

// Check if this is the first address (to set as default)
$checkFirst = mysqli_query($con, "SELECT COUNT(*) as total FROM user_addresses WHERE user_id='$uid'");
$row = mysqli_fetch_assoc($checkFirst);
$is_default = ($row['total'] == 0) ? 1 : 0;

// Prepare the SQL statement with all fields
$stmt = mysqli_prepare($con, 
    "INSERT INTO user_addresses 
    (user_id, address_type, address, city, pincode, state, landmark, flat_no, lat, lng, is_default, added_on) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    exit();
}

// Bind parameters
mysqli_stmt_bind_param($stmt, "isssssssddi", 
    $uid, 
    $type, 
    $address, 
    $city, 
    $pincode, 
    $state, 
    $landmark, 
    $flat_no, 
    $lat, 
    $lng, 
    $is_default
);

if(mysqli_stmt_execute($stmt)){
    $new_address_id = mysqli_insert_id($con);
    
    // Fetch the complete saved address for response
    $fetch_sql = "SELECT * FROM user_addresses WHERE id = ?";
    $fetch_stmt = mysqli_prepare($con, $fetch_sql);
    mysqli_stmt_bind_param($fetch_stmt, "i", $new_address_id);
    mysqli_stmt_execute($fetch_stmt);
    $result = mysqli_stmt_get_result($fetch_stmt);
    $saved_address = mysqli_fetch_assoc($result);
    mysqli_stmt_close($fetch_stmt);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Address saved successfully!',
        'address' => [
            'id' => $saved_address['id'],
            'address_type' => $saved_address['address_type'],
            'address' => $saved_address['address'],
            'city' => $saved_address['city'],
            'pincode' => $saved_address['pincode'],
            'state' => $saved_address['state'],
            'landmark' => $saved_address['landmark'],
            'flat_no' => $saved_address['flat_no'],
            'lat' => $saved_address['lat'],
            'lng' => $saved_address['lng'],
            'is_default' => $saved_address['is_default']
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to save address: ' . mysqli_stmt_error($stmt)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($con);
?>