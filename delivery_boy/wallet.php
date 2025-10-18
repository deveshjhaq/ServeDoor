<?php
// Use include_once for safety
include_once('../database.inc.php');
include_once('../function.inc.php');

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['DELIVERY_BOY_USER_LOGIN'])) {
    redirect('login.php');
}

$delivery_boy_id = $_SESSION['DELIVERY_BOY_ID'];
$page_title = 'My Wallet';

// Get New Order Count for the navigation badge
$sql_new_count = "SELECT COUNT(id) as total_new FROM order_master WHERE delivery_boy_id = ? AND order_status = 6";
$stmt_new_count = mysqli_prepare($con, $sql_new_count);
mysqli_stmt_bind_param($stmt_new_count, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_new_count);
$res_new_count = mysqli_stmt_get_result($stmt_new_count);
$row_new_count = mysqli_fetch_assoc($res_new_count);
$total_new_orders = $row_new_count['total_new'] ?? 0;

// --- Get Total Lifetime Earnings ---
$sql_earned = "SELECT SUM(delivery_commission) as total_earned FROM order_master WHERE delivery_boy_id = ? AND order_status = 4";
$stmt_earned = mysqli_prepare($con, $sql_earned);
mysqli_stmt_bind_param($stmt_earned, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_earned);
$res_earned = mysqli_stmt_get_result($stmt_earned);
$row_earned = mysqli_fetch_assoc($res_earned);
$total_earned = $row_earned['total_earned'] ?? 0;

// --- Get Total Paid Out Amount (only completed payouts) ---
$sql_paid = "SELECT SUM(amount) as total_paid FROM delivery_payouts WHERE delivery_boy_id = ? AND status = 'completed'";
$stmt_paid = mysqli_prepare($con, $sql_paid);
mysqli_stmt_bind_param($stmt_paid, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_paid);
$res_paid = mysqli_stmt_get_result($stmt_paid);
$row_paid = mysqli_fetch_assoc($res_paid);
$total_paid = $row_paid['total_paid'] ?? 0;

// --- Calculate Current Balance ---
$current_balance = $total_earned - $total_paid;

