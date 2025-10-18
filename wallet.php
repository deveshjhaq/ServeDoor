<?php
// wallet.php — ServeDoor Wallet (Production)

// Common bootstrap
include "header.php";                // header + session + $con (DB) etc.
require_once __DIR__."/constant.inc.php";
require_once __DIR__."/function.inc.php";

// Auth guard
if(!isset($_SESSION['FOOD_USER_ID'])){
    redirect(FRONT_SITE_PATH.'shop');
    exit;
}

$userId = (int)$_SESSION['FOOD_USER_ID'];

// Helpers (use existing functions if you already added them)
if (!function_exists('getWalletBalance')) {
    function getWalletBalance($user_id, $con){
        $user_id = (int)$user_id;
        $sql = "SELECT 
                  COALESCE(SUM(CASE WHEN type='in'  THEN amt END),0) -
                  COALESCE(SUM(CASE WHEN type='out' THEN amt END),0) AS bal
                FROM wallet WHERE user_id=".$user_id;
        $res = mysqli_query($con,$sql);
        $row = mysqli_fetch_assoc($res);
        return (float)$row['bal'];
    }
}
if (!function_exists('getWalletTxns')) {
    function getWalletTxns($user_id, $limit=100){
        global $con;
        $user_id = (int)$user_id;
        $limit   = (int)$limit;
        $sql = "SELECT id, amt, msg, type, payment_id, added_on
                FROM wallet
                WHERE user_id={$user_id}
                ORDER BY added_on DESC, id DESC
                LIMIT {$limit}";
        $res = mysqli_query($con,$sql);
        $out = [];
        while($row = mysqli_fetch_assoc($res)){ $out[] = $row; }
        return $out;
    }
}

