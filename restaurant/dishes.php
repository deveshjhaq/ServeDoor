<?php include('header.php'); $rid=$RESTAURANT_ID; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Dishes</h4>
  <a class="btn btn-success" href="manage_dish.php">Add Dish</a>
</div>

<div class="table-responsive">
<table class="table table-dark table-hover align-middle">
<thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Type</th><th>Status</th><th>Added</th><th>Action</th></tr></thead>
<tbody>
<?php
$q=mysqli_query($con,"SELECT * FROM dish WHERE restaurant_id=$rid ORDER BY id DESC");
while($r=mysqli_fetch_assoc($q)){ ?>
<tr>
  <td><?php echo $r['id'];?></td>
  <td><img src="<?php echo SITE_DISH_IMAGE.$r['image'];?>" style="width:50px;height:40px;object-fit:cover;border-radius:6px"></td>
  <td><?php echo htmlspecialchars($r['dish']);?></td>
  <td><?php echo htmlspecialchars($r['type']);?></td>
  <td><?php echo ((int)$r['status']==1)?'Active':'Inactive';?></td>
  <td><?php echo $r['added_on'];?></td>
  <td>
    <a class="btn btn-sm btn-info" href="manage_dish.php?id=<?php echo $r['id'];?>">Edit</a>
  </td>
</tr>
<?php } ?>
</tbody></table>
</div>
<?php include('footer.php'); ?>
