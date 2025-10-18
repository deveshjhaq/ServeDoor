<?php include('header.php'); $rid=$RESTAURANT_ID;
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = get_safe_value($_POST['name']);
  $phone= get_safe_value($_POST['phone']);
  $email= get_safe_value($_POST['email']);
  $address=get_safe_value($_POST['address']);
  $city = get_safe_value($_POST['city']);
  $pincode = get_safe_value($_POST['pincode']);
  $open = get_safe_value($_POST['open_time']);
  $close= get_safe_value($_POST['close_time']);
  $min = floatval($_POST['min_order_amount'] ?? 0);
  $is_open = isset($_POST['is_open'])?1:0;

  mysqli_query($con,"UPDATE restaurants SET 
    name='$name', phone='$phone', email='$email', address='$address', city='$city', pincode='$pincode',
    open_time='$open', close_time='$close', min_order_amount=$min, is_open=$is_open
    WHERE id=$rid");

  // logo upload
  if(isset($_FILES['logo']['name']) && $_FILES['logo']['name']!=''){
    $ext=strtolower(pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION));
    $new='logo_'.$rid.'_'.time().'.'.$ext;
    $server = defined('SERVER_RESTAURANT_IMAGE') ? SERVER_RESTAURANT_IMAGE : (SERVER_IMAGE."media/restaurants/");
    if(!is_dir($server)) @mkdir($server,0777,true);
    move_uploaded_file($_FILES['logo']['tmp_name'],$server.$new);
    mysqli_query($con,"UPDATE restaurants SET logo='".mysqli_real_escape_string($con,$new)."' WHERE id=$rid");
  }
  $msg='Saved.';
}

$r = mysqli_fetch_assoc(mysqli_query($con,"SELECT * FROM restaurants WHERE id=$rid"));
?>
<div class="card p-3">
  <h4 class="mb-3">Profile</h4>
  <?php if($msg){ echo '<div class="alert alert-success py-2">'.$msg.'</div>'; } ?>
  <form method="post" enctype="multipart/form-data">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="<?php echo htmlspecialchars($r['name']);?>"></div>
      <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?php echo htmlspecialchars($r['phone']);?>"></div>
      <div class="col-md-3"><label class="form-label">Email</label><input name="email" class="form-control" value="<?php echo htmlspecialchars($r['email']);?>"></div>
      <div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="<?php echo htmlspecialchars($r['address']);?>"></div>
      <div class="col-md-3"><label class="form-label">City</label><input name="city" class="form-control" value="<?php echo htmlspecialchars($r['city']);?>"></div>
      <div class="col-md-3"><label class="form-label">Pincode</label><input name="pincode" class="form-control" value="<?php echo htmlspecialchars($r['pincode']);?>"></div>
      <div class="col-md-3"><label class="form-label">Open Time</label><input name="open_time" class="form-control" value="<?php echo htmlspecialchars($r['open_time']);?>"></div>
      <div class="col-md-3"><label class="form-label">Close Time</label><input name="close_time" class="form-control" value="<?php echo htmlspecialchars($r['close_time']);?>"></div>
      <div class="col-md-3"><label class="form-label">Min Order Amount</label><input name="min_order_amount" type="number" step="0.01" class="form-control" value="<?php echo htmlspecialchars($r['min_order_amount']);?>"></div>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_open" id="is_open" <?php echo ($r['is_open']? 'checked':'');?>>
          <label class="form-check-label" for="is_open">Currently Open</label>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Logo</label>
        <input type="file" name="logo" class="form-control">
        <?php if(!empty($r['logo'])){ 
          $site = defined('SITE_RESTAURANT_IMAGE') ? SITE_RESTAURANT_IMAGE : (FRONT_SITE_PATH."media/restaurants/");
          echo '<img src="'.$site.$r['logo'].'" class="mt-2" style="height:60px;border-radius:6px">';
        } ?>
      </div>
      <div class="col-12"><button class="btn btn-success">Save</button></div>
    </div>
  </form>
</div>
<?php include('footer.php'); ?>
