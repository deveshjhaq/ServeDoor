<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("header.php");

if (!isUserLoggedIn()) {
    redirect(FRONT_SITE_PATH.'login_register.php');
    exit;
}

$uid = (int)($_SESSION['FOOD_USER_ID'] ?? 0);

/* ---------------------------------------
   Resolve order id (GET takes priority)
----------------------------------------*/
$order_id = 0;
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $order_id = (int)$_GET['id'];
} elseif (isset($_SESSION['ORDER_ID']) && ctype_digit((string)$_SESSION['ORDER_ID'])) {
    $order_id = (int)$_SESSION['ORDER_ID'];
}

if ($order_id <= 0) {
    redirect(FRONT_SITE_PATH.'shop');
    exit;
}

/* ---------------------------------------
   Load order & verify ownership
----------------------------------------*/
$stmt = mysqli_prepare($con, "SELECT id, user_id, final_price, payment_type, payment_status, order_status, added_on 
                              FROM order_master WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$order || (int)$order['user_id'] !== $uid) {
    redirect(FRONT_SITE_PATH.'shop');
    exit;
}

/* ---------------------------------------
   Optional: fetch a few line items
----------------------------------------*/
$items = [];
$it = mysqli_prepare($con, "
    SELECT od.qty, od.price, d.dish 
    FROM order_detail od
    JOIN dish_details dd ON dd.id = od.dish_details_id
    JOIN dish d ON d.id = dd.dish_id
    WHERE od.order_id = ?
");
mysqli_stmt_bind_param($it, "i", $order_id);
mysqli_stmt_execute($it);
$itres = mysqli_stmt_get_result($it);
while ($row = mysqli_fetch_assoc($itres)) { $items[] = $row; }
mysqli_stmt_close($it);

/* ---------------------------------------
   Cleanup one-time session flags
----------------------------------------*/
if (isset($_SESSION['COUPON_CODE'])) {
    unset($_SESSION['COUPON_CODE'], $_SESSION['FINAL_PRICE']);
}
if (isset($_SESSION['ORDER_ID'])) {
    unset($_SESSION['ORDER_ID']);
}

/* ---------------------------------------
   View vars
----------------------------------------*/
$amt       = (float)$order['final_price'];
$payType   = strtoupper($order['payment_type']);
$payStatus = strtoupper($order['payment_status']);
$addedOn   = !empty($order['added_on']) ? date('d M Y, h:i A', strtotime($order['added_on'])) : '';

/* Headline selection:
   COD / WALLET -> success immediately
   CASHFREE -> could be PENDING until webhook updates to SUCCESS
*/
$isSuccess = ($payStatus === 'SUCCESS') || in_array($order['payment_type'], ['cod', 'wallet'], true);
?>
<div class="container mx-auto px-4 py-16 text-center">
  <div class="theme-card max-w-lg mx-auto p-8 rounded-lg shadow-xl">
    <span class="material-symbols-outlined text-8xl" style="color:var(--primary-color);">task_alt</span>

    <h1 class="text-4xl font-bold mt-4">
      <?php echo $isSuccess ? 'Order Placed Successfully!' : 'Order Received'; ?>
    </h1>

    <p class="mt-2 text-lg theme-muted">
      <?php if ($isSuccess): ?>
        Thank you for your purchase.
      <?php else: ?>
        Payment is <strong>pending</strong>. If you completed UPI/card payment, it will update automatically in a moment.
      <?php endif; ?>
    </p>

    <p class="mt-4 font-bold text-xl">
      Your Order ID is: <span style="color:var(--primary-color);">#<?php echo (int)$order_id; ?></span>
    </p>

    <div class="mt-6 text-left text-sm theme-muted max-w-md mx-auto space-y-1">
      <div class="flex justify-between py-1"><span>Amount</span><span>₹<?php echo number_format($amt, 2); ?></span></div>
      <div class="flex justify-between py-1"><span>Payment Type</span><span><?php echo htmlspecialchars($payType); ?></span></div>
      <div class="flex justify-between py-1">
        <span>Payment Status</span>
        <span class="<?php echo ($payStatus==='SUCCESS' ? 'text-green-600' : 'text-yellow-700'); ?>">
          <?php echo htmlspecialchars($payStatus); ?>
        </span>
      </div>
      <?php if ($addedOn): ?>
      <div class="flex justify-between py-1"><span>Order Time</span><span><?php echo htmlspecialchars($addedOn); ?></span></div>
      <?php endif; ?>
    </div>

    <?php if (!empty($items)): ?>
      <div class="mt-6 text-left max-w-md mx-auto">
        <h3 class="font-semibold mb-2">Items</h3>
        <ul class="space-y-1 text-sm">
          <?php foreach ($items as $it): ?>
            <li class="flex justify-between">
              <span><?php echo htmlspecialchars($it['dish']); ?> × <?php echo (int)$it['qty']; ?></span>
              <span>₹<?php echo number_format((float)$it['price'] * (int)$it['qty'], 2); ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($order['payment_type']==='cashfree' && $payStatus!=='SUCCESS'): ?>
      <p class="mt-3 text-xs theme-muted">
        Tip: If you were charged but the status is still pending, it will update via our secure payment webhook shortly. You can also check the latest status in “My Orders”.
      </p>
    <?php endif; ?>

    <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
      <a href="<?php echo FRONT_SITE_PATH; ?>my_order" class="w-full sm:w-auto text-white font-bold py-3 px-6 rounded-lg" style="background:var(--primary-color)">
        View My Orders
      </a>
      <a href="<?php echo FRONT_SITE_PATH; ?>shop" class="w-full sm:w-auto font-bold py-3 px-6 rounded-lg border theme-border hover:bg-gray-100 dark:hover:bg-gray-700">
        Continue Shopping
      </a>
    </div>
  </div>
</div>

<?php include("footer.php"); ?>
