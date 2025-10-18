<?php
// Start session conditionally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include core files
include_once('../database.inc.php');
include_once('../function.inc.php');
include_once('../constant.inc.php');

/* ---------- Resolve current path (query-safe) ---------- */
$reqUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$pathOnly = parse_url($reqUri, PHP_URL_PATH);
$cur_path = $pathOnly ? basename($pathOnly) : '';

/* ---------- Auth Guard ---------- */
if (!isset($_SESSION['IS_LOGIN'])) {
    redirect('login.php');
}

// --- [SECURE] Get count of pending payouts for the navigation badge ---
$stmt_payout = mysqli_prepare($con, "SELECT COUNT(id) as total_pending FROM delivery_payouts WHERE status='pending'");
mysqli_stmt_execute($stmt_payout);
$payout_count_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_payout));
$pending_payouts = $payout_count_row['total_pending'] ?? 0;

// --- [NEW & SECURE] Get count of pending restaurants for the navigation badge ---
$stmt_rest_count = mysqli_prepare($con, "SELECT COUNT(id) as total_pending FROM restaurants WHERE status=0");
mysqli_stmt_execute($stmt_rest_count);
$rest_count_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_rest_count));
$pending_restaurants = $rest_count_row['total_pending'] ?? 0;


/* ---------- Page Title Logic ---------- */
$page_title = '';
if ($cur_path == '' || $cur_path == 'index.php') { $page_title = 'Dashboard'; } 
elseif ($cur_path == 'category.php' || $cur_path == 'manage_category.php') { $page_title = 'Manage Category'; } 
elseif ($cur_path == 'user.php' || $cur_path == 'manage_user.php') { $page_title = 'Manage User'; } 
elseif ($cur_path == 'delivery_boy.php' || $cur_path == 'manage_delivery_boy.php') { $page_title = 'Manage Delivery Boy'; } 
elseif ($cur_path == 'payouts.php') { $page_title = 'Payout Requests'; } 
elseif ($cur_path == 'coupon_code.php' || $cur_path == 'manage_coupon_code.php') { $page_title = 'Manage Coupon Code'; } 
elseif ($cur_path == 'dish.php' || $cur_path == 'manage_dish.php') { $page_title = 'Manage Dish'; } 
elseif ($cur_path == 'banner.php' || $cur_path == 'manage_banner.php') { $page_title = 'Manage Banner'; } 
elseif ($cur_path == 'contact_us.php') { $page_title = 'Contact Us'; } 
elseif ($cur_path == 'order.php' || $cur_path == 'order_master.php' || $cur_path == 'order_detail.php') { $page_title = 'Order Master'; } 
elseif ($cur_path == 'setting.php') { $page_title = 'Setting'; } 
elseif ($cur_path == 'restaurant.php' || $cur_path == 'manage_restaurant.php') { $page_title = 'Manage Restaurants'; } 
elseif ($cur_path == 'restaurant_owner.php' || $cur_path == 'manage_restaurant_owner.php') { $page_title = 'Manage Restaurant Owners'; }
elseif ($cur_path == 'pending_restaurants.php') { $page_title = 'Pending Approvals'; } // [NEW]

