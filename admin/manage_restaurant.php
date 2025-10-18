<?php 
include('top.php');

$msg="";
$id="";
$name="";
$slug="";
$phone="";
$email="";
$address="";
$city="";
$pincode="";
$open_time="";
$close_time="";
$min_order_amount="0.00";
$is_open=1;
$status=1;

if(isset($_GET['id']) && $_GET['id']>0){
    $id=get_safe_value($_GET['id']);
    $row=mysqli_fetch_assoc(mysqli_query($con,"SELECT * FROM restaurants WHERE id='$id'"));
    if($row){
        $name=$row['name'];
        $slug=$row['slug'];
        $phone=$row['phone'];
        $email=$row['email'];
        $address=$row['address'];
        $city=$row['city'];
        $pincode=$row['pincode'];
        $open_time=$row['open_time'];
        $close_time=$row['close_time'];
        $min_order_amount=$row['min_order_amount'];
        $is_open=$row['is_open'];
        $status=$row['status'];
    }
}

if(isset($_POST['submit'])){
    $name=get_safe_value($_POST['name']);
    $slug=get_safe_value($_POST['slug']);
    $phone=get_safe_value($_POST['phone']);
    $email=get_safe_value($_POST['email']);
    $address=get_safe_value($_POST['address']);
    $city=get_safe_value($_POST['city']);
    $pincode=get_safe_value($_POST['pincode']);
    $open_time=get_safe_value($_POST['open_time']);
    $close_time=get_safe_value($_POST['close_time']);
    $min_order_amount=get_safe_value($_POST['min_order_amount']);
    $is_open=isset($_POST['is_open'])?1:0;
    $status=isset($_POST['status'])?1:0;

    if($id==''){
        // unique slug
        $res=mysqli_query($con,"SELECT id FROM restaurants WHERE slug='$slug'"); 
        if(mysqli_num_rows($res)>0){
            $msg="Slug already exists";
        }else{
            mysqli_query($con,"INSERT INTO restaurants(name,slug,phone,email,address,city,pincode,open_time,close_time,min_order_amount,is_open,status) VALUES('$name','$slug','$phone','$email','$address','$city','$pincode','$open_time','$close_time','$min_order_amount','$is_open','$status')");
            redirect('restaurant.php');
        }
    }else{
        $res=mysqli_query($con,"SELECT id FROM restaurants WHERE slug='$slug' AND id!='$id'");
        if(mysqli_num_rows($res)>0){
            $msg="Slug already exists";
        }else{
            mysqli_query($con,"UPDATE restaurants SET name='$name', slug='$slug', phone='$phone', email='$email', address='$address', city='$city', pincode='$pincode', open_time='$open_time', close_time='$close_time', min_order_amount='$min_order_amount', is_open='$is_open', status='$status' WHERE id='$id'");
            redirect('restaurant.php');
        }
    }
}
?>
<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title"><?php echo ($id!='')? 'Edit Restaurant' : 'Add Restaurant'; ?></h4>
        <p class="card-description" style="color:red;"><?php echo $msg?></p>
        <form class="forms-sample" method="post">
          <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" required value="<?php echo $name?>">
          </div>
          <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" class="form-control" id="slug" name="slug" required value="<?php echo $slug?>">
          </div>
          <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $phone?>">
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo $email?>">
          </div>
          <div class="form-group">
            <label for="address">Address</label>
            <input type="text" class="form-control" id="address" name="address" value="<?php echo $address?>">
          </div>
          <div class="form-group">
            <label for="city">City</label>
            <input type="text" class="form-control" id="city" name="city" value="<?php echo $city?>">
          </div>
          <div class="form-group">
            <label for="pincode">Pincode</label>
            <input type="text" class="form-control" id="pincode" name="pincode" value="<?php echo $pincode?>">
          </div>
          <div class="form-group">
            <label for="open_time">Open Time</label>
            <input type="text" class="form-control" id="open_time" name="open_time" placeholder="10:00 AM" value="<?php echo $open_time?>">
          </div>
          <div class="form-group">
            <label for="close_time">Close Time</label>
            <input type="text" class="form-control" id="close_time" name="close_time" placeholder="10:00 PM" value="<?php echo $close_time?>">
          </div>
          <div class="form-group">
            <label for="min_order_amount">Min Order Amount</label>
            <input type="number" step="0.01" class="form-control" id="min_order_amount" name="min_order_amount" value="<?php echo $min_order_amount?>">
          </div>
          <div class="form-check">
            <label class="form-check-label">
              <input type="checkbox" class="form-check-input" name="is_open" <?php echo ($is_open)?'checked':'';?>> Open Now
            </label>
          </div>
          <div class="form-check">
            <label class="form-check-label">
              <input type="checkbox" class="form-check-input" name="status" <?php echo ($status)?'checked':'';?>> Active
            </label>
          </div>
          <br>
          <button type="submit" name="submit" class="btn btn-primary mr-2">Save</button>
          <a href="restaurant.php" class="btn btn-light">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include('footer.php'); ?>
