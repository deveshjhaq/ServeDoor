<?php
include('top.php');
$msg = "";

// [SECURE] Handle form submission with a prepared statement
if (isset($_POST['submit'])) {
    // Sanitize all POST data
    $cart_min_price = get_safe_value($_POST['cart_min_price']);
    $cart_min_price_msg = get_safe_value($_POST['cart_min_price_msg']);
    $website_close = get_safe_value($_POST['website_close']);
    $website_close_msg = get_safe_value($_POST['website_close_msg']);
    $wallet_amt = get_safe_value($_POST['wallet_amt']);
    $referral_amt = get_safe_value($_POST['referral_amt']);
    $gst_percentage = get_safe_value($_POST['gst_percentage']);
    $delivery_fee_per_km = get_safe_value($_POST['delivery_fee_per_km']);
    $platform_fee = get_safe_value($_POST['platform_fee']);

    $sql = "UPDATE setting SET 
                cart_min_price=?, cart_min_price_msg=?, website_close=?, 
                website_close_msg=?, wallet_amt=?, referral_amt=?, 
                gst_percentage=?, delivery_fee_per_km=?, platform_fee=? 
            WHERE id=1";
            
    $stmt = mysqli_prepare($con, $sql);

    // [FIXED] The types string now has 9 characters to match the 9 variables.
    mysqli_stmt_bind_param($stmt, "dsisiiddd", 
        $cart_min_price, $cart_min_price_msg, $website_close, 
        $website_close_msg, $wallet_amt, $referral_amt, 
        $gst_percentage, $delivery_fee_per_km, $platform_fee
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $msg = "Settings Updated Successfully!";
    } else {
        $msg = "Error: Could not update settings.";
    }
}

// Fetch all settings to display in the form
$row = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM setting WHERE id='1'"));
$cart_min_price = $row['cart_min_price'];
$cart_min_price_msg = $row['cart_min_price_msg'];
$website_close = $row['website_close'];
$website_close_msg = $row['website_close_msg'];
$wallet_amt = $row['wallet_amt'];
$referral_amt = $row['referral_amt'];
$gst_percentage = $row['gst_percentage'];
$delivery_fee_per_km = $row['delivery_fee_per_km'];
$platform_fee = $row['platform_fee'];

$websiteCloseArr = array('No', 'Yes');
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Website Settings</h4>
                <p class="card-description">Manage all sitewide settings, fees, and taxes from here.</p>
                
                <?php if ($msg != ""): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>

                <form class="forms-sample" method="post">
                    
                    <h5 class="mt-4 text-primary">Order Settings</h5>
                    <div class="form-group">
                        <label for="cart_min_price">Cart Minimum Price (₹)</label>
                        <input type="number" step="0.01" class="form-control" name="cart_min_price" required value="<?php echo htmlspecialchars($cart_min_price); ?>">
                    </div>
                    <div class="form-group">
                        <label for="cart_min_price_msg">Cart Minimum Price Message</label>
                        <input type="text" class="form-control" name="cart_min_price_msg" value="<?php echo htmlspecialchars($cart_min_price_msg); ?>">
                    </div>

                    <h5 class="mt-4 text-primary">Fee & Tax Settings</h5>
                    <div class="form-group">
                        <label for="gst_percentage">GST Percentage (%)</label>
                        <input type="number" step="0.01" class="form-control" name="gst_percentage" required value="<?php echo htmlspecialchars($gst_percentage); ?>">
                        <small class="form-text text-muted">Example: For 5%, enter 5.00.</small>
                    </div>
                    <div class="form-group">
                        <label for="delivery_fee_per_km">Delivery Fee per KM (₹)</label>
                        <input type="number" step="0.01" class="form-control" name="delivery_fee_per_km" required value="<?php echo htmlspecialchars($delivery_fee_per_km); ?>">
                        <small class="form-text text-muted">The charge for each kilometer.</small>
                    </div>
                    <div class="form-group">
                        <label for="platform_fee">Platform Fee (₹)</label>
                        <input type="number" step="0.01" class="form-control" name="platform_fee" required value="<?php echo htmlspecialchars($platform_fee); ?>">
                        <small class="form-text text-muted">A flat fee added to every order.</small>
                    </div>

                    <h5 class="mt-4 text-primary">User Settings</h5>
                     <div class="form-group">
                        <label for="wallet_amt">Sign Up Wallet Amount (₹)</label>
                        <input type="number" class="form-control" name="wallet_amt" value="<?php echo htmlspecialchars($wallet_amt); ?>">
                         <small class="form-text text-muted">Amount for new user's wallet on sign up.</small>
                    </div>
                    <div class="form-group">
                        <label for="referral_amt">Referral Amount (₹)</label>
                        <input type="number" class="form-control" name="referral_amt" value="<?php echo htmlspecialchars($referral_amt); ?>">
                        <small class="form-text text-muted">Amount for successful referrals.</small>
                    </div>

                    <h5 class="mt-4 text-primary">Website Status</h5>
                    <div class="form-group">
                        <label for="website_close">Close Website for Orders</label>
                        <select name="website_close" class="form-control">
                            <option value="">Select Option</option>
                            <?php foreach ($websiteCloseArr as $key => $val) {
                                $selected = ($website_close == $key) ? 'selected' : '';
                                echo "<option value='$key' $selected>$val</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="website_close_msg">Website Close Message</label>
                        <input type="text" class="form-control" name="website_close_msg" value="<?php echo htmlspecialchars($website_close_msg); ?>">
                    </div>
                    
                    <button type="submit" name="submit" class="btn btn-primary mr-2">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>