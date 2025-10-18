<?php
// 'include' को 'include_once' से बदल दिया गया है
// और session_start() को शर्त के साथ जोड़ा गया है
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../database.inc.php');
include_once('../function.inc.php');
include_once('../constant.inc.php');

if(!isset($_SESSION['IS_LOGIN'])){ 
    redirect('login.php'); 
}

$page_title = 'Restaurant Owners';
// यहाँ से top.php का include हटाया गया है क्योंकि वह नीचे है
?>
<?php 
// अब top.php को include करें
include_once('top.php'); 
?>

<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">Restaurant Owners</h3>
        <a href="manage_restaurant_owner.php" class="btn btn-primary">Add Owner</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Restaurant</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT id, name, username, status, last_login_at FROM restaurants ORDER BY id DESC";
                        $q = mysqli_query($con, $sql);

                        if (!$q) {
                            echo "<tr><td colspan='6'><strong>Database Error:</strong> " . htmlspecialchars(mysqli_error($con)) . "</td></tr>";
                        } 
                        elseif (mysqli_num_rows($q) > 0) {
                            while($r = mysqli_fetch_assoc($q)) { 
                        ?>
                                <tr>
                                    <td><?php echo $r['id']; ?></td>
                                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td><?php echo $r['username'] ? htmlspecialchars($r['username']) : '<span class="text-muted">—</span>'; ?></td>
                                    <td>
                                        <?php if($r['status'] == 1) {
                                            echo '<span class="badge badge-success">Active</span>';
                                        } else {
                                            echo '<span class="badge badge-danger">Inactive</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $r['last_login_at'] ? date('d-m-Y h:i A', strtotime($r['last_login_at'])) : '—'; ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="manage_restaurant_owner.php?id=<?php echo $r['id']; ?>">Edit</a>
                                        <?php if($r['username']) { ?>
                                            <a class="btn btn-sm btn-warning" href="manage_restaurant_owner.php?id=<?php echo $r['id']; ?>&reset=1">Reset Password</a>
                                        <?php } ?>
                                    </td>
                                </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='6'>No restaurant owners found.</td></tr>";
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once('footer.php'); ?>