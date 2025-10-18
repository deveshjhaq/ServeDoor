<?php 
include('top.php');
$msg="";
$coupon_code="";
$coupon_type="";
$coupon_value="";
$cart_min_value="";
$expired_on="";
$id="";

if(isset($_GET['id']) && $_GET['id']>0){
	$id=get_safe_value($_GET['id']);
	$row=mysqli_fetch_assoc(mysqli_query($con,"select * from coupons where id='$id'"));
	$coupon_code=$row['coupon_code'];
	$coupon_type=$row['coupon_type'];
	$coupon_value=$row['coupon_value'];
	$cart_min_value=$row['min_order_value'];
	$expired_on=$row['end_date'];
}

if(isset($_POST['submit'])){
	$coupon_code=get_safe_value($_POST['coupon_code']);
	$coupon_type=get_safe_value($_POST['coupon_type']);
	$coupon_value=get_safe_value($_POST['coupon_value']);
	$cart_min_value=get_safe_value($_POST['min_order_value']);
	$expired_on=get_safe_value($_POST['expired_on']);
	$added_on=date('Y-m-d h:i:s');
	
	if($id==''){
		$sql="select * from coupons where coupon_code='$coupon_code'";
	}else{
		$sql="select * from coupons where coupon_code='$coupon_code' and id!='$id'";
	}	
	if(mysqli_num_rows(mysqli_query($con,$sql))>0){
		$msg="Coupon code already added";
	}else{
		if($id==''){
			
			mysqli_query($con,"insert into coupons(coupon_code,coupon_type,coupon_value,min_order_value,start_date,end_date,status,created_at) values('$coupon_code','$coupon_type','$coupon_value','$cart_min_value',NOW(),'$expired_on',1,NOW())");
		}else{
			mysqli_query($con,"update coupons set coupon_code='$coupon_code', coupon_type='$coupon_type' , coupon_value='$coupon_value', min_order_value='$cart_min_value', end_date='$expired_on' where id='$id'");
		}
		
		redirect('coupon_code.php');
	}
}
?>
<div class="row">
			<h1 class="grid_title ml10 ml15">Manage Coupon Code</h1>
            <div class="col-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <form class="forms-sample" method="post">
                    <div class="form-group">
                      <label for="exampleInputName1">Coupon Code</label>
                      <input type="text" class="form-control" placeholder="Coupon Code" name="coupon_code" required value="<?php echo $coupon_code?>">
					  <div class="error mt8"><?php echo $msg?></div>
                    </div>
					<div class="form-group">
                      <label for="exampleInputName1">Coupon Type</label>
                      <select name="coupon_type" required class="form-control">
						<option value="">Select Type</option>
						<?php
						$arr=array('percentage'=>'Percentage','fixed'=>'Fixed');
						foreach($arr as $key=>$val){
							if($key==$coupon_type){
								echo "<option value='".$key."' selected>".$val."</option>";
							}else{
								echo "<option value='".$key."'>".$val."</option>";
							}
							
						}
						?>
					  </select>
					  
                    </div>
                    <div class="form-group">
                      <label for="exampleInputEmail3" required>Coupon Value</label>
                      <input type="textbox" class="form-control" placeholder="Coupon Value" name="coupon_value"  value="<?php echo $coupon_value?>">
                    </div>
					<div class="form-group">
                      <label for="exampleInputEmail3" required>Minimum Order Value</label>
                      <input type="number" step="0.01" class="form-control" placeholder="Minimum Order Value" name="min_order_value"  value="<?php echo $cart_min_value?>" required>
                    </div>
					<div class="form-group">
                      <label for="exampleInputEmail3">Expiry Date</label>
                      <input type="date" class="form-control" placeholder="Expiry Date" name="expired_on"  value="<?php echo $expired_on?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mr-2" name="submit">Submit</button>
                  </form>
                </div>
              </div>
            </div>
            
		 </div>
        
<?php include('footer.php');?>