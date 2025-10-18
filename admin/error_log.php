<?php
include('top.php');

// Mark an error as "seen"
if(isset($_GET['type']) && $_GET['type'] === 'update_status' && isset($_GET['id'])){
    $id = get_safe_value($_GET['id']);
    mysqli_query($con, "UPDATE error_log SET status='seen' WHERE id='$id'");
    redirect('error_log.php');
}

// Fetch all errors
$res = mysqli_query($con, "SELECT * FROM error_log ORDER BY timestamp DESC");
?>
<div class="card">
    <div class="card-body">
        <h1 class="grid_title">Website Error Log</h1>
        <p class="card-description">Any PHP errors, warnings, or notices on your website will be logged here automatically.</p>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Level</th>
                        <th>Message</th>
                        <th>Location</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res) > 0):
                        while ($row = mysqli_fetch_assoc($res)): ?>
                        <tr class="<?php echo ($row['status'] == 'new') ? 'font-weight-bold' : ''; ?>">
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <?php if($row['error_level'] == 'Fatal Error'): ?>
                                    <span class="badge badge-danger"><?php echo $row['error_level']; ?></span>
                                <?php elseif($row['error_level'] == 'Warning'): ?>
                                    <span class="badge badge-warning"><?php echo $row['error_level']; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-info"><?php echo $row['error_level']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['error_message']); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars(basename($row['file_path'])); ?></div>
                                <small class="text-muted">Line: <?php echo $row['line_number']; ?></small>
                            </td>
                            <td><?php echo date('d M, Y h:i A', strtotime($row['timestamp'])); ?></td>
                            <td>
                                <?php if($row['status'] == 'new'): ?>
                                    <a href="?type=update_status&id=<?php echo $row['id']; ?>"><div class="badge badge-primary">New</div></a>
                                <?php else: ?>
                                    <div class="badge badge-secondary">Seen</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center">No errors logged. Your website is running smoothly!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include('footer.php'); ?>