$balance    = getWalletBalance($userId, $con);
$txns       = getWalletTxns($userId, 100);
$err_msg    = '';
$minTopup   = 10; // backstop validation
?>
<style>
    .wallet_in { color:#22c55e; font-weight:600; }   /* green */
    .wallet_out{ color:#ef4444; font-weight:600; }   /* red   */
    .theme-card{ background:var(--theme-surface,#fff); }
    .theme-input{ width:100%; padding:.75rem 1rem; border:1px solid var(--theme-border,#e5e7eb); border-radius:.5rem; outline:none; }
    .theme-muted{ color:#6b7280; }
    .btn-primary{ background:var(--primary-color,#7c3aed); color:#fff; border:0; border-radius:.5rem; padding:.6rem 1rem; font-weight:700; cursor:pointer; }
    .btn-primary[disabled]{ opacity:.6; cursor:not-allowed; }
</style>

<section class="container mx-auto px-4 py-8">
  <div class="max-w-4xl mx-auto">

    <h1 class="text-2xl md:text-3xl font-bold mb-6">My Wallet</h1>

    <!-- Balance Card -->
    <div class="theme-card p-5 rounded-lg shadow-lg border theme-border mb-6">
      <div class="text-sm theme-muted mb-1">Current Wallet Balance</div>
      <div class="text-3xl font-extrabold">₹<?php echo number_format($balance,2); ?></div>
    </div>

    <!-- Add Money -->
    <div class="theme-card p-6 rounded-lg shadow-lg mb-8 border theme-border">
      <h2 class="text-xl font-bold border-b theme-border pb-3 mb-4">Add Money to Wallet</h2>

      <!-- Progressive enhancement: if JS is off, form could still post to legacy endpoint (optional) -->
      <form id="addMoneyForm" class="flex flex-col sm:flex-row items-start sm:items-center gap-4" onsubmit="return false;">
        <div class="w-full sm:w-auto flex-grow">
          <label for="amt" class="sr-only">Amount</label>
          <input type="number" id="amt" name="amt" class="theme-input" placeholder="Enter Amount" required step="0.01" min="<?php echo (int)$minTopup; ?>">
        </div>
        <button id="btnAdd" type="button" class="w-full sm:w-auto btn-primary">Proceed to Add</button>
      </form>

      <div id="err" class="text-red-500 mt-3 text-sm">
        <?php if ($err_msg!='') echo htmlspecialchars($err_msg); ?>
      </div>

      <p class="text-xs theme-muted mt-2">Minimum top-up ₹<?php echo (int)$minTopup; ?>. Payment is processed via Cashfree (UPI / Cards / NetBanking).</p>
    </div>

    <!-- History -->
    <div class="theme-card p-6 rounded-lg shadow-lg border theme-border">
      <h2 class="text-xl font-bold border-b theme-border pb-3 mb-4">Transaction History</h2>
      <div class="overflow-x-auto">
        <?php if (!empty($txns)): ?>
          <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase theme-surface">
              <tr>
                <th class="px-6 py-3">S.No</th>
                <th class="px-6 py-3">Amount</th>
                <th class="px-6 py-3">Details</th>
                <th class="px-6 py-3">Payment ID</th>
                <th class="px-6 py-3">Date</th>
              </tr>
            </thead>
            <tbody>
            <?php $i=1; foreach($txns as $row): ?>
              <tr class="theme-surface border-b theme-border">
                <td class="px-6 py-4"><?php echo $i++; ?></td>
                <td class="px-6 py-4 font-medium <?php echo ($row['type']==='in')?'wallet_in':'wallet_out'; ?>">
                  <?php echo ($row['type']==='in'?'+':'-'); ?> ₹<?php echo htmlspecialchars(number_format((float)$row['amt'],2)); ?>
                </td>
                <td class="px-6 py-4"><?php echo htmlspecialchars($row['msg']); ?></td>
                <td class="px-6 py-4">
                  <?php echo $row['payment_id'] ? htmlspecialchars($row['payment_id']) : '<span class="theme-muted">—</span>'; ?>
                </td>
                <td class="px-6 py-4 theme-muted"><?php echo date('d M, Y h:i A', strtotime($row['added_on'])); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-center theme-muted py-8">No transactions found.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</section>

<!-- Cashfree SDK + Wallet JS -->
<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<script type="module">
  // Unified production SDK instance
  const cashfree = Cashfree({ mode: "production" });

  async function createOrder(amount, customer_id){
    const res = await fetch('cashfree/createorder.php', {
      method: 'POST',
      headers: { 'Content-Type':'application/json' },
      body: JSON.stringify({
        amount: amount,
        customer_id: customer_id,
        purpose: 'wallet',             // wallet top-up
        order_note: 'Wallet Top-up'
      })
    });
    return res.json();
  }

  const btn   = document.getElementById('btnAdd');
  const input = document.getElementById('amt');
  const errEl = document.getElementById('err');
  const MIN   = <?php echo (int)$minTopup; ?>;
  const USER  = <?php echo (int)$userId; ?>;

  btn.addEventListener('click', async () => {
    errEl.textContent = '';
    let amt = parseFloat(input.value || '0');
    if (isNaN(amt) || amt < MIN){
      errEl.textContent = 'Please enter at least ₹'+MIN;
      return;
    }
    btn.disabled = true; btn.textContent = 'Creating order...';

    try{
      const resp = await createOrder(amt, USER);
      if (!resp.success){
        errEl.textContent = resp.message || 'Failed to create order';
        btn.disabled = false; btn.textContent = 'Proceed to Add';
        return;
      }

      if (resp.payment_link){
        // Hosted checkout link (if Cashfree returns)
        window.location.href = resp.payment_link;
        return;
      }

      if (resp.payment_session_id){
        // Open JS SDK checkout (Drop-in)
        await cashfree.checkout({
          paymentSessionId: resp.payment_session_id,
          redirectTarget: "_self"
        });
        return;
      }

      errEl.textContent = 'Unexpected response from gateway.';
    }catch(e){
      errEl.textContent = 'Network error. Try again.';
    }finally{
      // Button will change page on success; if not, re-enable
      btn.disabled = false; btn.textContent = 'Proceed to Add';
    }
  });
</script>

<?php include "footer.php"; ?>