// --- Check if a payout can be requested (Weekly Rule) ---
$can_request_payout = true;
$last_request_msg = "";
$check_sql = "SELECT request_date FROM delivery_payouts WHERE delivery_boy_id = ? ORDER BY request_date DESC LIMIT 1";
$stmt_check = mysqli_prepare($con, $check_sql);
mysqli_stmt_bind_param($stmt_check, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
if(mysqli_num_rows($res_check) > 0){
    $last_request_date = strtotime(mysqli_fetch_assoc($res_check)['request_date']);
    if(time() - $last_request_date < (7 * 24 * 60 * 60)){ // 7 days
        $can_request_payout = false;
        $next_request_date = date('d M, Y', $last_request_date + (7 * 24 * 60 * 60));
        $last_request_msg = "You can make your next request after " . $next_request_date;
    }
}

// --- Get Payout History ---
$sql_history = "SELECT * FROM delivery_payouts WHERE delivery_boy_id = ? ORDER BY request_date DESC";
$stmt_history = mysqli_prepare($con, $sql_history);
mysqli_stmt_bind_param($stmt_history, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_history);
$res_history = mysqli_stmt_get_result($stmt_history);

// --- Fetch Bank Details ---
$stmt_bank = mysqli_prepare($con, "SELECT ac_holder_name, ac_no, ifsc_code, upi_id FROM delivery_boy WHERE id = ?");
mysqli_stmt_bind_param($stmt_bank, "i", $delivery_boy_id);
mysqli_stmt_execute($stmt_bank);
$res_bank = mysqli_stmt_get_result($stmt_bank);
$bank_details = mysqli_fetch_assoc($res_bank);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Wallet</title>
    <link rel="stylesheet" href="../admin/assets/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="../admin/assets/css/vendor.bundle.base.css">
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
                <li class="nav-item"><span class="nav-link">Welcome, <b><?php echo htmlspecialchars($_SESSION['DELIVERY_BOY_USER']); ?></b></span></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel" style="width:100%;">
            <div class="content-wrapper">
                <div class="row page-title-header">
                    <div class="col-12">
                        <div class="page-header">
                            <h4 class="page-title">My Wallet</h4>
                            <div class="quick-link-wrapper w-100 d-md-flex flex-md-wrap">
                                <ul class="quick-links ml-auto">
                                    <li><a href="index.php">Dashboard</a></li>
                                    <li><a href="new_orders.php">New Orders <?php if($total_new_orders > 0) echo '<span class="badge badge-danger ml-1">'.$total_new_orders.'</span>'; ?></a></li>
                                    <li><a href="history.php">Order History</a></li>
                                    <li><a href="wallet.php" class="font-weight-bold">My Wallet</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 grid-margin">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="card-title mb-1">Current Available Balance</p>
                                        <h2 class="mb-0"><b>₹<?php echo number_format($current_balance, 2); ?></b></h2>
                                        <?php if(!$can_request_payout && $current_balance > 0): ?>
                                            <small class="text-white-50"><?php echo $last_request_msg; ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <button id="requestPayoutBtn" class="btn btn-light btn-lg" <?php if($current_balance <= 0 || !$can_request_payout) echo 'disabled'; ?>>Request Payout</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body"><p class="card-title">Total Lifetime Earnings</p><div class="d-flex align-items-center"><h3 class="mb-0">₹<?php echo number_format($total_earned, 2); ?></h3><i class="mdi mdi-trophy-variant-outline icon-lg text-success ml-auto"></i></div></div>
                        </div>
                    </div>
                    <div class="col-md-6 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body"><p class="card-title">Total Paid Out</p><div class="d-flex align-items-center"><h3 class="mb-0">₹<?php echo number_format($total_paid, 2); ?></h3><i class="mdi mdi-wallet-outline icon-lg text-danger ml-auto"></i></div></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="card-title"><i class="mdi mdi-bank"></i> My Payout Details</h4>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#bankDetailsModal">Edit</button>
                                </div>
                                <p class="card-description">Your earnings will be transferred to this account.</p>
                                <div id="bank-details-display" class="mt-3">
                                    <p><strong>A/c Holder Name:</strong> <span id="display_name"><?php echo htmlspecialchars($bank_details['ac_holder_name'] ?? 'Not Added'); ?></span></p>
                                    <p><strong>A/c Number:</strong> <span id="display_ac"><?php echo htmlspecialchars($bank_details['ac_no'] ?? 'Not Added'); ?></span></p>
                                    <p><strong>IFSC Code:</strong> <span id="display_ifsc"><?php echo htmlspecialchars($bank_details['ifsc_code'] ?? 'Not Added'); ?></span></p>
                                    <p><strong>UPI ID:</strong> <span id="display_upi"><?php echo htmlspecialchars($bank_details['upi_id'] ?? 'Not Added'); ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title"><i class="mdi mdi-history"></i> Payout History</h4>
                                <div class="table-responsive mt-3" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table">
                                        <tbody>
                                        <?php if (mysqli_num_rows($res_history) > 0) {
                                            while ($row = mysqli_fetch_assoc($res_history)) {
                                        ?>
                                            <tr>
                                                <td>
                                                    <p class="mb-0"><b>₹<?php echo htmlspecialchars(number_format($row['amount'], 2)); ?></b></p>
                                                    <p class="text-muted mb-0"><small>Requested: <?php echo date('d M, Y', strtotime($row['request_date'])); ?></small></p>
                                                </td>
                                                <td class="text-right">
                                                    <?php if($row['status'] == 'pending'): ?>
                                                        <div class="badge badge-warning">Pending</div>
                                                    <?php else: ?>
                                                        <div class="badge badge-success">Completed</div>
                                                        <p class="text-muted mb-0"><small>Paid: <?php echo date('d M, Y', strtotime($row['payout_date'])); ?></small></p>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php
                                            }
                                        } else { ?>
                                            <tr><td colspan="2" class="text-center"><i class="mdi mdi-history mdi-24px text-muted"></i><p class="text-muted mt-2">No payout history.</p></td></tr>
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
    </div>
</div>

<div class="modal fade" id="bankDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add / Edit Bank Details</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body">
                <form id="bankDetailsForm">
                    <div class="form-group"><label for="ac_holder_name">Account Holder Name*</label><input type="text" class="form-control" id="ac_holder_name" name="ac_holder_name" value="<?php echo htmlspecialchars($bank_details['ac_holder_name'] ?? ''); ?>" required></div>
                    <div class="form-group"><label for="ac_no">Account Number*</label><input type="text" class="form-control" id="ac_no" name="ac_no" value="<?php echo htmlspecialchars($bank_details['ac_no'] ?? ''); ?>" required></div>
                    <div class="form-group"><label for="ifsc_code">IFSC Code*</label><input type="text" class="form-control" id="ifsc_code" name="ifsc_code" value="<?php echo htmlspecialchars($bank_details['ifsc_code'] ?? ''); ?>" required></div>
                    <div class="form-group"><label for="upi_id">UPI ID (Optional)</label><input type="text" class="form-control" id="upi_id" name="upi_id" value="<?php echo htmlspecialchars($bank_details['upi_id'] ?? ''); ?>"></div>
                    <div id="form-message" class="mt-2"></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button><button type="submit" form="bankDetailsForm" class="btn btn-primary">Save changes</button></div>
        </div>
    </div>
</div>

<script src="../admin/assets/js/vendor.bundle.base.js"></script>
<script>
document.getElementById('bankDetailsForm').addEventListener('submit', function(e) { /* ... Bank details form JS ... */ });

document.getElementById('requestPayoutBtn').addEventListener('click', function() {
    if (!confirm('Are you sure you want to request a payout for your entire current balance?')) return;
    const btn = this;
    btn.disabled = true;
    btn.innerText = 'Submitting...';
    fetch('request_payout.php', { method: 'POST' })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if(data.status === 'success'){
            window.location.reload();
        } else {
            btn.disabled = false;
            btn.innerText = 'Request Payout';
        }
    });
});
</script>

</body>
</html>