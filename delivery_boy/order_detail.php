<?php
include_once('../database.inc.php');
include_once('../function.inc.php');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['DELIVERY_BOY_USER_LOGIN'])) { redirect('login.php'); }

$delivery_boy_id = $_SESSION['DELIVERY_BOY_ID'];
$page_title = 'Order Details';

$order_id = get_safe_value($_GET['id']);

// Fetch order details, including restaurant and customer coordinates
$sql = "SELECT om.*, r.name as restaurant_name, r.address as restaurant_address, r.lat as pickup_lat, r.lng as pickup_lng
        FROM order_master om
        JOIN restaurants r ON om.restaurant_id = r.id
        WHERE om.id = ? AND om.delivery_boy_id = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $delivery_boy_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($res) == 0){
    // If order not found or not assigned to this boy, redirect
    redirect('index.php');
}
$order_details = mysqli_fetch_assoc($res);

// Get New Order Count for badge
$total_new_orders = 0; // You can add the count logic here if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Order Details #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="../admin/assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../admin/assets/css/style.css">
    <style> #map { height: 400px; width: 100%; border-radius: 8px; } </style>
</head>
<body class="sidebar-light">
<div class="container-scroller">
    <?php include_once('navbar.php'); ?>
    
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel" style="width:100%;">
            <div class="content-wrapper">
                <?php include_once('sub_nav.php'); ?>

                <div class="row">
                    <div class="col-lg-8 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Live Delivery Map</h4>
                                <div id="map"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Order #<?php echo htmlspecialchars($order_details['id']); ?></h4>
                                
                                <div class="mt-4">
                                    <h5><i class="mdi mdi-store text-primary"></i> Pickup Location</h5>
                                    <p class="mb-0"><b><?php echo htmlspecialchars($order_details['restaurant_name']); ?></b></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($order_details['restaurant_address']); ?></p>
                                </div>
                                <hr>
                                <div class="mt-4">
                                    <h5><i class="mdi mdi-account-location text-success"></i> Drop-off Location</h5>
                                    <p class="mb-0"><b><?php echo htmlspecialchars($order_details['name']); ?></b></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($order_details['address']); ?></p>
                                    <a href="tel:<?php echo htmlspecialchars($order_details['mobile']); ?>" class="btn btn-outline-primary btn-sm">Call Customer</a>
                                </div>
                                <hr>
                                <div class="mt-4">
                                    <a id="navigateBtn" href="#" target="_blank" class="btn btn-success btn-lg btn-block">
                                        <i class="mdi mdi-navigation"></i> Start Navigation
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC2gYIa_zyioA1bhG9_1RKw3iuJ309aw1w&libraries=directions"></script>
<script>
let map;
let deliveryBoyMarker;
const pickupLocation = { lat: <?php echo $order_details['pickup_lat']; ?>, lng: <?php echo $order_details['pickup_lng']; ?> };
const dropoffLocation = { lat: <?php echo $order_details['dropoff_lat']; ?>, lng: <?php echo $order_details['dropoff_lng']; ?> };

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: pickupLocation,
        zoom: 14,
    });

    const directionsService = new google.maps.DirectionsService();
    const directionsRenderer = new google.maps.DirectionsRenderer({ suppressMarkers: true });
    directionsRenderer.setMap(map);

    // Markers for pickup and dropoff
    new google.maps.Marker({ position: pickupLocation, map: map, label: "R" });
    new google.maps.Marker({ position: dropoffLocation, map: map, label: "C" });

    // Marker for the delivery boy
    deliveryBoyMarker = new google.maps.Marker({
        map: map,
        icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png' // A simple blue dot for the boy
    });

    // Watch the delivery boy's live location
    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            (position) => {
                const boyLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                
                // Update marker position
                deliveryBoyMarker.setPosition(boyLocation);
                
                // Draw route from boy to pickup to dropoff
                drawRoute(directionsService, directionsRenderer, boyLocation, pickupLocation, dropoffLocation);

                // Update navigation button link
                const navUrl = `https://www.google.com/maps/dir/?api=1&origin=${boyLocation.lat},${boyLocation.lng}&destination=${dropoffLocation.lat},${dropoffLocation.lng}&waypoints=${pickupLocation.lat},${pickupLocation.lng}&travelmode=driving`;
                document.getElementById('navigateBtn').href = navUrl;
            },
            () => { console.log("Error: The Geolocation service failed."); }
        );
    } else {
        console.log("Browser doesn't support Geolocation.");
    }
}

function drawRoute(service, renderer, origin, waypoint, destination) {
    service.route({
        origin: origin,
        destination: destination,
        waypoints: [{ location: waypoint, stopover: true }],
        travelMode: google.maps.TravelMode.DRIVING,
    }, (result, status) => {
        if (status === "OK") {
            renderer.setDirections(result);
        } else {
            console.error(`Directions request failed due to ${status}`);
        }
    });
}

// Initialize the map when the window loads
window.onload = initMap;
</script>
</body>
</html>