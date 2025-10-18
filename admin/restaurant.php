<?php 
include('top.php');

if(isset($_GET['type']) && $_GET['type']!=='' && isset($_GET['id']) && $_GET['id']>0){
    $type=get_safe_value($_GET['type']);
    $id=get_safe_value($_GET['id']);
    if($type=='active' || $type=='deactive'){
        $status=1;
        if($type=='deactive'){
            $status=0;
        }
        mysqli_query($con,"UPDATE restaurants SET status='$status' WHERE id='$id'");
        redirect('restaurant.php');
    }
    if($type=='delete'){
        mysqli_query($con,"DELETE FROM restaurants WHERE id='$id'");
        redirect('restaurant.php');
    }
}

$res=mysqli_query($con,"SELECT * FROM restaurants ORDER BY id DESC");
?>
<div class="row">
  <div class="col-12 grid-margin">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Restaurants</h4>
        <a href="manage_restaurant.php" class="btn btn-inverse-info btn-fw" style="margin-bottom:15px;">Add Restaurant</a>
        <div class="table-responsive">
          <table id="order-listing" class="table">
            <thead>
              <tr>
                <th width="7%">#</th>
                <th width="20%">Name</th>
                <th width="15%">Slug</th>
                <th width="20%">Phone</th>
                <th width="15%">Open</th>
                <th width="10%">Status</th>
                <th width="13%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $i=1;
              while($row=mysqli_fetch_assoc($res)){ ?>
                <tr>
                  <td><?php echo $i++?></td>
                  <td><?php echo $row['name']?></td>
                  <td><?php echo $row['slug']?></td>
                  <td><?php echo $row['phone']?></td>
                  <td><?php echo $row['open_time'].' - '.$row['close_time']?></td>
                  <td><?php echo $row['status']==1 ? 'Active' : 'Deactive'?></td>
                  <td>
                    <a href="manage_restaurant.php?id=<?php echo $row['id']?>" class="badge badge-info">Edit</a>
                    &nbsp;
                    <?php if($row['status']==1){ ?>
                      <a href="?id=<?php echo $row['id']?>&type=deactive" class="badge badge-danger">Deactivate</a>
                    <?php } else { ?>
                      <a href="?id=<?php echo $row['id']?>&type=active" class="badge badge-success">Activate</a>
                    <?php } ?>
                    &nbsp;
                    <a href="?id=<?php echo $row['id']?>&type=delete" class="badge badge-warning" onclick="return confirm('Delete this restaurant?')">Delete</a>
                  </td>
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