/* ---------- Active menu helper ---------- */
function is_active($files) {
    global $cur_path;
    if (!is_array($files)) {
        $files = [$files];
    }
    return in_array($cur_path, $files) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="sidebar-light">
<div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="navbar-menu-wrapper d-flex align-items-stretch justify-content-between">
            <ul class="navbar-nav mr-lg-2 d-none d-lg-flex">
                <li class="nav-item nav-toggler-item">
                    <button class="navbar-toggler align-self-center" type="button" data-toggle="minimize"><span class="mdi mdi-menu"></span></button>
                </li>
            </ul>
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                <a class="navbar-brand brand-logo" href="index.php"><img src="assets/images/logo.png" alt="logo"/></a>
                <a class="navbar-brand brand-logo-mini" href="index.php"><img src="assets/images/logo.png" alt="logo"/></a>
            </div>
            <ul class="navbar-nav navbar-nav-right">
                <li class="nav-item nav-profile dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" id="profileDropdown">
                        <span class="nav-profile-name"><?php echo isset($_SESSION['ADMIN_USER']) ? htmlspecialchars($_SESSION['ADMIN_USER']) : 'Admin'; ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                        <a class="dropdown-item" href="logout.php"><i class="mdi mdi-logout text-primary"></i> Logout</a>
                    </div>
                </li>
                <li class="nav-item nav-toggler-item-right d-lg-none">
                    <button class="navbar-toggler align-self-center" type="button" data-toggle="offcanvas"><span class="mdi mdi-menu"></span></button>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container-fluid page-body-wrapper">
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
            <ul class="nav">
                <li class="nav-item <?php echo is_active(['index.php']); ?>"><a class="nav-link" href="index.php"><i class="mdi mdi-view-quilt menu-icon"></i><span class="menu-title">Dashboard</span></a></li>
                <li class="nav-item <?php echo is_active(['order.php', 'order_master.php', 'order_detail.php']); ?>"><a class="nav-link" href="order.php"><i class="mdi mdi-cart-outline menu-icon"></i><span class="menu-title">Orders</span></a></li>
                <li class="nav-item <?php echo is_active(['user.php', 'manage_user.php']); ?>"><a class="nav-link" href="user.php"><i class="mdi mdi-account-multiple-outline menu-icon"></i><span class="menu-title">Users</span></a></li>
                <li class="nav-item <?php echo is_active(['restaurant.php', 'manage_restaurant.php']); ?>"><a class="nav-link" href="restaurant.php"><i class="mdi mdi-store menu-icon"></i><span class="menu-title">Restaurants</span></a></li>
                
                <li class="nav-item <?php echo is_active(['pending_restaurants.php']); ?>">
                  <a class="nav-link" href="pending_restaurants.php">
                    <i class="mdi mdi-store-off menu-icon"></i>
                    <span class="menu-title">Pending Approvals</span>
                    <?php if($pending_restaurants > 0): ?>
                      <span class="badge badge-warning ml-auto"><?php echo $pending_restaurants; ?></span>
                    <?php endif; ?>
                  </a>
                </li>

                <li class="nav-item <?php echo is_active(['restaurant_owner.php', 'manage_restaurant_owner.php']); ?>"><a class="nav-link" href="restaurant_owner.php"><i class="mdi mdi-account-key menu-icon"></i><span class="menu-title">Restaurant Owners</span></a></li>
                <li class="nav-item <?php echo is_active(['delivery_boy.php', 'manage_delivery_boy.php']); ?>"><a class="nav-link" href="delivery_boy.php"><i class="mdi mdi-motorbike menu-icon"></i><span class="menu-title">Delivery Boys</span></a></li>
                <li class="nav-item <?php echo is_active(['payouts.php']); ?>">
                  <a class="nav-link" href="payouts.php">
                    <i class="mdi mdi-cash-multiple menu-icon"></i><span class="menu-title">Payout Requests</span>
                    <?php if($pending_payouts > 0): ?><span class="badge badge-danger ml-auto"><?php echo $pending_payouts; ?></span><?php endif; ?>
                  </a>
                </li>
                <li class="nav-item <?php echo is_active(['category.php', 'manage_category.php']); ?>"><a class="nav-link" href="category.php"><i class="mdi mdi-format-list-bulleted menu-icon"></i><span class="menu-title">Categories</span></a></li>
                <li class="nav-item <?php echo is_active(['dish.php', 'manage_dish.php']); ?>"><a class="nav-link" href="dish.php"><i class="mdi mdi-food-variant menu-icon"></i><span class="menu-title">Dishes</span></a></li>
                <li class="nav-item <?php echo is_active(['banner.php', 'manage_banner.php']); ?>"><a class="nav-link" href="banner.php"><i class="mdi mdi-image-area menu-icon"></i><span class="menu-title">Banners</span></a></li>
                <li class="nav-item <?php echo is_active(['coupon_code.php', 'manage_coupon_code.php']); ?>"><a class="nav-link" href="coupon_code.php"><i class="mdi mdi-ticket-percent menu-icon"></i><span class="menu-title">Coupons</span></a></li>
                <li class="nav-item <?php echo is_active(['contact_us.php']); ?>"><a class="nav-link" href="contact_us.php"><i class="mdi mdi-email-outline menu-icon"></i><span class="menu-title">Contact Us</span></a></li>
                <li class="nav-item <?php echo is_active(['error_log.php']); ?>"><a class="nav-link" href="error_log.php"><i class="mdi mdi-bug menu-icon"></i><span class="menu-title">Error Log</span></a></li>
                <li class="nav-item <?php echo is_active(['setting.php']); ?>"><a class="nav-link" href="setting.php"><i class="mdi mdi-settings menu-icon"></i><span class="menu-title">Settings</span></a></li>
            </ul>
        </nav>
        <div class="main-panel">
            <div class="content-wrapper">