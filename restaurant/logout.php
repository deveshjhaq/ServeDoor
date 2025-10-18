<?php
session_start();
unset($_SESSION['RESTO_LOGIN'], $_SESSION['RESTO_ID'], $_SESSION['RESTO_NAME']);
header('Location: login.php'); exit;
