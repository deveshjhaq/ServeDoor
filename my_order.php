<?php
include('header.php');

// Redirect user if they are not logged in
if(!isset($_SESSION['FOOD_USER_ID'])){
    redirect(FRONT_SITE_PATH.'shop');
    exit;
}
$uid = $_SESSION['FOOD_USER_ID'];

// --- SECURE CANCEL ORDER LOGIC ---
if(isset($_GET['cancel_id'])){
    $cancel_id = get_safe_value($_GET['cancel_id']);
    $cancel_at = date('Y-m-d H:i:s');
    
    // Using prepared statement to prevent SQL injection
    $sql_cancel = "UPDATE order_master SET order_status='5', cancel_by='user', cancel_at=? WHERE id=? AND user_id=? AND order_status='1'";
    $stmt_cancel = mysqli_prepare($con, $sql_cancel);
    mysqli_stmt_bind_param($stmt_cancel, "sii", $cancel_at, $cancel_id, $uid);
    mysqli_stmt_execute($stmt_cancel);
    mysqli_stmt_close($stmt_cancel);
    // Redirect to the same page to refresh the view
    redirect(FRONT_SITE_PATH.'my_order');
    exit;
}

// --- SECURE QUERY TO FETCH ORDERS ---
$sql = "SELECT order_master.*, order_status.order_status AS order_status_str 
        FROM order_master 
        INNER JOIN order_status ON order_master.order_status = order_status.id 
        WHERE order_master.user_id = ? 
        ORDER BY order_master.id DESC";
        
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 border-b theme-border pb-3">Order History</h1>

    <?php if (count($orders) > 0): ?>
        <div class="theme-card p-4 sm:p-6 rounded-lg shadow-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y theme-border">
                    <thead class="theme-surface">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium theme-muted uppercase tracking-wider">Order</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium theme-muted uppercase tracking-wider">Address</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium theme-muted uppercase tracking-wider">Price Details</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium theme-muted uppercase tracking-wider">Status & Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y theme-border">
                        <?php foreach ($orders as $row): ?>
                            <tr>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="font-medium">#<?php echo htmlspecialchars($row['id']); ?></div>
                                    <div class="text-sm theme-muted"><?php echo date('d M, Y', strtotime($row['added_on'])); ?></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <div><?php echo htmlspecialchars($row['address']); ?></div>
                                    <div class="theme-muted">Zip: <?php echo htmlspecialchars($row['zipcode']); ?></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <div>Final Price: <span class="font-medium">₹<?php echo htmlspecialchars($row['final_price']); ?></span></div>
                                    <div class="capitalize theme-muted">Payment: <?php echo htmlspecialchars($row['payment_status']); ?></div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <div class="font-medium"><?php echo htmlspecialchars($row['order_status_str']); ?></div>
                                    <div class="mt-2 space-y-2">
                                        <a href="<?php echo FRONT_SITE_PATH.'order_detail.php?id='.$row['id']?>" class="text-sm font-medium hover:underline text-blue-600">View Details</a>
                                        
                                        <?php if($row['order_status'] == 3): // If order status is 'On the Way' ?>
                                            <a href="<?php echo FRONT_SITE_PATH.'my_order_detail.php?id='.$row['id']; ?>" class="block text-sm font-medium hover:underline text-green-600">Track Order</a>
                                        <?php endif; ?>

                                        <?php if($row['order_status'] == 1): // If order status is 'Pending' ?>
                                            <a href="?cancel_id=<?php echo $row['id']; ?>" class="block text-sm font-medium hover:underline text-red-500" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</a>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo FRONT_SITE_PATH.'download_invoice.php?id='.$row['id']?>" class="block text-sm font-medium hover:underline text-gray-600">Download Invoice</a>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="theme-card p-8 rounded-lg shadow-lg text-center">
            <span class="material-symbols-outlined text-6xl theme-muted">receipt_long</span>
            <h2 class="mt-4 text-2xl font-bold">You haven't placed any orders yet.</h2>
            <p class="mt-2 theme-muted">All your future orders will appear here.</p>
            <a href="<?php echo FRONT_SITE_PATH?>shop" class="mt-6 inline-block text-white font-bold py-3 px-6 rounded-lg" style="background:var(--primary-color)">Shop Now</a>
        </div>
    <?php endif; ?>
</div>

<?php
include('footer.php');
?>