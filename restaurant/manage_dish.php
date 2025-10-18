<?php
include('header.php'); $rid=$RESTAURANT_ID;

$id = isset($_GET['id'])? intval($_GET['id']) : 0;
$editing=false; $dishRow=null;
if($id>0){
  $rs = mysqli_query($con,"SELECT * FROM dish WHERE id=$id AND restaurant_id=$rid LIMIT 1");
  if($rs && mysqli_num_rows($rs)){ $editing=true; $dishRow=mysqli_fetch_assoc($rs); }
  else { echo "<div class='alert alert-danger'>Dish not found.</div>"; include('footer.php'); exit; }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $dish = get_safe_value($_POST['dish']);
  $dish_detail = get_safe_value($_POST['dish_detail']);
  $category_id = intval($_POST['category_id']);
  $type = ($_POST['type']=='non-veg')?'non-veg':'veg';
  $status = intval($_POST['status'])==1?1:0;

  // image upload optional
  $imgName = $dishRow['image'] ?? '';
  if(isset($_FILES['image']['name']) && $_FILES['image']['name']!=''){
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $new = rand(111111,999999).'_'.time().'.'.$ext;
    move_uploaded_file($_FILES['image']['tmp_name'], SERVER_DISH_IMAGE.$new);
    $imgName = $new;
  }

  if($editing){
    mysqli_query($con,"UPDATE dish SET dish='$dish', dish_detail='$dish_detail', category_id=$category_id, type='$type', status=$status, image='$imgName' WHERE id=$id AND restaurant_id=$rid");
  }else{
    mysqli_query($con,"INSERT INTO dish(category_id,restaurant_id,dish,dish_detail,image,type,status,added_on)
    VALUES($category_id,$rid,'$dish','$dish_detail','$imgName','$type',$status,NOW())");
    $id = mysqli_insert_id($con);
    $editing=true;
  }

  // Attributes (sizes/prices)
  if(isset($_POST['attr_name'])){
    mysqli_query($con,"DELETE FROM dish_details WHERE dish_id=$id"); // simple reset
    foreach($_POST['attr_name'] as $k=>$aname){
      $aname = get_safe_value($aname);
      $price = floatval($_POST['attr_price'][$k] ?? 0);
      if($aname!='' && $price>0){
        mysqli_query($con,"INSERT INTO dish_details(dish_id,attribute,price,status,added_on) VALUES($id,'$aname',$price,1,NOW())");
      }
    }
  }

  header('Location: dishes.php'); exit;
}

$cats = mysqli_query($con,"SELECT id,category FROM category WHERE status=1 ORDER BY order_number DESC");
?>
<div class="card p-3">
  <h4 class="mb-3"><?php echo $editing?'Edit':'Add';?> Dish</h4>
  <form method="post" enctype="multipart/form-data">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Dish Name</label>
        <input name="dish" class="form-control" required value="<?php echo htmlspecialchars($dishRow['dish'] ?? '');?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select" required>
          <?php while($c=mysqli_fetch_assoc($cats)){ ?>
            <option value="<?php echo $c['id'];?>" <?php echo (isset($dishRow['category_id']) && $dishRow['category_id']==$c['id'])?'selected':''; ?>>
              <?php echo htmlspecialchars($c['category']);?>
            </option>
          <?php } ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Details</label>
        <textarea name="dish_detail" class="form-control" rows="3"><?php echo htmlspecialchars($dishRow['dish_detail'] ?? '');?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">Type</label>
        <select name="type" class="form-select">
          <option value="veg" <?php echo (isset($dishRow['type']) && $dishRow['type']=='veg')?'selected':''; ?>>Veg</option>
          <option value="non-veg" <?php echo (isset($dishRow['type']) && $dishRow['type']=='non-veg')?'selected':''; ?>>Non-Veg</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="1" <?php echo (isset($dishRow['status']) && $dishRow['status']==1)?'selected':''; ?>>Active</option>
          <option value="0" <?php echo (isset($dishRow['status']) && $dishRow['status']==0)?'selected':''; ?>>Inactive</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Image</label>
        <input type="file" name="image" class="form-control">
        <?php if(!empty($dishRow['image'])){ ?>
          <img src="<?php echo SITE_DISH_IMAGE.$dishRow['image'];?>" class="mt-2" style="height:60px;border-radius:6px">
        <?php } ?>
      </div>

      <div class="col-12">
        <label class="form-label">Attributes (Size/Weight & Price)</label>
        <div id="attr_rows">
          <?php
          if($editing){
            $dd = mysqli_query($con,"SELECT * FROM dish_details WHERE dish_id=$id ORDER BY price ASC");
            while($a=mysqli_fetch_assoc($dd)){
              echo '<div class="row g-2 mb-2"><div class="col"><input name="attr_name[]" class="form-control" value="'.htmlspecialchars($a['attribute']).'" placeholder="e.g., Half / 450 g"></div><div class="col"><input name="attr_price[]" class="form-control" value="'.htmlspecialchars($a['price']).'" placeholder="Price"></div></div>';
            }
          } else {
            echo '<div class="row g-2 mb-2"><div class="col"><input name="attr_name[]" class="form-control" placeholder="e.g., Half / 450 g"></div><div class="col"><input name="attr_price[]" class="form-control" placeholder="Price"></div></div>';
          }
          ?>
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="addAttr()">+ Add more</button>
      </div>
      <div class="col-12"><button class="btn btn-success">Save</button></div>
    </div>
  </form>
</div>
<script>
function addAttr(){
  const box=document.getElementById('attr_rows');
  const row=document.createElement('div');
  row.className='row g-2 mb-2';
  row.innerHTML='<div class="col"><input name="attr_name[]" class="form-control" placeholder="e.g., Half / 450 g"></div><div class="col"><input name="attr_price[]" class="form-control" placeholder="Price"></div>';
  box.appendChild(row);
}
</script>
<?php include('footer.php'); ?>
