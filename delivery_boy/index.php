<?php
// Use include_once for safety
include_once('../database.inc.php');
include_once('../function.inc.php');

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the delivery boy is logged in
if (!isset($_SESSION['DELIVERY_BOY_USER_LOGIN'])) {
    redirect('login.php');
}

$delivery_boy_id = $_SESSION['DELIVERY_BOY_ID'];
$success_msg = '';
$page_title = 'Dashboard';

// --- [SECURE] Handle marking order as delivered ---
if (isset($_GET['set_order_id'])) {
    $set_order_id = get_safe_value($_GET['set_order_id']);
    $delivered_on = date('Y-m-d H:i:s');

    // [UPDATED] This query now also updates payment_status to 'success' for COD orders
    $sql_update = "UPDATE order_master 
                   SET 
                       order_status = 4, 
                       delivered_on = ?,
                       payment_status = CASE 
                                           WHEN payment_type = 'cod' THEN 'success' 
                                           ELSE payment_status 
                                        END
                   WHERE id = ? AND delivery_boy_id = ?";
            
    $stmt_update = mysqli_prepare($con, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "sii", $delivered_on, $set_order_id, $delivery_boy_id);
    mysqli_stmt_execute($stmt_update);

    $success_msg = "Order #" . $set_order_id . " has been marked as delivered!";
}

// --- Get Delivery Boy's Online/Offline Status ---
$is_online = false;
$stmt_status = mysqli_prepare($con, "SELECT last_seen_at FROM delivery_boy WHERE id = ?");
mysqli_stmt_bind_param($stmt_status, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_status);
$res_status = mysqli_stmt_get_result($stmt_status);
if(mysqli_num_rows($res_status) > 0){
    $row_status = mysqli_fetch_assoc($res_status);
    $last_seen_at = strtotime($row_status['last_seen_at']);
    if((time() - $last_seen_at) < 180){ // 3 minutes threshold
        $is_online = true;
    }
}

// --- FILTER LOGIC ---
$filter = $_GET['filter'] ?? 'today';
$date_condition = "";
$filter_title = "Today";

switch ($filter) {
    case 'week':
        $date_condition = "AND YEARWEEK(delivered_on, 1) = YEARWEEK(CURDATE(), 1)";
        $filter_title = "This Week";
        break;
    case 'month':
        $date_condition = "AND MONTH(delivered_on) = MONTH(CURDATE()) AND YEAR(delivered_on) = YEAR(CURDATE())";
        $filter_title = "This Month";
        break;
    case 'all':
        $date_condition = "";
        $filter_title = "All Time";
        break;
    case 'today':
    default:
        $date_condition = "AND DATE(delivered_on) = CURDATE()";
        $filter_title = "Today";
        break;
}

// --- Get Filtered Analytics Data ---
function getStatValue($con, $sql, $params, $types) {
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}

$sql_earnings = "SELECT SUM(delivery_commission) as total FROM order_master WHERE delivery_boy_id = ? AND order_status=4 $date_condition";
$filtered_earnings = getStatValue($con, $sql_earnings, [$delivery_boy_id], "i");

$sql_orders = "SELECT COUNT(id) as total FROM order_master WHERE delivery_boy_id = ? AND order_status=4 $date_condition";
$filtered_orders = getStatValue($con, $sql_orders, [$delivery_boy_id], "i");

// Get New Order Count for badge
$sql_new_count = "SELECT COUNT(id) as total_new FROM order_master WHERE delivery_boy_id = ? AND order_status = 6";
$total_new_orders = getStatValue($con, $sql_new_count, [$delivery_boy_id], "i");

