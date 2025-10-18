<?php
session_start();
include('database.inc.php');
include('function.inc.php');
include('constant.inc.php');

$theme = isset($_POST['theme']) ? $_POST['theme'] : '';
if($theme!=='light' && $theme!=='dark'){ http_response_code(400); exit; }

if(isset($_SESSION['FOOD_USER_ID'])){
    $uid = intval($_SESSION['FOOD_USER_ID']);
    mysqli_query($con,"UPDATE `user` SET theme_pref='".mysqli_real_escape_string($con,$theme)."' WHERE id='$uid' LIMIT 1");
} else {
    // guests handled by cookie on client; nothing to do
}
echo 'ok';
