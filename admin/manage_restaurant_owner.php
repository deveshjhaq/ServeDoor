<?php
// --- Include top.php ---
// It's assumed that top.php already handles session_start() and the database connection.
include('top.php');

// --- Initialize variables with empty values ---
$msg = "";
$id = "";
$name = "";
$slug = "";
$username = "";
$phone = "";
$email = "";
$address = "";
$city = "";
$pincode = "";
$open_time = "";
$close_time = "";
$min_order_amount = "0.00";
$is_open = 1;
$status = 1;

// --- Load existing owner data (if in edit mode) ---
if (isset($_GET['id']) && $_GET['id'] > 0) {
    $id = get_safe_value($_GET['id']);
    $stmt = mysqli_prepare($con, "SELECT * FROM restaurants WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $name = $row['name'];
        $slug = $row['slug'];
        $username = $row['username'];
        $phone = $row['phone'];
        $email = $row['email'];
        $address = $row['address'];
        $city = $row['city'];
        $pincode = $row['pincode'];
        $open_time = $row['open_time'];
        $close_time = $row['close_time'];
        $min_order_amount = $row['min_order_amount'];
        $is_open = $row['is_open'];
        $status = $row['status'];
    }
}

// --- Handle form submission ---
if (isset($_POST['submit'])) {
    // Sanitize all input values
    $name = get_safe_value($_POST['name']);
    $slug = get_safe_value($_POST['slug']);
    $username = get_safe_value($_POST['username']);
    $password = get_safe_value($_POST['password']);
    $phone = get_safe_value($_POST['phone']);
    $email = get_safe_value($_POST['email']);
    $address = get_safe_value($_POST['address']);
    $city = get_safe_value($_POST['city']);
    $pincode = get_safe_value($_POST['pincode']);
    $open_time = get_safe_value($_POST['open_time']);
    $close_time = get_safe_value($_POST['close_time']);
    $min_order_amount = get_safe_value($_POST['min_order_amount']);
    $is_open = isset($_POST['is_open']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Use Prepared Statements
    if ($id == '') {
        // Add a new restaurant owner
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($con, "INSERT INTO restaurants (name, slug, username, password, phone, email, address, city, pincode, open_time, close_time, min_order_amount, is_open, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssssssssdii", $name, $slug, $username, $hashed_password, $phone, $email, $address, $city, $pincode, $open_time, $close_time, $min_order_amount, $is_open, $status);
        mysqli_stmt_execute($stmt);
        redirect('restaurant_owner.php');
    } else {
        // Update existing restaurant owner
        if ($password != '') {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($con, "UPDATE restaurants SET name=?, slug=?, username=?, password=?, phone=?, email=?, address=?, city=?, pincode=?, open_time=?, close_time=?, min_order_amount=?, is_open=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssssssssdisii", $name, $slug, $username, $hashed_password, $phone, $email, $address, $city, $pincode, $open_time, $close_time, $min_order_amount, $is_open, $status, $id);
        } else {
            // If the password is blank, do not update it
            $stmt = mysqli_prepare($con, "UPDATE restaurants SET name=?, slug=?, username=?, phone=?, email=?, address=?, city=?, pincode=?, open_time=?, close_time=?, min_order_amount=?, is_open=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssssssssdisii", $name, $slug, $username, $phone, $email, $address, $city, $pincode, $open_time, $close_time, $min_order_amount, $is_open, $status, $id);
        }
        mysqli_stmt_execute($stmt);
        redirect('restaurant_owner.php');
    }
}
?>

<div class="row">
    <div class="col-12">
        <h4 class="card-title"><?php echo ($id != '') ? 'Edit Restaurant Owner' : 'Add New Restaurant Owner'; ?></h4>
        <p class="card-description" style="color:red;"><?php echo htmlspecialchars($msg); ?></p>
    </div>
</div>

<form method="post">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Restaurant Details</h5>
                    <div class="form-group">
                        <label for="name">Restaurant Name*</label>
                        <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($name); ?>">
                    </div>
                    <div class="form-group">
                        <label for="slug">URL Slug*</label>
                        <input type="text" class="form-control" id="slug" name="slug" required value="<?php echo htmlspecialchars($slug); ?>">
                        <small class="form-text text-muted">This will appear in the URL (e.g., yoursite.com/restaurant/<b><?php echo htmlspecialchars($slug ?: 'restaurant-name'); ?></b>)</small>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Contact & Address</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Street Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                             <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pincode">Pincode</label>
                                <input type="text" class="form-control" id="pincode" name="pincode" value="<?php echo htmlspecialchars($pincode); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

             <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Owner's Login Details</h5>
                     <div class="form-group">
                        <label for="username">Username*</label>
                        <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($username); ?>">
                    </div>
                     <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo ($id == '') ? 'required' : ''; ?>>
                        <small class="form-text text-muted">
                            <?php echo ($id != '') ? 'Leave blank if you do not want to change the password.' : 'Create a strong password with at least 8 characters.'; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Settings</h5>
                    <div class="form-group">
                        <label for="open_time">Opening Time</label>
                        <input type="text" class="form-control" id="open_time" name="open_time" placeholder="e.g., 10:00 AM" value="<?php echo htmlspecialchars($open_time); ?>">
                    </div>
                    <div class="form-group">
                        <label for="close_time">Closing Time</label>
                        <input type="text" class="form-control" id="close_time" name="close_time" placeholder="e.g., 11:00 PM" value="<?php echo htmlspecialchars($close_time); ?>">
                    </div>
                    <div class="form-group">
                        <label for="min_order_amount">Minimum Order Amount</label>
                        <input type="number" step="0.01" class="form-control" id="min_order_amount" name="min_order_amount" value="<?php echo htmlspecialchars($min_order_amount); ?>">
                    </div>
                     <div class="form-check form-check-flat form-check-primary">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="is_open" <?php echo ($is_open) ? 'checked' : ''; ?>> Restaurant is Open
                        <i class="input-helper"></i></label>
                    </div>
                     <div class="form-check form-check-flat form-check-primary">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="status" <?php echo ($status) ? 'checked' : ''; ?>> Active Status
                        <i class="input-helper"></i></label>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                     <button type="submit" name="submit" class="btn btn-primary btn-block">Save Changes</button>
                     <a href="restaurant_owner.php" class="btn btn-light btn-block mt-2">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    if (nameInput && slugInput) {
        nameInput.addEventListener('keyup', function() {
            const nameValue = nameInput.value.trim();
            slugInput.value = generateSlug(nameValue);
        });
    }

    function generateSlug(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
    }
});
</script>

<?php include('footer.php'); ?>