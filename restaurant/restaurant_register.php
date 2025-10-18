<?php
// This is a self-contained page, it does not use the main header.php
// Start Session and include core files directly.
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// [FIXED] Corrected the path to go up one directory
include_once('../database.inc.php');
include_once('../function.inc.php');
include_once('../constant.inc.php');

$msg = "";
$error = "";

if(isset($_POST['submit'])){
    // Sanitize input
    $name = get_safe_value($_POST['name']);
    $owner_name = get_safe_value($_POST['owner_name']);
    $email = get_safe_value($_POST['email']);
    $mobile = get_safe_value($_POST['mobile']);
    $username = get_safe_value($_POST['username']); // NEW: Username field
    $password = get_safe_value($_POST['password']);
    $address = get_safe_value($_POST['address']);
    
    // Validate username (alphanumeric, 3-20 characters)
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
    } else {
        // Check for existing email, mobile, or username
        $stmt_check = mysqli_prepare($con, "SELECT id FROM restaurants WHERE email = ? OR phone = ? OR username = ?");
        mysqli_stmt_bind_param($stmt_check, "sss", $email, $mobile, $username);
        mysqli_stmt_execute($stmt_check);
        if(mysqli_stmt_get_result($stmt_check)->num_rows > 0){
            $error = "A restaurant with this email, mobile number, or username already exists.";
        } else {
            // Handle file upload securely
            if(isset($_FILES['fssai_certificate']) && $_FILES['fssai_certificate']['error'] == 0){
                $fssai_file = $_FILES['fssai_certificate'];
                $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
                if(in_array($fssai_file['type'], $allowed_types) && $fssai_file['size'] < 5000000){ // 5MB limit
                    
                    $file_ext = pathinfo($fssai_file['name'], PATHINFO_EXTENSION);
                    $file_name = 'fssai_' . time() . '.' . $file_ext;
                    $file_path = SERVER_RESTAURANT_IMAGE . $file_name;
                    
                    if(move_uploaded_file($fssai_file['tmp_name'], $file_path)){
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // NEW: Added username to the SQL query
                        $sql = "INSERT INTO restaurants (name, owner_name, email, phone, username, password, address, fssai_certificate, status, is_open) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";
                        $stmt = mysqli_prepare($con, $sql);
                        mysqli_stmt_bind_param($stmt, "ssssssss", $name, $owner_name, $email, $mobile, $username, $hashed_password, $address, $file_name);
                        
                        if(mysqli_stmt_execute($stmt)){
                            $msg = "Registration successful! Your application is under review. Our team will contact you within 24-48 hours.";
                        } else {
                            $error = "Database error. Please try again.";
                            log_custom_error("DB_WRITE", "Restaurant registration failed: " . mysqli_stmt_error($stmt), __FILE__, __LINE__);
                        }
                    } else {
                        $error = "Failed to upload certificate. Please check permissions and try again.";
                    }
                } else {
                    $error = "Invalid file type or size. Please upload a PDF, JPG, or PNG file under 5MB.";
                }
            } else {
                $error = "FSSAI certificate is required.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner with ServeDoor</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
      :root {
        --primary-color: #ea2a33;
        --secondary-color: #f8f9fa;
        --text-primary: #1b0e0e;
        --text-secondary: #6c757d;
        --border-color: #dee2e6;
      }
      .theme-input { @apply w-full p-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500; }
      .username-available { @apply border-green-500 bg-green-50; }
      .username-taken { @apply border-red-500 bg-red-50; }
      .username-checking { @apply border-yellow-500 bg-yellow-50; }
    </style>
</head>
<body class="bg-[var(--secondary-color)]">

<header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <a href="<?php echo FRONT_SITE_PATH; ?>" class="text-2xl font-bold text-[var(--text-primary)]">ServeDoor</a>
        <a href="<?php echo FRONT_SITE_PATH; ?>shop.php" class="text-sm font-medium text-[var(--primary-color)] hover:underline">Back to Shop</a>
    </div>
</header>

<main>
    <section class="relative py-20 md:py-28 bg-gray-800 text-white text-center" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=2074&auto=format&fit=crop'); background-size: cover; background-position: center;">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">Grow Your Restaurant Business</h1>
            <p class="mt-4 text-lg md:text-xl text-gray-300 max-w-3xl mx-auto">Join ServeDoor and reach thousands of new customers in your city.</p>
        </div>
    </section>

    <section class="py-16 md:py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Why Partner With Us?</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="text-center p-6"><span class="material-symbols-outlined text-5xl text-[var(--primary-color)]">groups</span><h3 class="mt-4 text-xl font-bold">Wider Customer Reach</h3><p class="mt-2 text-gray-600">Connect with a large and growing community of food lovers.</p></div>
                <div class="text-center p-6"><span class="material-symbols-outlined text-5xl text-[var(--primary-color)]">trending_up</span><h3 class="mt-4 text-xl font-bold">Increased Orders</h3><p class="mt-2 text-gray-600">Boost your sales with our established delivery network.</p></div>
                <div class="text-center p-6"><span class="material-symbols-outlined text-5xl text-[var(--primary-color)]">smartphone</span><h3 class="mt-4 text-xl font-bold">Easy-to-Use Platform</h3><p class="mt-2 text-gray-600">Manage your menu and orders from a simple dashboard.</p></div>
            </div>
        </div>
    </section>

    <section id="register-form" class="py-16 md:py-20 bg-[var(--secondary-color)]">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-12">
                     <h2 class="text-3xl md:text-4xl font-bold">Join Us Today</h2>
                     <p class="mt-3 text-gray-600">Fill out the form below to start your journey with ServeDoor.</p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl shadow-xl">
                    <?php if($msg != ""): ?>
                        <div class="mb-6 p-4 text-sm text-green-700 bg-green-100 rounded-lg text-center">
                            <p class="font-bold">Thank You!</p>
                            <?php echo htmlspecialchars($msg); ?>
                        </div>
                    <?php endif; ?>
                    <?php if($error != ""): ?>
                        <div class="mb-6 p-4 text-sm text-red-700 bg-red-100 rounded-lg text-center">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Restaurant Name*</label>
                            <input type="text" name="name" class="theme-input" required>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Owner's Full Name*</label>
                                <input type="text" name="owner_name" class="theme-input" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Mobile Number*</label>
                                <input type="tel" name="mobile" class="theme-input" required pattern="[0-9]{10}" maxlength="10">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email Address*</label>
                                <input type="email" name="email" class="theme-input" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username*</label>
                                <input type="text" name="username" id="username" class="theme-input" required 
                                       pattern="[a-zA-Z0-9_]{3,20}" 
                                       title="3-20 characters, letters, numbers, and underscores only">
                                <div id="username-feedback" class="text-xs mt-1"></div>
                                <small class="text-xs text-gray-500">3-20 characters, letters, numbers, and underscores only.</small>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Create Password*</label>
                                <input type="password" name="password" class="theme-input" required minlength="8">
                                <small class="text-xs text-gray-500">Minimum 8 characters.</small>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password*</label>
                                <input type="password" name="confirm_password" class="theme-input" required>
                                <div id="password-match" class="text-xs mt-1"></div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Restaurant Address*</label>
                            <textarea name="address" rows="3" class="theme-input" required></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">FSSAI Certificate (PDF/JPG/PNG)*</label>
                            <input type="file" name="fssai_certificate" class="theme-input file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100" accept="image/jpeg,image/png,application/pdf" required>
                            <small class="text-xs text-gray-500">Max file size: 5MB</small>
                        </div>
                        
                        <button type="submit" name="submit" class="w-full text-white font-bold py-4 px-6 rounded-lg text-lg" style="background:var(--primary-color)">Submit Application</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Username availability check
document.getElementById('username').addEventListener('input', function() {
    const username = this.value;
    const feedback = document.getElementById('username-feedback');
    
    if (username.length < 3) {
        this.classList.remove('username-available', 'username-taken', 'username-checking');
        feedback.textContent = '';
        return;
    }
    
    // Validate pattern
    if (!/^[a-zA-Z0-9_]{3,20}$/.test(username)) {
        this.classList.add('username-taken');
        feedback.textContent = 'Invalid username format';
        feedback.className = 'text-xs mt-1 text-red-600';
        return;
    }
    
    this.classList.add('username-checking');
    feedback.textContent = 'Checking availability...';
    feedback.className = 'text-xs mt-1 text-yellow-600';
    
    // Check username availability
    fetch('check_username.php?username=' + encodeURIComponent(username))
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                this.classList.remove('username-checking', 'username-taken');
                this.classList.add('username-available');
                feedback.textContent = 'Username is available';
                feedback.className = 'text-xs mt-1 text-green-600';
            } else {
                this.classList.remove('username-checking', 'username-available');
                this.classList.add('username-taken');
                feedback.textContent = 'Username is already taken';
                feedback.className = 'text-xs mt-1 text-red-600';
            }
        })
        .catch(error => {
            this.classList.remove('username-checking');
            feedback.textContent = 'Error checking username';
            feedback.className = 'text-xs mt-1 text-red-600';
        });
});

// Password match validation
document.querySelector('input[name="password"]').addEventListener('input', checkPasswordMatch);
document.querySelector('input[name="confirm_password"]').addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    const matchDiv = document.getElementById('password-match');
    
    if (confirmPassword === '') {
        matchDiv.textContent = '';
        return;
    }
    
    if (password === confirmPassword) {
        matchDiv.textContent = 'Passwords match';
        matchDiv.className = 'text-xs mt-1 text-green-600';
    } else {
        matchDiv.textContent = 'Passwords do not match';
        matchDiv.className = 'text-xs mt-1 text-red-600';
    }
}
</script>

<?php include('../footer.php'); ?>
</body>
</html>