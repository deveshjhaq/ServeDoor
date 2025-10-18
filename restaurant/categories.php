<?php include('header.php'); ?>
<div class="card p-3">
  <h4 class="mb-3">Categories (Global)</h4>
  <ul class="list-group list-group-flush">
    <?php
    $c = mysqli_query($con,"SELECT id,category,status FROM category ORDER BY order_number DESC");
    while($r=mysqli_fetch_assoc($c)){
      echo '<li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>'.htmlspecialchars($r['category']).'</span><a class="btn btn-sm btn-outline-info" href="dishes.php?cat='.$r['id'].'">View Dishes</a></li>';
    }
    ?>
  </ul>
</div>
<?php include('footer.php'); ?>
