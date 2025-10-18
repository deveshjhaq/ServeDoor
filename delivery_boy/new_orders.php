<?php
include_once('../database.inc.php');
include_once('../function.inc.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['DELIVERY_BOY_USER_LOGIN'])) {
    redirect('login.php');
}

$delivery_boy_id = $_SESSION['DELIVERY_BOY_ID'];
$page_title = 'New Orders';

// Get New Order Count for the navigation badge
$sql_new_count = "SELECT COUNT(id) as total_new FROM order_master WHERE delivery_boy_id = ? AND order_status = 6";
$stmt_new_count = mysqli_prepare($con, $sql_new_count);
mysqli_stmt_bind_param($stmt_new_count, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_new_count);
$res_new_count = mysqli_stmt_get_result($stmt_new_count);
$row_new_count = mysqli_fetch_assoc($res_new_count);
$total_new_orders = $row_new_count['total_new'] ?? 0;

// Fetch new orders assigned to the delivery boy (status 6: Waiting for Acceptance)
$sql = "SELECT om.*, r.name as restaurant_name, r.address as restaurant_address, r.lat as restaurant_lat, r.lng as restaurant_lng
        FROM order_master om
        JOIN restaurants r ON om.restaurant_id = r.id
        WHERE om.delivery_boy_id = ? AND om.order_status = 6
        ORDER BY om.id DESC";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>New Orders</title>
    <link rel="stylesheet" href="../admin/assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../admin/assets/css/style.css">
</head>
<body class="sidebar-light">
<div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="navbar-menu-wrapper d-flex align-items-stretch justify-content-between">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                <a class="navbar-brand brand-logo" href="index.php"><img src="../admin/assets/images/logo.png" alt="logo"/></a>
            </div>
            <ul class="navbar-nav navbar-nav-right">
                <li class="nav-item">
                    <span class="nav-link">Welcome, <b><?php echo htmlspecialchars($_SESSION['DELIVERY_BOY_USER']); ?></b></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel" style="width:100%;">
            <div class="content-wrapper">
                <div class="row page-title-header">
                    <div class="col-12">
                        <div class="page-header">
                            <h4 class="page-title">New Orders</h4>
                            <div class="quick-link-wrapper w-100 d-md-flex flex-md-wrap">
                                <ul class="quick-links ml-auto">
                                    <li><a href="index.php">Dashboard</a></li>
                                    <li>
                                        <a href="new_orders.php" class="font-weight-bold">
                                            New Orders
                                            <?php if($total_new_orders > 0): ?>
                                                <span class="badge badge-danger ml-1"><?php echo $total_new_orders; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <li><a href="history.php">Order History</a></li>
                                    <li><a href="wallet.php">My Wallet</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="new-orders-list">
                    <?php if (mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                    ?>
                    <div class="card grid-margin" id="order-card-<?php echo $row['id']; ?>">
                        <div class="card-body">
                            <h4 class="card-title">Order #<?php echo $row['id']; ?></h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Pickup From (Restaurant)</h5>
                                    <p class="mb-0"><b><?php echo htmlspecialchars($row['restaurant_name']); ?></b></p>
                                    <p><?php echo htmlspecialchars($row['restaurant_address']); ?></p>
                                    
                                    <div class="mt-2 text-primary distance-info" 
                                         data-rlat="<?php echo $row['restaurant_lat']; ?>" 
                                         data-rlng="<?php echo $row['restaurant_lng']; ?>">
                                        <b><i class="mdi mdi-map-marker-distance"></i> Calculating distance...</b>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Deliver To (Customer)</h5>
                                    <p class="mb-0"><b><?php echo htmlspecialchars($row['name']); ?></b></p>
                                    <p><?php echo htmlspecialchars($row['address']); ?>, <?php echo htmlspecialchars($row['zipcode']); ?></p>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <button type="button" class="btn btn-success" onclick="handleOrder(<?php echo $row['id']; ?>, 'accept')">Accept Order</button>
                                <button type="button" class="btn btn-danger" onclick="handleOrder(<?php echo $row['id']; ?>, 'reject')">Reject Order</button>
                            </div>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                        echo "<div class='alert alert-info'>No new orders assigned to you at the moment.</div>";
                    } ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_NEW_SECRET_API_KEY&libraries=places"></script>
<script>
let userLocation = null;

document.addEventListener("DOMContentLoaded", function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            calculateAllDistances();
        }, function() {
            console.log("Geolocation permission denied.");
            document.querySelectorAll('.distance-info').forEach(el => {
                el.innerHTML = `<b><i class="mdi mdi-map-marker-distance"></i> Location permission needed to calculate distance.</b>`;
            });
        });
    }
});

function calculateAllDistances() {
    if (!userLocation) return;
    const distanceElements = document.querySelectorAll('.distance-info');
    const service = new google.maps.DistanceMatrixService();
    distanceElements.forEach(el => {
        const restaurantLat = parseFloat(el.getAttribute('data-rlat'));
        const restaurantLng = parseFloat(el.getAttribute('data-rlng'));
        if (isNaN(restaurantLat) || isNaN(restaurantLng)) return;
        const restaurantPosition = { lat: restaurantLat, lng: restaurantLng };
        service.getDistanceMatrix({
            origins: [userLocation],
            destinations: [restaurantPosition],
            travelMode: 'DRIVING',
        }, (response, status) => {
            if (status === 'OK' && response.rows[0].elements[0].status === 'OK') {
                const distance = response.rows[0].elements[0].distance.text;
                el.innerHTML = `<b><i class="mdi mdi-map-marker-distance"></i> Restaurant is ${distance} away.</b>`;
            } else {
                el.innerHTML = `<b><i class="mdi mdi-map-marker-distance"></i> Could not calculate distance.</b>`;
            }
        });
    });
}

function handleOrder(orderId, action) {
    let confirmation = confirm(`Are you sure you want to ${action} this order?`);
    if (!confirmation) return;

    fetch('handle_order_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const card = document.getElementById('order-card-' + orderId);
            card.innerHTML = `<div class="card-body text-center"><p class="text-${action === 'accept' ? 'success' : 'danger'}">${data.message}</p></div>`;
            setTimeout(() => card.style.display = 'none', 2500);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
</body>
</html>