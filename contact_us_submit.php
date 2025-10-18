<?php
// We need all includes for this to work
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include('database.inc.php');
include('function.inc.php');
include('constant.inc.php');

$is_error = false;
$error_message = 'Something went wrong. Please try again.';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get form data
    $name = get_safe_value($_POST['name']);
    $email = get_safe_value($_POST['email']);
    $mobile = get_safe_value($_POST['mobile']);
    $subject = get_safe_value($_POST['subject']);
    $message = get_safe_value($_POST['message']);
    $added_on = date('Y-m-d H:i:s');

    // --- 1. SECURELY SAVE TO DATABASE (So it shows in Admin Panel) ---
    $sql = "INSERT INTO contact_us(name, email, mobile, subject, message, added_on) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $mobile, $subject, $message, $added_on);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $is_error = true;
    }

    // --- 2. SEND EMAIL NOTIFICATION ---
    if (!$is_error) {
    $to = "support@obfuscated.com";
        $email_subject = "New Contact Message: " . $subject;
        $body = "You have a new message from the Servedoor contact form.\n\n";
        $body .= "Name: " . $name . "\n";
        $body .= "Email: " . $email . "\n";
        $body .= "Mobile: " . $mobile . "\n\n";
        $body .= "Message:\n" . $message . "\n";
        $headers = "From: no-reply@servedoor.com" . "\r\n" . "Reply-To: " . $email;

        // Try to send the email
        if (!mail($to, $email_subject, $body, $headers)) {
            // This part is not critical, even if email fails, message is saved.
            // You can log this error if you want.
        }
    }

} else {
    redirect(FRONT_SITE_PATH.'contact-us');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Submission - Servedoor</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; margin: 0; padding: 20px;}
        .container { background-color: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #28a745; font-size: 36px; }
        p { font-size: 18px; color: #555; }
        .btn { display: inline-block; margin-top: 25px; padding: 12px 25px; font-size: 16px; font-weight: bold; border-radius: 25px; background-color: #333; color: white; text-decoration: none; cursor: pointer; transition: background-color 0.3s; }
        .btn:hover { background-color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($is_error): ?>
            <h1 style="color:#dc3545;">Oops!</h1>
            <p><?php echo $error_message; ?></p>
            <a href="<?php echo FRONT_SITE_PATH; ?>contact-us" class="btn">Try Again</a>
        <?php else: ?>
            <h1>Thank You!</h1>
            <p>Your message has been sent successfully. Our team will get back to you shortly.</p>
            <a href="<?php echo FRONT_SITE_PATH; ?>shop" class="btn">Back to Shop</a>
        <?php endif; ?>
    </div>
</body>
</html>