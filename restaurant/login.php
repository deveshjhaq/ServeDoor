<?php
session_start();
include('../database.inc.php');
include('../function.inc.php');

$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $u = get_safe_value($_POST['username'] ?? '');
  $p = get_safe_value($_POST['password'] ?? '');
  $res = mysqli_query($con,"SELECT id,password,status,name FROM restaurants WHERE username='".mysqli_real_escape_string($con,$u)."' LIMIT 1");
  if ($res && mysqli_num_rows($res)==1) {
    $row = mysqli_fetch_assoc($res);
    if ((int)$row['status']!==1) {
      $error = 'Your restaurant is disabled. Contact admin.';
    } elseif (!password_verify($p, $row['password'])) {
      $error = 'Invalid credentials.';
    } else {
      $_SESSION['RESTO_LOGIN'] = true;
      $_SESSION['RESTO_ID']    = (int)$row['id'];
      $_SESSION['RESTO_NAME']  = $row['name'];
      mysqli_query($con,"UPDATE restaurants SET last_login_at=NOW(), last_login_ip='".mysqli_real_escape_string($con,$_SERVER['REMOTE_ADDR'] ?? '')."' WHERE id=".$row['id']);
      header('Location: index.php'); exit;
    }
  } else { $error = 'Restaurant not found.'; }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Restaurant Login</title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<style>body{background:#0f172a;color:#fff} .card{background:#111827;border:1px solid #1f2937}</style>
</head><body class="d-flex align-items-center justify-content-center" style="min-height:100vh">
  <div class="card p-4" style="width: 380px;">
    <h4 class="mb-3">Restaurant Login</h4>
    <?php if($error){ echo '<div class="alert alert-danger py-2">'.$error.'</div>'; } ?>
    <form method="post">
      <div class="form-group mb-3">
        <label>Username</label>
        <input name="username" class="form-control" required>
      </div>
      <div class="form-group mb-3">
        <label>Password</label>
        <input name="password" type="password" class="form-control" required>
      </div>
      <button class="btn btn-success w-100">Login</button>
    </form>
  </div>
</body></html>
