<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ("header.php");

if(!isset($_SESSION['FOOD_USER_ID'])){
    redirect(FRONT_SITE_PATH.'login_register.php');
    die();
}
$uid = (int)$_SESSION['FOOD_USER_ID'];

$cartArr = getUserFullCart();
if($website_close==1 || count($cartArr) === 0){
    redirect(FRONT_SITE_PATH.'shop');
    die();
}

$subtotal = getcartTotalPrice();

/* ---------------- Restaurant guard: single restaurant only --------------- */
$restaurant_id = null;
if(count($cartArr) > 0){
    $dish_detail_ids = array_map('intval', array_keys($cartArr));
    $ids_placeholder = implode(',', array_fill(0, count($dish_detail_ids), '?'));
    $types = str_repeat('i', count($dish_detail_ids));
    $sql_check = "SELECT DISTINCT d.restaurant_id 
                  FROM dish_details dd 
                  JOIN dish d ON d.id = dd.dish_id 
                  WHERE dd.id IN ($ids_placeholder)";
    $stmt_check = mysqli_prepare($con, $sql_check);
    mysqli_stmt_bind_param($stmt_check, $types, ...$dish_detail_ids);
    mysqli_stmt_execute($stmt_check);
    $res_check = mysqli_stmt_get_result($stmt_check);
    if(mysqli_num_rows($res_check) > 1){
        $_SESSION['CHECKOUT_ERROR'] = "You can only order from one restaurant at a time.";
        redirect(FRONT_SITE_PATH.'cart');
        exit;
    }
    $rowR = mysqli_fetch_assoc($res_check);
    $restaurant_id = (int)($rowR['restaurant_id'] ?? 0);
    mysqli_stmt_close($stmt_check);
}

/* ---------------- COD / Wallet server flow (Corrected & Secure) ---------------- */
if(isset($_POST['place_order'])){
    $payment_type = get_safe_value($_POST['payment_type']);

    // Server-side validation
    $required_fields = ['address', 'zipcode', 'final_subtotal', 'final_grand_total'];
    foreach($required_fields as $field){
        if(empty($_POST[$field])){
            $_SESSION['CHECKOUT_ERROR'] = "Missing required information. Please try again.";
            redirect(FRONT_SITE_PATH.'checkout');
            exit;
        }
    }

    // --- SECURITY FIX: SERVER-SIDE WALLET BALANCE CHECK ---
    if($payment_type == 'wallet'){
        $current_wallet_balance = getWalletAmt($uid);
        $final_grand_total  = (float)get_safe_value($_POST['final_grand_total']);
        
        if($current_wallet_balance < $final_grand_total){
            $_SESSION['CHECKOUT_ERROR'] = "Insufficient funds in wallet. Please top up or choose another payment method.";
            redirect(FRONT_SITE_PATH.'checkout');
            exit;
        }
    }
    // --- END OF FIX ---

    if($payment_type == 'cod' || $payment_type == 'wallet'){
        $address            = get_safe_value($_POST['address']);
        $zipcode            = get_safe_value($_POST['zipcode']);
        $final_subtotal     = (float)get_safe_value($_POST['final_subtotal']);
        $final_gst          = (float)get_safe_value($_POST['final_gst']);
        $final_platform_fee = (float)get_safe_value($_POST['final_platform_fee']);
        $final_delivery_fee = (float)get_safe_value($_POST['final_delivery_fee']);
        $final_grand_total  = (float)get_safe_value($_POST['final_grand_total']);
        $dropoff_lat        = ($_POST['dropoff_lat']!=='') ? (float)$_POST['dropoff_lat'] : null;
        $dropoff_lng        = ($_POST['dropoff_lng']!=='') ? (float)$_POST['dropoff_lng'] : null;
        $user_details       = getUserDetailsByid();

        // Insert into order_master table
        $sql_master = "INSERT INTO order_master 
          (user_id, restaurant_id, name, email, mobile, address, zipcode, total_price, 
           order_status, payment_status, added_on, final_price, payment_type, gst_amount, 
           platform_fee, delivery_fee, dropoff_lat, dropoff_lng) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
        $stmt_master = mysqli_prepare($con, $sql_master);
        $order_status   = 1;
        $payment_status = ($payment_type == 'wallet') ? 'success' : 'pending';
        mysqli_stmt_bind_param(
            $stmt_master, "iisssssdssdssdddd",
            $uid, $restaurant_id, $user_details['name'], $user_details['email'], $user_details['mobile'],
            $address, $zipcode, $final_subtotal, $order_status, $payment_status,
            $final_grand_total, $payment_type, $final_gst, $final_platform_fee,
            $final_delivery_fee, $dropoff_lat, $dropoff_lng
        );
        mysqli_stmt_execute($stmt_master);
        $insert_id = mysqli_insert_id($con);
        mysqli_stmt_close($stmt_master);

        // Insert into order_detail table
        $sql_detail = "INSERT INTO order_detail(order_id, dish_details_id, price, qty) VALUES(?, ?, ?, ?)";
        $stmt_detail = mysqli_prepare($con, $sql_detail);
        foreach($cartArr as $ddid=>$val){
            $ddid = (int)$ddid;
            $price = (float)$val['price'];
            $qty   = (int)$val['qty'];
            mysqli_stmt_bind_param($stmt_detail, "iidi", $insert_id, $ddid, $price, $qty);
            mysqli_stmt_execute($stmt_detail);
        }
        mysqli_stmt_close($stmt_detail);

        // This code will now only run if the balance was sufficient
        if($payment_type == 'wallet'){
            // FIXED: Pass payment_id as NULL to avoid unique constraint violation
            $wallet_result = manageWallet($uid, $final_grand_total, 'out', 'Order Placed - #'.$insert_id, NULL);
            if (!$wallet_result) {
                $_SESSION['CHECKOUT_ERROR'] = "Wallet transaction failed. Please try again.";
                redirect(FRONT_SITE_PATH.'checkout');
                exit;
            }
        }

        // Notifications
        $admin_mobile = '6205411077';
        $restaurant_res = mysqli_query($con, "SELECT phone FROM restaurants WHERE id='$restaurant_id'");
        $restaurant_mobile = '';
        if($restaurant_res && mysqli_num_rows($restaurant_res)>0){
            $restaurant_mobile = mysqli_fetch_assoc($restaurant_res)['phone'];
        }
        sendWhatsAppNotification($admin_mobile, $insert_id, $final_grand_total, $payment_type);
        if($restaurant_mobile){ sendWhatsAppNotification($restaurant_mobile, $insert_id, $final_grand_total, $payment_type); }
        $email_html = orderEmail($insert_id, $uid);
        if (!empty($user_details['email'])) {
            send_email($user_details['email'], $email_html, 'Order Placed Successfully - ServeDoor');
        }

        emptyCart();
        redirect(FRONT_SITE_PATH.'success.php?id='.$insert_id);
        die();
    }
}


