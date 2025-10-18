<?php 
include('top.php');

/* ------------ Actions: status toggle ------------- */
if(isset($_GET['type']) && $_GET['type']!=='' && isset($_GET['id']) && $_GET['id']>0){
    $type=get_safe_value($_GET['type']);
    $id=get_safe_value($_GET['id']);
    if($type=='active' || $type=='deactive'){
        $status = ($type=='active') ? 1 : 0;
        mysqli_query($con,"UPDATE dish SET status='$status' WHERE id='$id'");
        redirect('dish.php');
    }
}

/* ------------ Restaurant Filter ------------- */
$filter_restaurant = isset($_GET['restaurant_id']) ? get_safe_value($_GET['restaurant_id']) : '';
$rest_list = mysqli_query($con,"SELECT id,name FROM restaurants WHERE status=1 ORDER BY name");

/* ------------ Listing Query (JOIN with category + restaurants) ------------- */
$where = '';
if($filter_restaurant!=''){
    $where = " WHERE d.restaurant_id='".$filter_restaurant."' ";
}

$sql = "SELECT d.*, c.category, r.name AS restaurant_name
        FROM dish d
        LEFT JOIN category c ON c.id = d.category_id
        LEFT JOIN restaurants r ON r.id = d.restaurant_id
        $where
        ORDER BY d.id DESC";
$res = mysqli_query($con,$sql);
?>
<div class="card">
  <div class="card-body">
    <h1 class="grid_title">Dish Master</h1>
    <a href="manage_dish.php" class="add_link">Add Dish</a>

    <!-- Filter: Restaurant -->
    <form method="get" class="mb-3">
      <div class="row">
        <div class="col-md-4">
          <select name="restaurant_id" class="form-control">
            <option value="">All Restaurants</option>
            <?php while($rr = mysqli_fetch_assoc($rest_list)){ ?>
              <option value="<?php echo $rr['id']; ?>" <?php echo ($filter_restaurant==$rr['id'])?'selected':''; ?>>
                <?php echo htmlspecialchars($rr['name']); ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary" type="submit">Filter</button>
          <?php if($filter_restaurant!=''){ ?>
            <a href="dish.php" class="btn btn-light">Reset</a>
          <?php } ?>
        </div>
      </div>
    </form>

    <div class="row grid_box">
      <div class="col-12">
        <div class="table-responsive">
          <table id="order-listing" class="table">
            <thead>
              <tr>
                <th width="8%">S.No #</th>
                <th width="14%">Category</th>
                <th width="20%">Dish</th>
                <th width="18%">Restaurant</th>
                <th width="15%">Image</th>
                <th width="12%">Added On</th>
                <th width="13%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              if(mysqli_num_rows($res)>0){
                $i=1;
                while($row=mysqli_fetch_assoc($res)){
                  $imgSrc = SITE_DISH_IMAGE.$row['image'];
              ?>
              <tr>
                <td><?php echo $i; ?></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td><?php echo htmlspecialchars($row['dish']); ?> (<?php echo strtoupper($row['type']); ?>)</td>
                <td><?php echo htmlspecialchars($row['restaurant_name']); ?></td>
                <td>
                  <?php if($row['image']!=''){ ?>
                    <a target="_blank" href="<?php echo $imgSrc; ?>">
                      <img src="<?php echo $imgSrc; ?>" style="height:45px;border-radius:6px;" />
                    </a>
                  <?php } ?>
                </td>
                <td>
                  <?php 
                    $dateStr=strtotime($row['added_on']);
                    echo date('d-m-Y',$dateStr);
                  ?>
                </td>
                <td>
                  <a href="manage_dish.php?id=<?php echo $row['id']; ?>">
                    <label class="badge badge-success hand_cursor">Edit</label>
                  </a>&nbsp;
                  <?php if($row['status']==1){ ?>
                    <!-- show Deactivate when currently Active -->
                    <a href="?id=<?php echo $row['id']; ?>&type=deactive">
                      <label class="badge badge-danger hand_cursor">Deactivate</label>
                    </a>
                  <?php }else{ ?>
                    <a href="?id=<?php echo $row['id']; ?>&type=active">
                      <label class="badge badge-info hand_cursor">Activate</label>
                    </a>
                  <?php } ?>
                </td>
              </tr>
              <?php 
                  $i++;
                } 
              } else { ?>
              <tr>
                <td colspan="7">No data found</td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include('footer.php'); ?>
