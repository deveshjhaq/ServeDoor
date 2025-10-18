<?php
// wallet_checkout.php  — opens Cashfree Hosted Checkout using payment_session_id
session_start();
require_once __DIR__.'/constant.inc.php';

// payment_session_id ko query ya session se lo
$psid = '';
if (!empty($_GET['psid'])) {
  $psid = $_GET['psid'];
} elseif (!empty($_SESSION['CF_PAYMENT_SESSION_ID'])) {
  $psid = $_SESSION['CF_PAYMENT_SESSION_ID'];
}

// basic guard
if ($psid === '') {
  echo '<p style="font-family:system-ui">Missing payment_session_id.</p><p><a href="wallet.php">Back</a></p>';
  exit;
}

// Mode: production (aap LIVE par ho)
$mode = (defined('CASHFREE_ENV') && CASHFREE_ENV === 'production') ? 'production' : 'sandbox';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>ServeDoor — Secure Checkout</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <!-- Cashfree JS SDK (v3) -->
  <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; padding: 24px; }
    .wrap { max-width: 520px; margin: 10vh auto; text-align: center; }
    button { padding: 12px 18px; border-radius: 8px; border: 0; font-weight: 700; cursor: pointer; }
  </style>
</head>
<body>
  <div class="wrap">
    <h2>Redirecting to secure payment…</h2>
    <p>If not redirected, click the button below.</p>
    <button id="payNow">Pay Now</button>
  </div>

  <script>
    (function() {
      // PHP se injected variables
      const PAYMENT_SESSION_ID = <?php echo json_encode($psid); ?>;
      const MODE = <?php echo json_encode($mode); ?>; // "production" or "sandbox"

      // Initialize Cashfree SDK
      const cashfree = Cashfree({ mode: MODE });

      function openCheckout() {
        // Redirect checkout (same tab). Alternatives: "_blank", "_modal", or DOM element for inline
        cashfree.checkout({
          paymentSessionId: PAYMENT_SESSION_ID,
          redirectTarget: "_self"
        });
      }

      // Auto-open
      openCheckout();

      // Manual fallback button
      document.getElementById('payNow').addEventListener('click', openCheckout);
    })();
  </script>
</body>
</html>