/* ---------------- UI data ---------------- */
$userArr              = getUserDetailsByid();
$getWalletAmt         = (float)getWalletAmt($uid);
$sql_addresses        = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC";
$stmt_addresses       = mysqli_prepare($con, $sql_addresses);
mysqli_stmt_bind_param($stmt_addresses, "i", $uid);
mysqli_stmt_execute($stmt_addresses);
$saved_addresses = [];
$res_addr = mysqli_stmt_get_result($stmt_addresses);
while($row_addr = mysqli_fetch_assoc($res_addr)){ $saved_addresses[] = $row_addr; }
mysqli_stmt_close($stmt_addresses);

$restaurant_coords = ['lat'=>0,'lng'=>0];
$resCoords = mysqli_query($con, "SELECT lat, lng FROM restaurants WHERE id='".(int)$restaurant_id."'");
if($resCoords && mysqli_num_rows($resCoords)>0){
    $restaurant_coords = mysqli_fetch_assoc($resCoords);
}
$settings = mysqli_fetch_assoc(mysqli_query($con, "SELECT gst_percentage, delivery_fee_per_km, platform_fee FROM setting WHERE id=1"));
$platform_fee         = (float)($settings['platform_fee'] ?? 0);
$gst_percentage       = (float)($settings['gst_percentage'] ?? 0);
$delivery_fee_per_km  = (float)($settings['delivery_fee_per_km'] ?? 0);
$gst_amount           = ($subtotal * $gst_percentage) / 100;
$total_without_delivery = $subtotal + $platform_fee + $gst_amount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ServeDoor</title>
    <style>
        .text-yellow-600 { color: #d97706; }
        .text-blue-600 { color: #2563eb; }
        .text-green-600 { color: #059669; }
        .border-warning { border-left: 3px solid #d97706; }
        .border-success { border-left: 3px solid #10b981; }
        .manual-entry { border-left: 3px solid #2563eb; }
        .loading-spinner { 
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="container mx-auto px-4 py-8">
  <h1 class="text-3xl font-bold mb-6 border-b theme-border pb-3">Checkout</h1>
  
  <?php
    if (isset($_SESSION['CHECKOUT_ERROR'])) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
        echo '<strong class="font-bold">Error!</strong>';
        echo '<span class="block sm:inline"> '.htmlspecialchars($_SESSION['CHECKOUT_ERROR']).'</span>';
        echo '</div>';
        unset($_SESSION['CHECKOUT_ERROR']);
    }
  ?>
  
  <form id="checkoutForm" method="post" novalidate>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-6">

        <div class="theme-card p-6 rounded-lg shadow-lg">
          <h2 class="text-xl font-bold mb-4">1. Your Details</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-muted mb-1">Name</label>
              <input type="text" value="<?php echo htmlspecialchars($userArr['name']); ?>" class="theme-input bg-gray-100 dark:bg-gray-700" readonly>
            </div>
            <div>
              <label class="block text-sm font-medium theme-muted mb-1">Email</label>
              <input type="text" value="<?php echo htmlspecialchars($userArr['email']); ?>" class="theme-input bg-gray-100 dark:bg-gray-700" readonly>
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium theme-muted mb-1">Mobile</label>
              <input type="text" value="<?php echo htmlspecialchars($userArr['mobile']); ?>" class="theme-input bg-gray-100 dark:bg-gray-700" readonly>
            </div>
          </div>
        </div>

        <div class="theme-card p-6 rounded-lg shadow-lg">
          <h2 class="text-xl font-bold mb-4">2. Delivery Address</h2>
          
          <!-- Saved Addresses Section -->
          <?php if(count($saved_addresses) > 0): ?>
          <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">Select from saved addresses</h3>
            <div id="address-list" class="space-y-3">
              <?php foreach($saved_addresses as $address): ?>
              <label class="flex items-start p-4 border theme-border rounded-lg cursor-pointer hover:border-[var(--primary-color)] transition-colors address-item">
                <input type="radio" name="selected_address_id"
                       onclick="selectSavedAddress(this)"
                       data-lat="<?php echo $address['lat']; ?>"
                       data-lng="<?php echo $address['lng']; ?>"
                       data-address="<?php echo htmlspecialchars($address['address']); ?>"
                       data-city="<?php echo htmlspecialchars($address['city']); ?>"
                       data-pincode="<?php echo htmlspecialchars($address['pincode']); ?>"
                       data-state="<?php echo htmlspecialchars($address['state']); ?>"
                       data-address-type="<?php echo htmlspecialchars($address['address_type']); ?>"
                       class="accent-[var(--primary-color)] mt-1 address-radio"
                       <?php echo ($address['is_default'] == 1) ? 'checked' : ''; ?>>
                <div class="ml-3 flex-1">
                  <div class="flex justify-between items-start">
                    <div>
                      <span class="font-medium block"><?php echo htmlspecialchars($address['address_type']); ?></span>
                      <?php if($address['is_default'] == 1): ?>
                        <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded ml-2">Default</span>
                      <?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                      <button type="button" onclick="editAddress(<?php echo $address['id']; ?>)" class="text-blue-500 hover:text-blue-700 text-sm">
                        Edit
                      </button>
                      <?php if($address['is_default'] == 0): ?>
                        <button type="button" onclick="setDefaultAddress(<?php echo $address['id']; ?>)" class="text-green-500 hover:text-green-700 text-sm">
                          Set Default
                        </button>
                      <?php endif; ?>
                    </div>
                  </div>
                  <span class="text-sm theme-muted block mt-1"><?php echo htmlspecialchars($address['address']); ?></span>
                  <span class="text-xs theme-muted"><?php echo htmlspecialchars($address['city']) . ', ' . htmlspecialchars($address['state']) . ' - ' . htmlspecialchars($address['pincode']); ?></span>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Add New Address Section -->
          <div class="border-t theme-border pt-4">
            <div id="addNewAddressToggle" class="flex items-center gap-2 p-4 border-2 border-dashed theme-border rounded-lg cursor-pointer hover:border-[var(--primary-color)] transition-colors">
              <span class="material-symbols-outlined text-2xl text-[var(--primary-color)]">add_location</span>
              <span class="ml-1 font-medium">Add a New Address</span>
            </div>

            <div id="newAddressForm" class="mt-4 p-4 border theme-border rounded-lg" style="display:none;">
              <h3 class="text-lg font-semibold mb-3">Add New Address</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium theme-muted mb-1">Address Type *</label>
                  <select id="address_type" class="theme-input">
                    <option value="">Select Type</option>
                    <option value="Home">Home</option>
                    <option value="Work">Work</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium theme-muted mb-1">Custom Name (Optional)</label>
                  <input type="text" id="custom_address_name" placeholder="e.g., My Office, Mom's House" class="theme-input">
                </div>
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium theme-muted mb-1">Search Address *</label>
                <input type="text" id="autocomplete" placeholder="Search and select your full address" class="theme-input">
                <p class="text-xs theme-muted mt-1">Start typing your complete address and select from suggestions</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium theme-muted mb-1">Flat/House No. *</label>
                  <input type="text" id="flat_no" placeholder="House/Flat number" class="theme-input">
                </div>
                <div>
                  <label class="block text-sm font-medium theme-muted mb-1">Landmark</label>
                  <input type="text" id="landmark" placeholder="Nearby landmark" class="theme-input">
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium theme-muted mb-1">City *</label>
                  <input type="text" id="city" class="theme-input" placeholder="Enter city">
                  <p class="text-xs mt-1" id="city_note">Auto-filled from address</p>
                </div>
                <div>
                  <label class="block text-sm font-medium theme-muted mb-1">Pincode *</label>
                  <input type="text" id="pincode" class="theme-input" placeholder="Enter 6-digit pincode" maxlength="6">
                  <p class="text-xs mt-1" id="pincode_note">Auto-filled from address</p>
                </div>
                <div>
                  <label class="block text-sm font-medium theme-muted mb-1">State *</label>
                  <input type="text" id="state" class="theme-input" placeholder="Enter state">
                  <p class="text-xs mt-1" id="state_note">Auto-filled from address</p>
                </div>
              </div>

              <div class="flex gap-3">
                <button type="button" id="saveAddressBtn" class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center gap-2">
                  <span>Save Address</span>
                </button>
                <button type="button" id="cancelAddressBtn" class="theme-btn-secondary px-6 py-2 rounded-lg">
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="theme-card p-6 rounded-lg shadow-lg">
          <h2 class="text-xl font-bold mb-4">3. Payment Method</h2>
          <div class="space-y-3">
            <label class="flex items-center p-4 border theme-border rounded-lg cursor-pointer hover:border-[var(--primary-color)] transition-colors">
              <input type="radio" name="payment_type" value="cod" class="accent-[var(--primary-color)]" checked>
              <span class="ml-3 font-medium">Cash on Delivery (COD)</span>
            </label>

            <label class="flex items-center p-4 border theme-border rounded-lg cursor-pointer hover:border-[var(--primary-color)] transition-colors">
              <input type="radio" id="wallet" name="payment_type" value="wallet" class="accent-[var(--primary-color)]" <?php echo ($getWalletAmt < $total_without_delivery) ? "disabled" : ""; ?>>
              <span class="ml-3 font-medium">Wallet</span>
              <span id="wallet_low_msg" class="ml-2 text-red-500 text-xs">
                <?php echo ($getWalletAmt < $total_without_delivery) ? "(Low Balance)" : ""; ?>
              </span>
            </label>

            <label class="flex items-center p-4 border theme-border rounded-lg cursor-pointer hover:border-[var(--primary-color)] transition-colors">
              <input type="radio" id="online_payment" name="payment_type" value="online" class="accent-[var(--primary-color)]">
              <span class="ml-3 font-medium">Online / UPI</span>
            </label>
          </div>
        </div>

      </div>

      <div class="lg:col-span-1">
        <div class="theme-card p-6 rounded-lg shadow-lg sticky top-24">
          <h2 class="text-xl font-bold border-b theme-border pb-3 mb-4">Order Summary</h2>

          <div class="space-y-4 max-h-60 overflow-auto pr-2 mb-4">
            <?php foreach($cartArr as $key=>$list){ ?>
              <div class="flex items-center gap-4 text-sm">
                <img src="<?php echo SITE_DISH_IMAGE.$list['image']?>" class="w-12 h-12 rounded-lg object-cover" alt="<?php echo htmlspecialchars($list['dish']); ?>">
                <div class="flex-1">
                  <h4 class="font-medium"><?php echo htmlspecialchars($list['dish']); ?></h4>
                  <p class="text-xs theme-muted">Qty: <?php echo (int)$list['qty'];?></p>
                </div>
                <div class="font-medium">₹<?php echo (int)$list['qty']*(float)$list['price'];?></div>
              </div>
            <?php } ?>
          </div>

          <div class="border-t theme-border pt-4 space-y-2 text-sm">
            <div class="flex justify-between"><span>Item Total</span><span>₹<?php echo number_format($subtotal, 2); ?></span></div>
            <div class="flex justify-between"><span>Platform Fee</span><span>+ ₹<?php echo number_format($platform_fee, 2); ?></span></div>
            <div class="flex justify-between"><span>GST (<?php echo number_format($gst_percentage,2); ?>%)</span><span>+ ₹<?php echo number_format($gst_amount, 2); ?></span></div>
            <div class="flex justify-between">
              <span>Delivery Fee <small id="distance_km" class="theme-muted">(Select Address)</small></span>
              <span id="delivery_fee_display" class="font-medium">+ ₹0.00</span>
            </div>
            <hr class="my-2">
            <div class="flex justify-between font-bold text-lg pt-2">
              <span>TO PAY</span>
              <span id="grand_total">₹<?php echo number_format($total_without_delivery, 2); ?></span>
            </div>
          </div>

          <!-- Hidden fields without required attributes -->
          <input type="hidden" name="address" id="final_address">
          <input type="hidden" name="zipcode" id="final_pincode">
          <input type="hidden" name="city" id="final_city">
          <input type="hidden" name="final_subtotal" value="<?php echo $subtotal; ?>">
          <input type="hidden" name="final_gst" value="<?php echo $gst_amount; ?>">
          <input type="hidden" name="final_platform_fee" value="<?php echo $platform_fee; ?>">
          <input type="hidden" id="final_delivery_fee" name="final_delivery_fee" value="0">
          <input type="hidden" id="final_grand_total" name="final_grand_total" value="<?php echo $total_without_delivery; ?>">
          <input type="hidden" id="final_lat" name="dropoff_lat">
          <input type="hidden" id="final_lng" name="dropoff_lng">
          <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>">

          <div class="mt-6">
            <button type="submit" name="place_order" id="placeOrderBtn"
              class="w-full text-center text-white font-bold py-3 px-6 rounded-lg text-lg bg-gray-400 cursor-not-allowed transition-colors" disabled>
              Place Order
            </button>
            <p id="address_error" class="text-red-500 text-xs text-center mt-2">
              Please select or add a delivery address to place the order.
            </p>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- Edit Address Modal -->
<div id="editAddressModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="theme-card p-6 rounded-lg w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
    <h3 class="text-xl font-bold mb-4">Edit Address</h3>
    <div id="editAddressForm">
      <!-- Edit form will be loaded here via AJAX -->
    </div>
    <div class="flex gap-3 mt-4">
      <button type="button" id="updateAddressBtn" class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-lg hover:opacity-90 transition-opacity">
        Update Address
      </button>
      <button type="button" onclick="closeEditModal()" class="theme-btn-secondary px-6 py-2 rounded-lg">
        Cancel
      </button>
    </div>
  </div>
</div>

<!-- Custom Notification -->
<div id="customNotification" class="fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg hidden"></div>

<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_API_KEY; ?>&libraries=places"></script>
<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const placeOrderBtn   = document.getElementById('placeOrderBtn');
    const addressError    = document.getElementById('address_error');
    const addToggle       = document.getElementById('addNewAddressToggle');
    const newAddressForm  = document.getElementById('newAddressForm');
    const walletRadio     = document.getElementById('wallet');
    const saveAddressBtn  = document.getElementById('saveAddressBtn');
    const cancelAddressBtn = document.getElementById('cancelAddressBtn');
    const notification    = document.getElementById('customNotification');

    const subtotal        = <?php echo $subtotal; ?>;
    const platform_fee    = <?php echo $platform_fee; ?>;
    const gst_amount      = <?php echo $gst_amount; ?>;
    const getWalletAmt    = <?php echo $getWalletAmt; ?>;
    const feePerKm        = <?php echo $delivery_fee_per_km; ?>;
    const restaurantLocation = { lat: <?php echo (float)$restaurant_coords['lat']; ?>, lng: <?php echo (float)$restaurant_coords['lng']; ?> };

    let autocomplete;
    let selectedPlace = null;
    let isProcessingPayment = false;

    function disablePlaceOrder(disable) {
        console.log('Setting place order button to:', disable ? 'disabled' : 'enabled');
        
        placeOrderBtn.disabled = !!disable;
        if (disable) {
            placeOrderBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            placeOrderBtn.classList.remove('bg-[var(--primary-color)]', 'hover:opacity-90');
        } else {
            placeOrderBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            placeOrderBtn.classList.add('bg-[var(--primary-color)]', 'hover:opacity-90');
        }
    }

    function showNotification(message, type = 'info') {
        notification.textContent = message;
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.classList.remove('hidden');
        
        setTimeout(() => {
            notification.classList.add('hidden');
        }, 5000);
    }

    function validateAddressBeforeSubmit() {
        const finalAddress = document.getElementById('final_address').value;
        const finalPincode = document.getElementById('final_pincode').value;
        const finalCity = document.getElementById('final_city').value;
        const finalLat = document.getElementById('final_lat').value;
        const finalLng = document.getElementById('final_lng').value;
        
        console.log('Validation Check:', {
            address: finalAddress,
            pincode: finalPincode,
            city: finalCity,
            lat: finalLat,
            lng: finalLng
        });
        
        if (!finalAddress || !finalPincode || !finalCity || !finalLat || !finalLng) {
            console.error('Validation failed: Missing address data');
            return false;
        }
        
        if (!finalPincode.match(/^\d{6}$/)) {
            console.error('Validation failed: Invalid pincode');
            return false;
        }
        
        console.log('Validation passed');
        return true;
    }

    // Toggle new address form
    if (addToggle) {
        addToggle.addEventListener('click', (e) => {
            e.preventDefault();
            newAddressForm.style.display = 'block';
            addToggle.style.display = 'none';
            document.querySelectorAll('.address-radio').forEach(r => r.checked = false);
            disablePlaceOrder(true);
            addressError.style.display = 'block';
            initializeAutocomplete();
        });
    }

    // Cancel new address
    if (cancelAddressBtn) {
        cancelAddressBtn.addEventListener('click', () => {
            newAddressForm.style.display = 'none';
            addToggle.style.display = 'flex';
            resetAddressForm();
        });
    }

    function resetAddressForm() {
        document.getElementById('autocomplete').value = '';
        document.getElementById('flat_no').value = '';
        document.getElementById('landmark').value = '';
        document.getElementById('city').value = '';
        document.getElementById('pincode').value = '';
        document.getElementById('state').value = '';
        document.getElementById('address_type').value = '';
        document.getElementById('custom_address_name').value = '';
        
        // Reset notes
        document.getElementById('city_note').textContent = 'Auto-filled from address';
        document.getElementById('pincode_note').textContent = 'Auto-filled from address';
        document.getElementById('state_note').textContent = 'Auto-filled from address';
        
        // Reset styles
        document.getElementById('city_note').className = 'text-xs mt-1 theme-muted';
        document.getElementById('pincode_note').className = 'text-xs mt-1 theme-muted';
        document.getElementById('state_note').className = 'text-xs mt-1 theme-muted';
        
        // Reset input borders
        document.getElementById('city').classList.remove('border-warning', 'manual-entry');
        document.getElementById('pincode').classList.remove('border-warning', 'manual-entry');
        document.getElementById('state').classList.remove('border-warning', 'manual-entry');
        
        selectedPlace = null;
    }

    function initializeAutocomplete() {
        const acInput = document.getElementById('autocomplete');
        if (acInput && typeof google !== 'undefined' && google.maps?.places?.Autocomplete) {
            autocomplete = new google.maps.places.Autocomplete(acInput, {
                componentRestrictions: { country: 'in' },
                fields: ['address_components', 'geometry', 'formatted_address', 'name']
            });
            
            autocomplete.addListener('place_changed', () => {
                selectedPlace = autocomplete.getPlace();
                if (selectedPlace?.geometry?.location) {
                    parseAddressComponents(selectedPlace);
                }
            });
        }
    }

    function parseAddressComponents(place) {
        let city = '', pincode = '', state = '', area = '';
        let hasPincode = false, hasCity = false, hasState = false;
        
        (place.address_components || []).forEach(component => {
            const types = component.types;
            if (types.includes('locality')) {
                city = component.long_name;
                hasCity = true;
            } else if (types.includes('postal_code')) {
                pincode = component.long_name;
                hasPincode = true;
            } else if (types.includes('administrative_area_level_1')) {
                state = component.long_name;
                hasState = true;
            } else if (types.includes('sublocality') || types.includes('neighborhood')) {
                area = component.long_name;
            }
        });

        // Set values
        document.getElementById('city').value = city;
        document.getElementById('pincode').value = pincode;
        document.getElementById('state').value = state;
        
        // Update notes and styles based on whether data was auto-filled
        updateFieldStatus('city', hasCity, city);
        updateFieldStatus('pincode', hasPincode, pincode);
        updateFieldStatus('state', hasState, state);
        
        // Show warning if pincode is missing
        if (!hasPincode) {
            showNotification('Pincode not found in address. Please enter it manually.', 'warning');
        }
        
        // Auto-suggest address type based on place name
        const placeName = place.name?.toLowerCase() || '';
        if (placeName.includes('home') || placeName.includes('house') || placeName.includes('residence')) {
            document.getElementById('address_type').value = 'Home';
        } else if (placeName.includes('work') || placeName.includes('office') || placeName.includes('company')) {
            document.getElementById('address_type').value = 'Work';
        }
    }

    function updateFieldStatus(fieldName, isAutoFilled, value) {
        const field = document.getElementById(fieldName);
        const note = document.getElementById(fieldName + '_note');
        
        if (isAutoFilled && value) {
            note.textContent = 'Auto-filled from address';
            note.className = 'text-xs mt-1 text-green-600';
            field.classList.remove('border-warning', 'manual-entry');
            field.classList.add('border-success');
        } else {
            note.textContent = 'Please enter manually';
            note.className = 'text-xs mt-1 text-yellow-600';
            field.classList.remove('border-success');
            field.classList.add('border-warning');
        }
    }

    // Make fields editable and track manual entries
    ['city', 'pincode', 'state'].forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field) {
            field.addEventListener('input', () => {
                if (field.value.trim()) {
                    const note = document.getElementById(fieldName + '_note');
                    note.textContent = 'Manually entered';
                    note.className = 'text-xs mt-1 text-blue-600';
                    field.classList.remove('border-warning', 'border-success');
                    field.classList.add('manual-entry');
                }
            });
        }
    });

    // Save new address with enhanced validation
    if (saveAddressBtn) {
        saveAddressBtn.addEventListener('click', async () => {
            const addressType = document.getElementById('address_type').value;
            const customName = document.getElementById('custom_address_name').value;
            const flatNo = document.getElementById('flat_no').value;
            const landmark = document.getElementById('landmark').value;
            const city = document.getElementById('city').value;
            const pincode = document.getElementById('pincode').value;
            const state = document.getElementById('state').value;
            const fullAddress = document.getElementById('autocomplete').value;

            // Manual validation instead of browser required attributes
            const requiredFields = [
                { field: addressType, name: 'Address Type' },
                { field: flatNo, name: 'Flat/House No' },
                { field: city, name: 'City' },
                { field: pincode, name: 'Pincode' },
                { field: state, name: 'State' },
                { field: fullAddress, name: 'Address' }
            ];

            const missingFields = requiredFields.filter(item => !item.field.trim());
            if (missingFields.length > 0) {
                showNotification(`Please fill all required fields: ${missingFields.map(f => f.name).join(', ')}`, 'error');
                return;
            }

            if (!pincode.match(/^\d{6}$/)) {
                showNotification('Please enter a valid 6-digit pincode', 'error');
                return;
            }

            if (!selectedPlace?.geometry) {
                showNotification('Please select a valid address from the suggestions', 'error');
                return;
            }

            // Construct final address
            const finalAddress = `${flatNo}, ${fullAddress}${landmark ? ', Near ' + landmark : ''}`;
            const addressName = customName || addressType;

            const addressData = {
                type: addressName,
                address: finalAddress,
                city: city,
                pincode: pincode,
                state: state,
                lat: selectedPlace.geometry.location.lat(),
                lng: selectedPlace.geometry.location.lng(),
                landmark: landmark,
                flat_no: flatNo
            };

            // Show loading state
            const originalText = saveAddressBtn.innerHTML;
            saveAddressBtn.innerHTML = '<div class="loading-spinner mr-2"></div> Saving...';
            saveAddressBtn.disabled = true;

            try {
                const response = await fetch('<?php echo FRONT_SITE_PATH; ?>add_address.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(addressData)
                });

                const result = await response.json();
                
                if (result.status === 'success') {
                    showNotification('Address saved successfully!', 'success');
                    
                    // Reload the page to show the new address in the list
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    throw new Error(result.message || 'Failed to save address');
                }
            } catch (error) {
                console.error('Error saving address:', error);
                showNotification(error.message, 'error');
            } finally {
                // Reset button state
                saveAddressBtn.innerHTML = originalText;
                saveAddressBtn.disabled = false;
            }
        });
    }

    // Edit address functions
    window.editAddress = function(addressId) {
        fetch('<?php echo FRONT_SITE_PATH; ?>get_address.php?id=' + addressId)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const address = data.address;
                    const editForm = document.getElementById('editAddressForm');
                    editForm.innerHTML = `
                        <input type="hidden" id="edit_address_id" value="${address.id}">
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="block text-sm font-medium theme-muted mb-1">Address Type</label>
                                <select id="edit_address_type" class="theme-input">
                                    <option value="Home" ${address.address_type === 'Home' ? 'selected' : ''}>Home</option>
                                    <option value="Work" ${address.address_type === 'Work' ? 'selected' : ''}>Work</option>
                                    <option value="Other" ${address.address_type === 'Other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium theme-muted mb-1">Custom Name</label>
                                <input type="text" id="edit_custom_name" value="${address.address_type}" class="theme-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium theme-muted mb-1">Address</label>
                                <textarea id="edit_address" class="theme-input h-20">${address.address}</textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium theme-muted mb-1">City</label>
                                    <input type="text" id="edit_city" value="${address.city}" class="theme-input">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium theme-muted mb-1">Pincode</label>
                                    <input type="text" id="edit_pincode" value="${address.pincode}" class="theme-input">
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('editAddressModal').classList.remove('hidden');
                } else {
                    showNotification(data.message || 'Error loading address', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error loading address details', 'error');
            });
    };

    window.closeEditModal = function() {
        document.getElementById('editAddressModal').classList.add('hidden');
    };

    // Set default address
    window.setDefaultAddress = function(addressId) {
        fetch('<?php echo FRONT_SITE_PATH; ?>set_default_address.php?id=' + addressId)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('Default address updated!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Error updating default address', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error updating default address', 'error');
            });
    };

    // Called from radio onclick on saved address
    window.selectSavedAddress = function(radioEl){
        console.log('Address selected:', radioEl.dataset.address);
        
        newAddressForm.style.display = 'none';
        addToggle.style.display = 'flex';
        const coords = {
            lat: parseFloat(radioEl.dataset.lat),
            lng: parseFloat(radioEl.dataset.lng)
        };
        
        console.log('Coordinates:', coords);
        
        const placeDetails = {
            formatted_address: radioEl.dataset.address,
            address_components: [
                { long_name: radioEl.dataset.city,    types: ['locality'] },
                { long_name: radioEl.dataset.pincode, types: ['postal_code'] },
                { long_name: radioEl.dataset.state,   types: ['administrative_area_level_1'] }
            ]
        };
        
        calculateAndDisplayBill(coords, placeDetails);
    };

    function calculateAndDisplayBill(customerCoords, placeDetails) {
        console.log('Calculating bill for:', customerCoords);
        
        disablePlaceOrder(true);

        // Set hidden fields
        document.getElementById('final_lat').value = customerCoords.lat;
        document.getElementById('final_lng').value = customerCoords.lng;
        document.getElementById('final_address').value = placeDetails.formatted_address || '';

        let city = '', pincode = '', state = '';
        (placeDetails.address_components || []).forEach(c => {
            if (c.types[0] === 'locality')    city = c.long_name || city;
            if (c.types[0] === 'postal_code') pincode = c.long_name || pincode;
            if (c.types[0] === 'administrative_area_level_1') state = c.long_name || state;
        });
        
        document.getElementById('final_city').value = city;
        document.getElementById('final_pincode').value = pincode;

        console.log('Final values - City:', city, 'Pincode:', pincode);

        // Check if Google Maps is available
        if (typeof google === 'undefined' || !google.maps) {
            console.error('Google Maps not loaded');
            showNotification('Map service not available. Using default delivery fee.', 'warning');
            
            // Use default delivery fee if maps not available
            const deliveryFee = 40;
            const grandTotal = subtotal + platform_fee + gst_amount + deliveryFee;
            
            updateOrderSummary('5 km', deliveryFee, grandTotal);
            return;
        }

        const svc = new google.maps.DistanceMatrixService();
        svc.getDistanceMatrix({
            origins: [restaurantLocation],
            destinations: [customerCoords],
            travelMode: 'DRIVING',
        }, (resp, status) => {
            console.log('Distance Matrix Status:', status);
            
            if (status === 'OK' && resp.rows?.[0]?.elements?.[0]?.status === 'OK') {
                const el = resp.rows[0].elements[0];
                const distanceText = el.distance.text;
                const distanceInKm = el.distance.value / 1000;
                const deliveryFee = Math.max(20, distanceInKm * feePerKm);
                const grandTotal = subtotal + platform_fee + gst_amount + deliveryFee;

                updateOrderSummary(distanceText, deliveryFee, grandTotal);
                
            } else {
                console.error('Distance calculation failed:', status, resp);
                // Fallback to default delivery fee
                const deliveryFee = 40;
                const grandTotal = subtotal + platform_fee + gst_amount + deliveryFee;
                
                updateOrderSummary('5 km (estimated)', deliveryFee, grandTotal);
                showNotification('Using estimated delivery fee', 'info');
            }
        });
    }

    function updateOrderSummary(distanceText, deliveryFee, grandTotal) {
        document.getElementById('distance_km').innerText = `(${distanceText})`;
        document.getElementById('delivery_fee_display').innerText = `+ ₹${deliveryFee.toFixed(2)}`;
        document.getElementById('grand_total').innerText = `₹${grandTotal.toFixed(2)}`;
        document.getElementById('final_delivery_fee').value = deliveryFee.toFixed(2);
        document.getElementById('final_grand_total').value = grandTotal.toFixed(2);

        // Enable place order button only if validation passes
        const isValid = validateAddressBeforeSubmit();
        if (isValid) {
            addressError.style.display = 'none';
            disablePlaceOrder(false);
            console.log('Order summary updated. Place order enabled.');
        } else {
            console.log('Order summary updated but validation failed.');
        }
    }

    // Form submission handler
    document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
        const payType = document.querySelector('input[name="payment_type"]:checked')?.value || 'cod';
        
        // For online payment, handle via AJAX
        if (payType === 'online') {
            e.preventDefault();
            
            // Prevent multiple clicks
            if (isProcessingPayment) {
                showNotification('Payment is already being processed...', 'warning');
                return;
            }
            
            isProcessingPayment = true;
            
            // Disable place order button
            const originalText = placeOrderBtn.innerHTML;
            placeOrderBtn.innerHTML = '<div class="loading-spinner mr-2"></div> Processing...';
            placeOrderBtn.disabled = true;

            try {
                // Validate address before proceeding with online payment
                if (!validateAddressBeforeSubmit()) {
                    throw new Error('Please select or add a valid delivery address first.');
                }
                
                const amount = parseFloat(document.getElementById('final_grand_total').value || '0');
                if (!(amount > 0)) { 
                    throw new Error('Invalid order amount'); 
                }

                // STEP 1: Persist checkout data to session
                const fd = new FormData(document.getElementById('checkoutForm'));
                const psRes = await fetch("<?php echo FRONT_SITE_PATH; ?>cashfree/persist_checkout.php", {
                    method:'POST',
                    body: fd
                });
                const ps = await psRes.json();
                if (!ps || ps.success !== true) {
                    throw new Error('Could not prepare your order. Please try again.');
                }

                // STEP 2: Create Cashfree order
                const coRes = await fetch("<?php echo FRONT_SITE_PATH; ?>cashfree/createorder.php", {
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({
                        amount: amount,
                        customer_id: <?php echo $uid; ?>,
                        purpose: 'order',
                        order_note: 'Food Order Payment'
                    })
                });
                const cj = await coRes.json();
                if (!cj || cj.success !== true) {
                    throw new Error(cj.message || 'Failed to connect to payment gateway');
                }

                // STEP 3: Redirect to Cashfree
                if (cj.payment_link) {
                    window.location.href = cj.payment_link;
                } else if (cj.payment_session_id) {
                    const cashfree = Cashfree({ mode: "<?php echo CASHFREE_ENVIRONMENT; ?>" });
                    await cashfree.checkout({ 
                        paymentSessionId: cj.payment_session_id, 
                        redirectTarget: "_self" 
                    });
                } else {
                    throw new Error('Unexpected response from payment gateway');
                }
            } catch (err) {
                console.error(err);
                showNotification(err.message || 'A network error occurred while starting the payment process.', 'error');
            } finally {
                // Re-enable button
                isProcessingPayment = false;
                placeOrderBtn.innerHTML = originalText;
                placeOrderBtn.disabled = false;
            }
        } else {
            // For COD/Wallet payments, validate before allowing form submission
            if (!validateAddressBeforeSubmit()) {
                e.preventDefault();
                showNotification('Please select or add a valid delivery address first.', 'error');
                return;
            }
            
            // Additional validation for COD/Wallet
            const amount = parseFloat(document.getElementById('final_grand_total').value || '0');
            if (!(amount > 0)) {
                e.preventDefault();
                showNotification('Invalid order amount. Please refresh the page and try again.', 'error');
                return;
            }
            
            // If validation passes, allow form submission to proceed naturally
            console.log('Form submission proceeding for COD/Wallet');
        }
    });

    // Auto-select default address if available
    const defaultAddress = document.querySelector('.address-radio[checked]');
    if (defaultAddress) {
        console.log('Default address found, auto-selecting...');
        defaultAddress.click();
    } else {
        console.log('No default address found');
    }
});
</script>

<?php include("footer.php"); ?>