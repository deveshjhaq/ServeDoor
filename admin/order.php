<?php
include('top.php');

// --- [SECURE] Handle status updates and delivery boy assignments ---
if (isset($_GET['type']) && $_GET['type'] !== '' && isset($_GET['id']) && $_GET['id'] > 0) {
    $type = get_safe_value($_GET['type']);
    $id = get_safe_value($_GET['id']);

    if ($type == 'update_status') {
        $status = get_safe_value($_GET['status']);
        
        if ($status == 4) {
            $sql = "UPDATE order_master SET order_status = ?, delivered_on = NOW(), payment_status = CASE WHEN payment_type = 'cod' THEN 'success' ELSE payment_status END WHERE id = ?";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $status, $id);
        } else {
            $sql = "UPDATE order_master SET order_status = ? WHERE id = ?";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $status, $id);
        }
        mysqli_stmt_execute($stmt);
        redirect('order.php');
    }
    
    if ($type == 'assign_boy') {
        $boy_id = get_safe_value($_GET['boy_id']);
        $sql = "UPDATE order_master SET delivery_boy_id = ?, order_status = 6 WHERE id = ?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $boy_id, $id);
        mysqli_stmt_execute($stmt);
        redirect('order.php');
    }
}

// --- Fetch all orders. The "ORDER BY ... DESC" is CORRECT. ---
$sql = "SELECT order_master.*, order_status.order_status as order_status_str 
        FROM order_master 
        JOIN order_status ON order_master.order_status = order_status.id 
        ORDER BY order_master.id DESC";
$res = mysqli_query($con, $sql);

// --- Fetch helper data for dropdowns ---
$delivery_boys_res = mysqli_query($con, "SELECT id, name FROM delivery_boy WHERE status=1 ORDER BY name ASC");
$delivery_boys = [];
while($boy_row = mysqli_fetch_assoc($delivery_boys_res)){
    $delivery_boys[] = $boy_row;
}
$order_status_res = mysqli_query($con, "SELECT id, order_status FROM order_status ORDER BY id ASC");
$order_statuses = [];
while($status_row = mysqli_fetch_assoc($order_status_res)){
    $order_statuses[] = $status_row;
}
?>

<div class="card">
    <div class="card-body">
        <h1 class="grid_title">Order Master</h1>
        <div class="table-responsive">
            <table id="order-listing" class="table table-hover">
                <thead>
                    <tr>
                        <th width="5%">Order ID</th>
                        <th width="15%">Customer Details</th>
                        <th width="15%">Address</th>
                        <th width="10%">Price & Payment</th>
                        <th width="15%">Delivery Boy</th>
                        <th width="20%">Order Status</th>
                        <th width="10%">Added On</th>
                        <th width="10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td>
                            <p><?php echo htmlspecialchars($row['name']); ?></p>
                            <p class="text-muted"><?php echo htmlspecialchars($row['email']); ?></p>
                            <p class="text-muted"><?php echo htmlspecialchars($row['mobile']); ?></p>
                        </td>
                        <td>
                            <p><?php echo htmlspecialchars($row['address']); ?></p>
                            <p class="text-muted"><?php echo htmlspecialchars($row['zipcode']); ?></p>
                        </td>
                        <td>
                            <p><b>Final:</b> ₹<?php echo htmlspecialchars($row['final_price']); ?></p>
                            <p class="text-muted"><b>Type:</b> <?php echo htmlspecialchars(strtoupper($row['payment_type'])); ?></p>
                            <div><b>Status:</b> 
                                <?php 
                                if($row['payment_status'] == 'success'){
                                    echo '<span class="badge badge-success">Success</span>';
                                } else {
                                    echo '<span class="badge badge-warning">Pending</span>';
                                }
                                ?>
                            </div>
                        </td>
                        <td>
                            <select class="form-control" onchange="assign_delivery_boy(this.value, '<?php echo $row['id']; ?>')">
                                <option value="">Assign Boy</option>
                                <?php foreach($delivery_boys as $boy){
                                    $selected = ($boy['id'] == $row['delivery_boy_id']) ? 'selected' : '';
                                    echo "<option value='{$boy['id']}' {$selected}>" . htmlspecialchars($boy['name']) . "</option>";
                                } ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-control" onchange="update_order_status(this.value, '<?php echo $row['id']; ?>')">
                                <?php foreach($order_statuses as $status){
                                    $selected = ($status['id'] == $row['order_status']) ? 'selected' : '';
                                    echo "<option value='{$status['id']}' {$selected}>" . htmlspecialchars($status['order_status']) . "</option>";
                                } ?>
                            </select>
                        </td>
                        <td><?php echo date('d-m-Y', strtotime($row['added_on'])); ?></td>
                        <td>
                            <a href="order_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Details</a>
                            <?php if($row['delivery_boy_id'] > 0 && $row['order_status'] == 3){ ?>
                                <a href="track_order.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm mt-1" target="_blank">Track</a>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php
                        }
                    } else { ?>
                    <tr>
                        <td colspan="8" class="text-center">No orders found.</td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function update_order_status(status, order_id) {
    if (confirm("Are you sure you want to change the status for this order?")) {
        window.location.href = `?id=${order_id}&type=update_status&status=${status}`;
    }
}

function assign_delivery_boy(boy_id, order_id) {
    if (boy_id !== "") {
        if (confirm("Are you sure you want to assign this delivery boy?")) {
            window.location.href = `?id=${order_id}&type=assign_boy&boy_id=${boy_id}`;
        }
    }
}
</script>

<?php include('footer.php'); ?>

<script>
$(document).ready(function() {
    $('#order-listing').DataTable({
        "destroy": true,
        "order": [[ 0, "desc" ]] // Sort by the first column (index 0) in descending order
    });
});
</script>