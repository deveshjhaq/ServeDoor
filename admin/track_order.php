<?php
include('top.php');

$order_id = get_safe_value($_GET['id']);

// Fetch order details
$sql = "SELECT om.*, r.name as restaurant_name, r.lat as pickup_lat, r.lng as pickup_lng
        FROM order_master om
        JOIN restaurants r ON om.restaurant_id = r.id
        WHERE om.id = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($res) == 0){
    redirect('order.php');
}
$order_details = mysqli_fetch_assoc($res);
$delivery_boy_id = $order_details['delivery_boy_id'];

$js_data = [
    'pickup_lat' => $order_details['pickup_lat'],
    'pickup_lng' => $order_details['pickup_lng'],
    'dropoff_lat' => $order_details['dropoff_lat'],
    'dropoff_lng' => $order_details['dropoff_lng'],
    'delivery_boy_id' => $delivery_boy_id
];
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Live Tracking for Order #<?php echo $order_id; ?></h4>
                <div id="map" style="height: 600px; width: 100%; background-color: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-align: center; color: #666;">
                    Loading Map...
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Live Status</h4>
                <p><strong>Delivery Boy Status:</strong> <span id="db_status" class="badge">...</span></p>
                <p><strong>Distance from Restaurant:</strong> <span id="dist_from_rest">Calculating...</span></p>
                <p><strong>Distance to Customer:</strong> <span id="dist_to_cust">Calculating...</span></p>
                <p><strong>Estimated Time of Arrival:</strong> <span id="eta">Calculating...</span></p>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <h4 class="card-title">Order Details</h4>
                <p><strong>Restaurant:</strong> <?php echo htmlspecialchars($order_details['restaurant_name']); ?></p>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order_details['name']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($order_details['address']); ?></p>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC2gYIa_zyioA1bhG9_1RKw3iuJ309aw1w&callback=initMap" async defer></script>
<script>
const orderData = <?php echo json_encode($js_data); ?>;
let map, boyMarker;

function initMap() {
    if (!orderData.pickup_lat || !orderData.pickup_lng || !orderData.dropoff_lat || !orderData.dropoff_lng) {
        mapErrorHandler("Error: Missing coordinates. Please check if the restaurant and customer addresses are correct in the database.");
        return;
    }
    
    const pickupLocation = { lat: parseFloat(orderData.pickup_lat), lng: parseFloat(orderData.pickup_lng) };
    const dropoffLocation = { lat: parseFloat(orderData.dropoff_lat), lng: parseFloat(orderData.dropoff_lng) };

    map = new google.maps.Map(document.getElementById("map"), { center: pickupLocation, zoom: 14 });
    new google.maps.Marker({ position: pickupLocation, map: map, label: "R", title: "Restaurant" });
    new google.maps.Marker({ position: dropoffLocation, map: map, label: "C", title: "Customer" });
    boyMarker = new google.maps.Marker({ map: map, icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png', title: "Delivery Boy" });

    updateBoyLocation();
    setInterval(updateBoyLocation, 10000);
}

function mapErrorHandler(message) {
    document.getElementById('map').innerHTML = `<div style="padding: 20px;"><h5 style="color: red;">Could not load map</h5><p>${message}</p></div>`;
}

window.gm_authFailure = function() {
    mapErrorHandler("Authentication failed. Please check your API Key and ensure billing is enabled on your Google Cloud account.");
};

function updateBoyLocation() {
    if (!orderData.delivery_boy_id) return;
    fetch(`get_delivery_boy_status.php?id=${orderData.delivery_boy_id}`)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.lat && data.lng) {
            const boyLocation = { lat: parseFloat(data.lat), lng: parseFloat(data.lng) };
            boyMarker.setPosition(boyLocation);

            const statusBadge = document.getElementById('db_status');
            statusBadge.innerText = data.online_status;
            statusBadge.className = `badge ${data.online_status === 'Online' ? 'badge-success' : 'badge-danger'}`;

            calculateDistances(boyLocation, { lat: parseFloat(orderData.pickup_lat), lng: parseFloat(orderData.pickup_lng) }, { lat: parseFloat(orderData.dropoff_lat), lng: parseFloat(orderData.dropoff_lng) });
        }
    });
}

function calculateDistances(boyLocation, pickup, dropoff) {
    const service = new google.maps.DistanceMatrixService();
    service.getDistanceMatrix({
        origins: [boyLocation],
        destinations: [pickup, dropoff],
        travelMode: 'DRIVING',
    }, (response, status) => {
        if (status === 'OK') {
            const results = response.rows[0].elements;
            if (results[0].status === 'OK') document.getElementById('dist_from_rest').innerText = results[0].distance.text;
            if (results[1].status === 'OK') {
                document.getElementById('dist_to_cust').innerText = results[1].distance.text;
                document.getElementById('eta').innerText = results[1].duration.text;
            }
        }
    });
}
</script>