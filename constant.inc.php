<?php
// No output before JSON endpoints!
error_reporting(0);
ini_set('display_errors', 0);

/* ==============================
   BRAND / SITE
============================== */
define('SITE_NAME','ServeDoor Admin');
define('FRONT_SITE_NAME','ServeDoor');

/* Base URL (trailing slash जरूरी है) */
define('FRONT_SITE_PATH','https://servedoor.com/');   // ✅ SSL version

/* Server document root (images save करने के लिए) */
define('SERVER_IMAGE', $_SERVER['DOCUMENT_ROOT'] . "/");

/* ==============================
   MEDIA PATHS
============================== */
define('SERVER_DISH_IMAGE',       SERVER_IMAGE . "media/dish/");
define('SITE_DISH_IMAGE',         FRONT_SITE_PATH . "media/dish/");
define('SERVER_BANNER_IMAGE',     SERVER_IMAGE . "media/banner/");
define('SITE_BANNER_IMAGE',       FRONT_SITE_PATH . "media/banner/");
define('SERVER_RESTAURANT_IMAGE', SERVER_IMAGE . "media/restaurants/");
define('SITE_RESTAURANT_IMAGE',   FRONT_SITE_PATH . "media/restaurants/");

/* ==============================
   EMAIL (SMTP) SETTINGS - Hostinger
============================== */
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');
define('SMTP_USER', 'no-reply@servedoor.com');
define('SMTP_PASS', '*#pqR123');   // 👉 नया secure पासवर्ड डालो
define('SMTP_FROM_NAME', 'ServeDoor');

/* ==============================
   SMS / WHATSAPP (Fast2SMS)
============================== */
define('FAST2SMS_AUTH','C3re6W2iYBzNvdjOKLRuy5GS1H8gsQFTlq9oZDXnUcPwbMmfA7qviMXA1Ly2otmh8FdgZSYe5wVfPT4K');
define('FAST2SMS_SENDER','SRVDOR');       // Approved Sender ID
define('FAST2SMS_TEMPLATE_ID','197282');  // DLT Template ID for OTP
define('WHATSAPP_TEMPLATE_ID','6159');    // WhatsApp Template ID

/* ==============================
   GOOGLE API
============================== */
define('GOOGLE_API_KEY','AIzaSyC2gYIa_zyioA1bhG9_1RKw3iuJ309aw1w');

/* ==============================
   MISC (Application Flags)
============================== */
define('SINGLE_RESTAURANT_PER_ORDER', true);
define('FORCE_HTTPS', true); // SSL enforce

/* ==============================
   CASHFREE PAYMENT GATEWAY (PRODUCTION)
============================== */
// ⚠️ Dashboard → Payment Gateway → LIVE API Keys से App ID, Secret और Webhook Secret भरो
define('CASHFREE_ENVIRONMENT','production'); 
define('CASHFREE_APP_ID', trim('10860044154dc1afb161853e84b4006801'));        
define('CASHFREE_SECRET_KEY', trim('cfsk_ma_prod_6736f01f6c6920b2dbc37658b4046632_6219ff59'));
define('CASHFREE_WEBHOOK_SECRET', trim('ySecureWebhook@123'));
define('CASHFREE_ORDER_NOTE','ServeDoor Wallet Top-up');
?>
