<?php include('header.php'); $rid=$RESTAURANT_ID;

$status_map = [];
$st = mysqli_query($con,"SELECT id,order_status FROM order_status");
while($s=mysqli_fetch_assoc($st)){ $status_map[$s['id']]=$s['order_status']; }

$where = "WHERE om.restaurant_id=$rid";
if (isset($_GET['filter']) && $_GET['filter']!=='') {
  $f = intval($_GET['filter']); $where .= " AND om.order_status=$f";
}

$q = mysqli_query($con,"
  SELECT om.id, om.name, om.mobile, om.address, om.final_price, om.payment_status, om.order_status, om.added_on
  FROM order_master om
  $where
  ORDER BY om.id DESC
  LIMIT 200
");
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">Orders</h4>
  <form class="d-flex" method="get">
    <select name="filter" class="form-select me-2">
      <option value="">All</option>
      <?php foreach($status_map as $sid=>$snm){ ?>
        <option value="<?php echo $sid;?>" <?php echo (isset($_GET['filter']) && intval($_GET['filter'])==$sid)?'selected':''; ?>><?php echo $snm;?></option>
      <?php } ?>
    </select>
    <button class="btn btn-secondary">Apply</button>
  </form>
</div>

<div class="table-responsive">
<table class="table table-striped table-dark align-middle">
<thead><tr><th>ID</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Status</th><th>Added</th><th>Action</th></tr></thead>
<tbody>
<?php while($r=mysqli_fetch_assoc($q)){ ?>
  <tr>
    <td>#<?php echo $r['id'];?></td>
    <td>
      <div class="fw-semibold"><?php echo htmlspecialchars($r['name']);?></div>
      <div class="small text-muted"><?php echo htmlspecialchars($r['mobile']);?></div>
    </td>
    <td>₹ <?php echo number_format($r['final_price'],2);?></td>
    <td><?php echo htmlspecialchars($r['payment_status']);?></td>
    <td><?php echo htmlspecialchars($status_map[$r['order_status']] ?? $r['order_status']);?></td>
    <td><?php echo $r['added_on'];?></td>
    <td>
      <form method="post" action="order_action.php" class="d-flex gap-1">
        <input type="hidden" name="order_id" value="<?php echo $r['id'];?>">
        <select name="new_status" class="form-select form-select-sm">
          <?php foreach($status_map as $sid=>$snm){ ?>
            <option value="<?php echo $sid;?>" <?php echo ($sid==$r['order_status'])?'selected':''; ?>><?php echo $snm;?></option>
          <?php } ?>
        </select>
        <button class="btn btn-sm btn-success">Update</button>
      </form>
    </td>
  </tr>
<?php } ?>
</tbody></table>
</div>
<?php include('footer.php'); ?>
