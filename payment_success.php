<?php
session_start();
require_once __DIR__.'/constant.inc.php';
$order_id = isset($_GET['order_id']) ? preg_replace('/[^A-Za-z0-9_\-]/','', $_GET['order_id']) : '';
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Payment Success</title>
<style>body{font-family:system-ui;margin:40px auto;max-width:640px} .muted{color:#6b7280}</style>
</head>
<body>
  <h2>Thank you for your payment!</h2>
  <?php if ($order_id): ?>
    <p>Order: <b><?php echo htmlspecialchars($order_id); ?></b></p>
    <p id="msg" class="muted">Verifying payment…</p>
    <script>
      (async () => {
        try{
          // 1) Verify
          const v = await fetch('cashfree/verifypayment.php?order_id=<?php echo urlencode($order_id); ?>&credit=0');
          const j = await v.json();
          if (!j || j.success !== true || !['PAID','COMPLETED','SUCCESS','SUCCESSFUL'].includes(j.status||'')) {
            document.getElementById('msg').textContent = 'Verification failed or payment not successful.';
            return;
          }
          document.getElementById('msg').textContent = 'Creating your order…';
          // 2) Create order server-side from session cart + persisted checkout
          const c = await fetch('cashfree/confirm_order.php?order_id='+encodeURIComponent('<?php echo $order_id; ?>'));
          const r = await c.json();
          if (r && r.success) {
            location.href = 'success.php?id=' + r.order_id;
          } else {
            document.getElementById('msg').textContent = 'Order creation failed.';
          }
        }catch(e){
          document.getElementById('msg').textContent = 'Network error while creating order.';
        }
      })();
    </script>
  <?php else: ?>
    <p>Your payment has been received.</p>
  <?php endif; ?>
  <p><a href="shop">Continue Shopping</a></p>
</body></html>
