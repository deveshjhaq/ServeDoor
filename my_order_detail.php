<?php
include("header.php");
if(!isset($_SESSION['FOOD_USER_ID'])){ redirect(FRONT_SITE_PATH.'shop'); }

$order_id = get_safe_value($_GET['id']);

// [MODIFIED] Fetch comprehensive order details including delivery boy's name AND MOBILE
$sql_order = "SELECT om.*, r.name as restaurant_name, r.address as restaurant_address, 
              r.phone as restaurant_phone, r.lat as pickup_lat, r.lng as pickup_lng,
              db.name as delivery_boy_name, db.mobile as delivery_boy_mobile
              FROM order_master om
              JOIN restaurants r ON om.restaurant_id = r.id
              LEFT JOIN delivery_boy db ON om.delivery_boy_id = db.id
              WHERE om.id=? AND om.user_id=?";
$stmt_order = mysqli_prepare($con, $sql_order);
mysqli_stmt_bind_param($stmt_order, "ii", $order_id, $_SESSION['FOOD_USER_ID']);
mysqli_stmt_execute($stmt_order);
$res_order = mysqli_stmt_get_result($stmt_order);
if(mysqli_num_rows($res_order) == 0){ redirect(FRONT_SITE_PATH.'my_order'); }
$order_details = mysqli_fetch_assoc($res_order);

// Fetch order items (Corrected JOIN)
$sql_items = "SELECT od.qty, od.price, d.dish as dish_name, d.image as dish_image, dd.attribute
              FROM order_detail od
              JOIN dish_details dd ON od.dish_details_id = dd.id
              JOIN dish d ON dd.dish_id = d.id
              WHERE od.order_id = ?";
$stmt_items = mysqli_prepare($con, $sql_items);
mysqli_stmt_bind_param($stmt_items, "i", $order_id);
mysqli_stmt_execute($stmt_items);
$res_items = mysqli_stmt_get_result($stmt_items);

// Fetch order status history to show timestamps
$sql_history = "SELECT new_status, added_on FROM order_status_history WHERE order_id = ? ORDER BY added_on ASC";
$stmt_history = mysqli_prepare($con, $sql_history);
mysqli_stmt_bind_param($stmt_history, "i", $order_id);
mysqli_stmt_execute($stmt_history);
$res_history = mysqli_stmt_get_result($stmt_history);
$history_timestamps = [];
while($row_history = mysqli_fetch_assoc($res_history)){
    $history_timestamps[$row_history['new_status']] = $row_history['added_on'];
}

$status_map = [
    1 => ['text' => 'Order Placed', 'icon' => 'checklist'],
    6 => ['text' => 'Order Confirmed', 'icon' => 'thumb_up'],
    2 => ['text' => 'Preparing Food', 'icon' => 'soup_kitchen'],
    3 => ['text' => 'On the Way', 'icon' => 'electric_moped'],
    4 => ['text' => 'Delivered', 'icon' => 'verified'],
    5 => ['text' => 'Cancelled', 'icon' => 'cancel']
];
$current_status_info = $status_map[$order_details['order_status']] ?? $status_map[1];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --primary-color: #ea2a33; }
        /* [REMOVED] Dark mode specific variables are removed as it's handled globally */
    </style>
    </head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 font-sans">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Order from <span class="font-bold text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($order_details['restaurant_name']); ?></span></p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order #<?php echo htmlspecialchars($order_id); ?></h1>
                </div>
                <div class="text-2xl font-bold text-green-600">₹<?php echo number_format($order_details['final_price'], 2); ?></div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                 <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-green-500"><?php echo $current_status_info['icon']; ?></span>
                    <span class="font-semibold text-green-600"><?php echo $current_status_info['text']; ?></span>
                </div>
                <a href="<?php echo FRONT_SITE_PATH.'my_order.php'; ?>" class="text-sm font-medium text-red-500 hover:underline">View All Orders</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                    <div id="map" class="h-80 w-full"></div>
                </div>
                <?php if($order_details['order_status'] >= 3 && isset($order_details['delivery_boy_name'])): ?>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Your Delivery Partner</p>
                        <p class="font-bold text-lg text-gray-900 dark:text-white"><?php echo htmlspecialchars($order_details['delivery_boy_name']); ?></p>
                    </div>
                    <div class="flex gap-2">
                         <a href="tel:<?php echo htmlspecialchars($order_details['delivery_boy_mobile']); ?>" class="h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-red-500"><span class="material-symbols-outlined">call</span></a>
                         <a href="#" class="h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-red-500"><span class="material-symbols-outlined">chat</span></a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-1 space-y-6">
                 <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Order Timeline</h3>
                    <div class="relative">
                        <div class="absolute left-4 top-4 h-full border-l-2 border-gray-200 dark:border-gray-700"></div>
                        <div class="space-y-6">
                            <?php foreach($status_map as $status_id => $status_info): 
                                if ($status_id > 4) continue;
                                $is_completed = $order_details['order_status'] >= $status_id;
                                $timestamp = $history_timestamps[$status_id] ?? ($status_id == 1 ? $order_details['added_on'] : null);
                            ?>
                            <div class="flex gap-4 items-start relative z-10">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center <?php echo $is_completed ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'; ?>">
                                    <span class="material-symbols-outlined text-base"><?php echo $status_info['icon']; ?></span>
                                </div>
                                <div>
                                    <p class="font-semibold <?php echo $is_completed ? 'text-gray-800 dark:text-white' : 'text-gray-500 dark:text-gray-400'; ?>"><?php echo $status_info['text']; ?></p>
                                    <?php if($is_completed && $timestamp): ?>
                                    <p class="text-xs text-gray-400 dark:text-gray-500"><?php echo date('h:i A', strtotime($timestamp)); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                 </div>
                 <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Bill Details</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400"><span>Item Total</span><span>₹<?php echo number_format($order_details['total_price'], 2); ?></span></div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400"><span>Delivery Fee</span><span>₹<?php echo number_format($order_details['delivery_fee'], 2); ?></span></div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400"><span>GST + Platform Fee</span><span>₹<?php echo number_format($order_details['gst_amount'] + $order_details['platform_fee'], 2); ?></span></div>
                        <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-200 dark:border-gray-700 mt-2 text-gray-900 dark:text-white"><span>To Pay</span><span>₹<?php echo number_format($order_details['final_price'], 2); ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC2gYIa_zyioA1bhG9_1RKw3iuJ309aw1w&libraries=geometry,directions"></script>
