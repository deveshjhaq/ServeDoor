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
$page_title = 'Order History'; // Page title for navigation

// Get New Order Count for the navigation badge
$sql_new_count = "SELECT COUNT(id) as total_new FROM order_master WHERE delivery_boy_id = ? AND order_status = 6";
$stmt_new_count = mysqli_prepare($con, $sql_new_count);
mysqli_stmt_bind_param($stmt_new_count, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_new_count);
$res_new_count = mysqli_stmt_get_result($stmt_new_count);
$row_new_count = mysqli_fetch_assoc($res_new_count);
$total_new_orders = $row_new_count['total_new'] ?? 0;

// --- [SECURE] Fetch ALL completed orders for the delivery boy ---
$sql_completed = "SELECT om.*, os.order_status as order_status_str 
                  FROM order_master om 
                  JOIN order_status os ON om.order_status = os.id 
                  WHERE om.delivery_boy_id = ? AND om.order_status = 4 
                  ORDER BY om.delivered_on DESC";
$stmt_completed = mysqli_prepare($con, $sql_completed);
mysqli_stmt_bind_param($stmt_completed, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_completed);
$res_completed = mysqli_stmt_get_result($stmt_completed);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Order History</title>
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
                            <h4 class="page-title">Order History</h4>
                            <div class="quick-link-wrapper w-100 d-md-flex flex-md-wrap">
                                <ul class="quick-links ml-auto">
                                    <li><a href="index.php">Dashboard</a></li>
                                    <li>
                                        <a href="new_orders.php">
                                            New Orders
                                            <?php if($total_new_orders > 0): ?>
                                                <span class="badge badge-danger ml-1"><?php echo $total_new_orders; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <li><a href="history.php" class="font-weight-bold">Order History</a></li>
                                    <li><a href="wallet.php">My Wallet</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">My Completed Orders</h4>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer Details</th>
                                        <th>Address</th>
                                        <th>Delivered On</th>
                                        <th>Your Earning</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (mysqli_num_rows($res_completed) > 0) {
                                    while ($row = mysqli_fetch_assoc($res_completed)) {
                                ?>
                                    <tr>
                                        <td><b><?php echo htmlspecialchars($row['id']); ?></b></td>
                                        <td>
                                            <p><?php echo htmlspecialchars($row['name']); ?></p>
                                            <p class="text-muted"><?php echo htmlspecialchars($row['mobile']); ?></p>
                                        </td>
                                        <td>
                                            <p><?php echo htmlspecialchars($row['address']); ?></p>
                                            <p class="text-muted"><?php echo htmlspecialchars($row['zipcode']); ?></p>
                                        </td>
                                        <td><?php echo date('d M, Y h:i A', strtotime($row['delivered_on'])); ?></td>
                                        <td>
                                            <b class="text-success">₹<?php echo htmlspecialchars(number_format($row['delivery_commission'], 2)); ?></b>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                } else { ?>
                                    <tr>
                                        <td colspan="5" class="text-center">You have not delivered any orders yet.</td>
                                    </tr>
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
</body>
</html>