<?php
if (session_status()===PHP_SESSION_NONE) session_start();
include('../database.inc.php');
include('../function.inc.php');
include('../constant.inc.php');

if (!isset($_SESSION['RESTO_LOGIN']) || $_SESSION['RESTO_LOGIN']!==true) {
  header('Location: login.php'); exit;
}
$RESTAURANT_ID = intval($_SESSION['RESTO_ID']);

function r_only($id){ return intval($id); } // tiny helper
