<?php
include('top.php');

// --- [SECURE] Handle Approve/Reject Actions ---
if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = get_safe_value($_GET['type']);
    $id = get_safe_value($_GET['id']);

    // For rejecting, we'll delete the record and the uploaded file
    if ($type === 'reject') {
        // First, get the certificate filename to delete it
        $stmt_file = mysqli_prepare($con, "SELECT fssai_certificate FROM restaurants WHERE id=?");
        mysqli_stmt_bind_param($stmt_file, "i", $id);
        mysqli_stmt_execute($stmt_file);
        $res_file = mysqli_stmt_get_result($stmt_file);
        if($row_file = mysqli_fetch_assoc($res_file)){
            $file_to_delete = SERVER_RESTAURANT_IMAGE . $row_file['fssai_certificate'];
            if(file_exists($file_to_delete)){
                unlink($file_to_delete); // Delete the file from server
            }
        }
        
        // Now, delete the database record
        $stmt_delete = mysqli_prepare($con, "DELETE FROM restaurants WHERE id=?");
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        mysqli_stmt_execute($stmt_delete);
    }

    // For approving, we'll set the status to 1 (Active)
    if ($type === 'approve') {
        $stmt_approve = mysqli_prepare($con, "UPDATE restaurants SET status=1 WHERE id=?");
        mysqli_stmt_bind_param($stmt_approve, "i", $id);
        mysqli_stmt_execute($stmt_approve);
    }
    
    // Redirect back to the page to see the updated list
    redirect('pending_restaurants.php');
    exit;
}

// --- Fetch all pending restaurants (status=0) ---
$res = mysqli_query($con, "SELECT * FROM restaurants WHERE status=0 ORDER BY added_on DESC");
?>

<div class="card">
    <div class="card-body">
        <h1 class="grid_title">Pending Restaurant Approvals</h1>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Restaurant Name</th>
                        <th>Owner Details</th>
                        <th>Address</th>
                        <th>FSSAI Certificate</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res) > 0):
                        while ($row = mysqli_fetch_assoc($res)): ?>
                        <tr>
                            <td><b><?php echo htmlspecialchars($row['name']); ?></b></td>
                            <td>
                                <p><?php echo htmlspecialchars($row['owner_name']); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($row['email']); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($row['phone']); ?></p>
                            </td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td>
                                <?php if($row['fssai_certificate']): ?>
                                <a href="<?php echo SITE_RESTAURANT_IMAGE . htmlspecialchars($row['fssai_certificate']); ?>" target="_blank" class="btn btn-info btn-sm">
                                    View Certificate
                                </a>
                                <?php else: ?>
                                    <span class="text-muted">Not Uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d M, Y', strtotime($row['added_on'])); ?></td>
                            <td>
                                <a href="?type=approve&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this restaurant?')">
                                    Approve
                                </a>
                                <a href="?type=reject&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm mt-1" onclick="return confirm('Are you sure you want to permanently reject and delete this application?')">
                                    Reject
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center">No pending approvals found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>