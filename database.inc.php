<?php
/**
 * Master Bootstrap File for ServeDoor
 * This file should be included FIRST on every page.
 * It handles: Composer Autoloading, Error Logging, Secure Sessions, and Database Connection.
 */

// [IMPORTANT] STEP 1: Load the Composer autoloader
// This will automatically load PHPMailer and any other libraries you install.
// This MUST be at the very top.
require_once __DIR__ . '/libs/PHPMailer/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/SMTP.php';


// [STEP 2] Custom Error Handler to log all PHP errors to the database
function log_php_error($errno, $errstr, $errfile, $errline) {
    // These are the credentials needed for the error handler.
    // Use your new, secure password here as well.
    $db_host = 'localhost';
    $db_user = 'DB_USER_PLACEHOLDER';
    $db_pass = 'DB_PASS_PLACEHOLDER'; // TODO: Use your secure password
    $db_name = 'DB_NAME_PLACEHOLDER';

    $err_con = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$err_con) { return true; }

    $error_level = 'Unknown Error';
    switch ($errno) {
        case E_ERROR: case E_USER_ERROR: $error_level = 'Fatal Error'; break;
        case E_WARNING: case E_USER_WARNING: $error_level = 'Warning'; break;
        case E_NOTICE: case E_USER_NOTICE: $error_level = 'Notice'; break;
    }

    $sql = "INSERT INTO error_log (error_level, error_message, file_path, line_number, status) VALUES (?, ?, ?, ?, 'new')";
    $stmt = mysqli_prepare($err_con, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $error_level, $errstr, $errfile, $errline);
    mysqli_stmt_execute($stmt);
    mysqli_close($err_con);

    return true; // Prevent default PHP error handler
}
// Set our custom function as the main error handler
set_error_handler("log_php_error");


// [STEP 3] Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// [STEP 4] Establish the main database connection
// Set the default timezone for all PHP date/time functions
date_default_timezone_set('Asia/Kolkata');

// Remember to use your NEW, secure password here
$con = mysqli_connect('localhost', 'u473024145_servedoor', '*#pqR123', 'u473024145_servedoor');

// Check connection
if (mysqli_connect_errno()) {
  trigger_error("Failed to connect to MySQL: " . mysqli_connect_error(), E_USER_ERROR);
  die("A critical database connection error occurred. The issue has been logged.");
}

// Sync the MySQL connection's timezone with PHP's timezone
mysqli_query($con, "SET time_zone = '+05:30'");
?>