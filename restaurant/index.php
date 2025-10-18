<?php include('header.php'); $rid = $RESTAURANT_ID;

/* KPIs */
$today = date('Y-m-d');
$row = mysqli_fetch_assoc(mysqli_query($con,"
  SELECT 
   SUM(CASE WHEN DATE(added_on)='$today' THEN final_price ELSE 0 END) as today_rev,
   SUM(final_price) as total_rev,
   SUM(CASE WHEN DATE(added_on)='$today' THEN 1 ELSE 0 END) as today_orders,
   COUNT(*) as total_orders
  FROM order_master
  WHERE restaurant_id=$rid AND payment_status IN ('success','pending')
"));
$today_rev = (float)($row['today_rev'] ?? 0);
$total_rev = (float)($row['total_rev'] ?? 0);
$today_orders = (int)($row['today_orders'] ?? 0);
$total_orders = (int)($row['total_orders'] ?? 0);

/* Most ordered dish (all time) */
$top = mysqli_fetch_assoc(mysqli_query($con,"
  SELECT d.dish, SUM(od.qty) as qty_sum
  FROM order_detail od
  JOIN dish_details dd ON dd.id=od.dish_details_id
  JOIN dish d ON d.id=dd.dish_id
  JOIN order_master om ON om.id=od.order_id
  WHERE d.restaurant_id=$rid AND om.restaurant_id=$rid
  GROUP BY d.id
  ORDER BY qty_sum DESC
  LIMIT 1
"));
$top_dish = $top ? $top['dish'].' ('.$top['qty_sum'].' pcs)' : '—';
?>
<div class="row g-3">
  <div class="col-md-3"><div class="card p-3"><div class="text-muted">Today Revenue</div><div class="h4">₹ <?php echo number_format($today_rev,2);?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted">Today Orders</div><div class="h4"><?php echo $today_orders;?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted">Total Revenue</div><div class="h4">₹ <?php echo number_format($total_rev,2);?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted">Total Orders</div><div class="h4"><?php echo $total_orders;?></div></div></div>
</div>

<div class="card mt-3 p-3">
  <div class="d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Most Ordered Dish</h5>
    <span class="badge badge-soft"><?php echo htmlspecialchars($top_dish);?></span>
  </div>
</div>

<div class="card mt-3 p-3">
  <h5 class="mb-3">Recent Orders</h5>
  <div class="table-responsive">
    <table class="table table-sm table-dark align-middle">
      <thead><tr><th>ID</th><th>Customer</th><th>Amount</th><th>Status</th><th>Added</th><th>Action</th></tr></thead>
      <tbody>
      <?php
      $rs = mysqli_query($con,"SELECT id,name,final_price,order_status,added_on FROM order_master WHERE restaurant_id=$rid ORDER BY id DESC LIMIT 10");
      while($r=mysqli_fetch_assoc($rs)){ ?>
        <tr>
          <td>#<?php echo $r['id'];?></td>
          <td><?php echo htmlspecialchars($r['name']);?></td>
          <td>₹ <?php echo number_format($r['final_price'],2);?></td>
          <td><?php echo (int)$r['order_status'];?></td>
          <td><?php echo $r['added_on'];?></td>
          <td><a class="btn btn-sm btn-outline-info" href="orders.php?id=<?php echo $r['id'];?>">View</a></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
</div>
<?php include('footer.php'); ?>
