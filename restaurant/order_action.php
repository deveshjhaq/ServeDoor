<?php
include('auth.php');
if($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location: orders.php'); exit; }

$oid = intval($_POST['order_id'] ?? 0);
$ns  = intval($_POST['new_status'] ?? 0);

$chk = mysqli_fetch_assoc(mysqli_query($con,"SELECT order_status FROM order_master WHERE id=$oid AND restaurant_id=$RESTAURANT_ID"));
if(!$chk){ header('Location: orders.php'); exit; }

$old = (int)$chk['order_status'];
mysqli_query($con,"UPDATE order_master SET order_status=$ns WHERE id=$oid AND restaurant_id=$RESTAURANT_ID");
mysqli_query($con,"INSERT INTO order_status_history(order_id,changed_by,changed_by_id,old_status,new_status,added_on)
VALUES($oid,'restaurant',$RESTAURANT_ID,$old,$ns,NOW())");

header('Location: orders.php');
