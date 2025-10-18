<?php
include('header.php');

// Redirect user if they are not logged in
if(!isset($_SESSION['FOOD_USER_ID'])){
    redirect(FRONT_SITE_PATH.'login_register');
    exit;
}

$uid = $_SESSION['FOOD_USER_ID'];
$user_details = [];
$profile_msg = '';

// Handle Profile Update
if(isset($_POST['update_profile'])){
    $name = get_safe_value($_POST['name']);
    $email = get_safe_value($_POST['email']);
    $mobile = get_safe_value($_POST['mobile']);
    $dob = get_safe_value($_POST['dob']);
    $gender = get_safe_value($_POST['gender']);
    
    $stmt_update = mysqli_prepare($con, "UPDATE user SET name=?, email=?, mobile=?, dob=?, gender=? WHERE id=?");
    mysqli_stmt_bind_param($stmt_update, "sssssi", $name, $email, $mobile, $dob, $gender, $uid);
    if(mysqli_stmt_execute($stmt_update)){
        $_SESSION['FOOD_USER_NAME'] = $name; // Update session name
        $profile_msg = "Profile Updated Successfully!";
    } else {
        $profile_msg = "Something went wrong. Please try again.";
    }
    mysqli_stmt_close($stmt_update);
}

// Fetch current user details
$stmt_user = mysqli_prepare($con, "SELECT name, email, mobile, dob, gender FROM user WHERE id=?");
mysqli_stmt_bind_param($stmt_user, "i", $uid);
mysqli_stmt_execute($stmt_user);
$res_user = mysqli_stmt_get_result($stmt_user);
if(mysqli_num_rows($res_user) > 0){
    $user_details = mysqli_fetch_assoc($res_user);
}
mysqli_stmt_close($stmt_user);

?>

<div class="container mx-auto px-4 py-8 md:py-12">
    <h1 class="text-3xl font-bold mb-8 border-b theme-border pb-3">My Profile</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
            <div class="theme-card p-6 rounded-lg shadow-lg text-center">
                <img src="<?php echo FRONT_SITE_PATH;?>assets/img/user.png" class="w-24 h-24 rounded-full mx-auto border-2 border-[var(--primary-color)]">
                <h2 class="text-2xl font-bold mt-4"><?php echo htmlspecialchars($user_details['name']); ?></h2>
                <p class="theme-muted"><?php echo htmlspecialchars($user_details['email']); ?></p>
            </div>
            <div class="theme-card p-6 rounded-lg shadow-lg text-center mt-8">
                <h3 class="text-xl font-bold">Wallet Balance</h3>
                <p class="text-4xl font-bold mt-2" style="color:var(--primary-color);"><?php echo (int)$getWalletAmt; ?> Rs</p>
                <a href="wallet.php" class="inline-block text-center mt-4 w-full text-white font-bold py-2 px-6 rounded-lg" style="background:var(--primary-color)">Manage Wallet</a>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="theme-card p-6 sm:p-8 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold mb-6">Edit Personal Information</h2>
                
                <?php if($profile_msg != ''): ?>
                    <div class="mb-4 p-3 rounded-lg bg-green-200 text-green-800 text-sm"><?php echo $profile_msg; ?></div>
                <?php endif; ?>

                <form method="post" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium theme-muted mb-1">Full Name</label>
                            <input type="text" name="name" class="theme-input" value="<?php echo htmlspecialchars($user_details['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium theme-muted mb-1">Email</label>
                            <input type="email" name="email" class="theme-input" value="<?php echo htmlspecialchars($user_details['email'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium theme-muted mb-1">Mobile</label>
                            <input type="text" name="mobile" class="theme-input" value="<?php echo htmlspecialchars($user_details['mobile'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium theme-muted mb-1">Date of Birth</label>
                            <input type="date" name="dob" class="theme-input" value="<?php echo htmlspecialchars($user_details['dob'] ?? ''); ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium theme-muted mb-1">Gender</label>
                        <div class="flex items-center gap-6 mt-2">
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="Male" class="accent-[var(--primary-color)]" <?php if(isset($user_details['gender']) && $user_details['gender'] == 'Male') echo 'checked'; ?>>
                                <span class="ml-2">Male</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="Female" class="accent-[var(--primary-color)]" <?php if(isset($user_details['gender']) && $user_details['gender'] == 'Female') echo 'checked'; ?>>
                                <span class="ml-2">Female</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="Other" class="accent-[var(--primary-color)]" <?php if(isset($user_details['gender']) && $user_details['gender'] == 'Other') echo 'checked'; ?>>
                                <span class="ml-2">Other</span>
                            </label>
                        </div>
                    </div>
                    <div class="border-t theme-border pt-6">
                        <button type="submit" name="update_profile" class="text-white font-bold py-3 px-8 rounded-lg" style="background:var(--primary-color)">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include('footer.php');
?>