<script>
    let map, boyMarker, directionsRenderer;
    let lastPosition = null;
    const orderId = <?php echo json_encode($order_id); ?>;

    function initMap() {
        const isDarkMode = document.documentElement.classList.contains('dark');
        
        map = new google.maps.Map(document.getElementById("map"), {
            center: { lat: <?php echo $order_details['pickup_lat']; ?>, lng: <?php echo $order_details['pickup_lng']; ?> },
            zoom: 14,
            // [CHANGED] Custom styles are removed to show the default Google Map
            styles: isDarkMode ? [ { "featureType": "all", "elementType": "geometry", "stylers": [ { "color": "#242f3e" } ] }, { "featureType": "all", "elementType": "labels.text.fill", "stylers": [ { "color": "#746855" } ] } /* Add full dark style here if needed */] : [],
            disableDefaultUI: true, 
            zoomControl: true
        });

        // The rest of your JavaScript for markers, animation, ETA etc. goes here
        // This part does not need changes for the map style itself
        
        const restaurantIcon = { url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#4CAF50"><path d="M16 6V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H2v13h18v-2h2V6h-6zm-6-2h4v2h-4V4zm10 15H4V8h16v11zM10 10v5h2v-5h-2z"/></svg>'), scaledSize: new google.maps.Size(40, 40) };
        const homeIcon = { url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#EA4335"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>'), scaledSize: new google.maps.Size(40, 40) };
        boyMarker = new google.maps.Marker({ map: map, title: "Delivery Partner" });
        
        updateMapData(restaurantIcon, homeIcon);
        setInterval(() => updateMapData(restaurantIcon, homeIcon), 10000);
    }
    
    // updateMapData, animateMarker, and calculateETA functions remain the same
    function updateMapData(restaurantIcon, homeIcon) {
        fetch(`get_tracking_data.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const pickupLocation = { lat: parseFloat(data.pickup.lat), lng: parseFloat(data.pickup.lng) };
                const dropoffLocation = { lat: parseFloat(data.dropoff.lat), lng: parseFloat(data.dropoff.lng) };
                if (!map.pickupMarker) {
                    map.pickupMarker = new google.maps.Marker({ position: pickupLocation, map: map, icon: restaurantIcon, title: "Restaurant" });
                    map.dropoffMarker = new google.maps.Marker({ position: dropoffLocation, map: map, icon: homeIcon, title: "Your Location" });
                }
                if (data.delivery_boy.lat && data.delivery_boy.lng) {
                    const newPosition = { lat: parseFloat(data.delivery_boy.lat), lng: parseFloat(data.delivery_boy.lng) };
                    if (!lastPosition) { lastPosition = newPosition; }
                    animateMarker(lastPosition, newPosition, 9000);
                    lastPosition = newPosition;
                    calculateETA(newPosition, dropoffLocation);
                }
            }
        });
    }

    function animateMarker(start, end, duration) {
        let startTime = performance.now();
        const isDarkMode = document.documentElement.classList.contains('dark');
        const bikeIcon = {
            path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
            fillColor: isDarkMode ? '#8ab4f8' : '#4285F4',
            fillOpacity: 1,
            strokeWeight: 2,
            strokeColor: isDarkMode ? '#2c3e50' : '#ffffff',
            rotation: 0,
            scale: 7,
            anchor: new google.maps.Point(0, 2.5)
        };
        const step = (currentTime) => {
            const elapsedTime = currentTime - startTime;
            const percentage = Math.min(elapsedTime / duration, 1);
            const lat = start.lat + (end.lat - start.lat) * percentage;
            const lng = start.lng + (end.lng - start.lng) * percentage;
            boyMarker.setPosition({ lat, lng });
            const heading = google.maps.geometry.spherical.computeHeading(new google.maps.LatLng(start.lat, start.lng), new google.maps.LatLng(end.lat, end.lng));
            bikeIcon.rotation = heading;
            boyMarker.setIcon(bikeIcon);
            if (percentage < 1) { requestAnimationFrame(step); }
        };
        requestAnimationFrame(step);
    }

    function calculateETA(origin, destination) {
        const service = new google.maps.DistanceMatrixService();
        service.getDistanceMatrix({
            origins: [origin],
            destinations: [destination],
            travelMode: 'DRIVING',
        }, (response, status) => {
            if (status === 'OK' && response.rows[0].elements[0].status === 'OK') {
                document.getElementById('eta').innerText = response.rows[0].elements[0].duration.text;
                document.getElementById('dist_to_you').innerText = response.rows[0].elements[0].distance.text;
            }
        });
    }

    window.onload = initMap;
</script>
</body>
</html>