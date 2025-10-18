<?php
// Minimal wallet_success.php — no header/footer, only verify + message
session_start();
require_once __DIR__.'/constant.inc.php';

$order_id = isset($_GET['order_id']) ? preg_replace('/[^A-Za-z0-9_\-]/','', $_GET['order_id']) : '';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Wallet Top-up</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Helvetica,Arial,sans-serif;
       margin:40px auto;max-width:680px;padding:0 16px;color:#111}
  .muted{color:#6b7280}
  .ok{color:#16a34a;font-weight:700}
  .warn{color:#b45309}
  .err{color:#dc2626}
  .box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin-top:16px}
  a.btn{display:inline-block;background:#7c3aed;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:700}
</style>
</head>
<body>
  <h1>Payment Status</h1>

  <?php if ($order_id): ?>
    <p class="muted">Order ID: <b><?php echo htmlspecialchars($order_id); ?></b></p>
    <div id="status" class="box">Verifying payment…</div>

    <script>
    (async () => {
      try{
        const url = 'cashfree/verifypayment.php?order_id=' + encodeURIComponent('<?php echo $order_id; ?>') + '&credit=1';
        const res = await fetch(url, { headers: { 'Accept':'application/json' } });
        const t = await res.json();

        const el = document.getElementById('status');
        if (!t || t.success === false) {
          el.innerHTML = `<div class="err">Verification failed.</div>
                          <div class="muted">Details: ${ (t && t.message) ? t.message : 'Unknown error' }</div>`;
          return;
        }

        // Show status & credit result
        let msg = `<div><b>Status:</b> ${t.status || 'UNKNOWN'}</div>`;
        if (t.credited === true) {
          msg += `<div class="ok">Wallet credited successfully.</div>`;
        } else if (t.credited === 'already') {
          msg += `<div class="ok">Wallet already credited.</div>`;
        } else {
          msg += `<div class="warn">Payment received. Wallet update may take a moment.</div>`;
        }
        if (t.payment_id) msg += `<div><b>Payment ID:</b> ${t.payment_id}</div>`;
        el.innerHTML = msg;
      }catch(e){
        document.getElementById('status').innerHTML =
          `<div class="warn">Payment received. It may take a moment to reflect.</div>
           <div class="muted">Network note: ${e && e.message ? e.message : e}</div>`;
      }
    })();
    </script>
  <?php else: ?>
    <div class="box">Order ID missing. If you paid successfully, your wallet will be updated shortly.</div>
  <?php endif; ?>

  <p style="margin-top:24px"><a class="btn" href="wallet.php">Back to Wallet</a></p>

  <!-- Optional quick-diag: show raw query -->
  <details style="margin-top:16px"><summary class="muted">Debug</summary><pre class="muted"><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></pre></details>
</body>
</html>