// Fetch ALL pending orders
$sql_all_pending = "SELECT om.*, os.order_status as order_status_str FROM order_master om JOIN order_status os ON om.order_status = os.id WHERE om.delivery_boy_id = ? AND om.order_status NOT IN (4, 6) ORDER BY om.id DESC";
$stmt_all_pending = mysqli_prepare($con, $sql_all_pending);
mysqli_stmt_bind_param($stmt_all_pending, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_all_pending);
$res_all_pending = mysqli_stmt_get_result($stmt_all_pending);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Delivery Boy Dashboard</title>
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
                    <span class="nav-link">
                        <?php if($is_online){ ?>
                            <div class="badge badge-success">● Online</div>
                        <?php } else { ?>
                            <div class="badge badge-danger">● Offline</div>
                        <?php } ?>
                    </span>
                </li>
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
                            <h4 class="page-title">Dashboard</h4>
                            <div class="quick-link-wrapper w-100 d-md-flex flex-md-wrap">
                                <ul class="quick-links ml-auto">
                                    <li><a href="index.php" class="font-weight-bold">Dashboard</a></li>
                                    <li>
                                        <a href="new_orders.php">
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

                <?php if ($success_msg != '') { ?>
                    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php } ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">My Performance</h4>
                        <div class="btn-group mb-4" role="group">
                            <a href="?filter=today" class="btn <?php echo ($filter == 'today') ? 'btn-primary' : 'btn-outline-primary'; ?>">Today</a>
                            <a href="?filter=week" class="btn <?php echo ($filter == 'week') ? 'btn-primary' : 'btn-outline-primary'; ?>">This Week</a>
                            <a href="?filter=month" class="btn <?php echo ($filter == 'month') ? 'btn-primary' : 'btn-outline-primary'; ?>">This Month</a>
                            <a href="?filter=all" class="btn <?php echo ($filter == 'all') ? 'btn-primary' : 'btn-outline-primary'; ?>">All Time</a>
                        </div>
                        <div class="row">
                            <div class="col-md-6 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <p class="card-title">Earnings (<?php echo $filter_title; ?>)</p>
                                        <div class="d-flex align-items-center"><h3 class="mb-0">₹<?php echo number_format($filtered_earnings, 2); ?></h3><i class="mdi mdi-cash-multiple icon-lg text-success ml-auto"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <p class="card-title">Orders Delivered (<?php echo $filter_title; ?>)</p>
                                        <div class="d-flex align-items-center"><h3 class="mb-0"><?php echo $filtered_orders; ?></h3><i class="mdi mdi-checkbox-marked-circle-outline icon-lg text-primary ml-auto"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">My Pending Orders</h4>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer Details</th>
                                        <th>Address</th>
                                        <th>Order Status</th>
                                        <th>Added On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (mysqli_num_rows($res_all_pending) > 0) {
                                    while ($row = mysqli_fetch_assoc($res_all_pending)) {
                                ?>
                                    <tr>
                                        <td><a href="order_detail.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="font-weight-bold"><?php echo htmlspecialchars($row['id']); ?></a></td>
                                        <td><p><?php echo htmlspecialchars($row['name']); ?></p><p class="text-muted"><?php echo htmlspecialchars($row['mobile']); ?></p></td>
                                        <td><p><?php echo htmlspecialchars($row['address']); ?></p><p class="text-muted"><?php echo htmlspecialchars($row['zipcode']); ?></p></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($row['order_status_str']); ?></span></td>
                                        <td><?php echo date('d-m-Y h:i A', strtotime($row['added_on'])); ?></td>
                                        <td>
                                            <a href="?set_order_id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to mark Order #<?php echo htmlspecialchars($row['id']); ?> as delivered?')">
                                               <i class="mdi mdi-check"></i> Delivered
                                            </a>
                                        </td>
                                    </tr>
                                <?php } } else { ?>
                                    <tr><td colspan="6" class="text-center">No pending orders found. Great job!</td></tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(sendLocationToServer, showError, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    }
});
function sendLocationToServer(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const deliveryBoyId = <?php echo json_encode($_SESSION['DELIVERY_BOY_ID']); ?>;
    fetch('update_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
        body: `lat=${lat}&lng=${lng}&delivery_boy_id=${deliveryBoyId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Location updated successfully:', data);
        <?php if(!$is_online){ ?>
            window.location.reload();
        <?php } ?>
    })
    .catch((error) => console.error('Error updating location:', error));
}
function showError(error) { console.warn(`Geolocation Error(${error.code}): ${error.message}`); }
</script>
</body>
</html>