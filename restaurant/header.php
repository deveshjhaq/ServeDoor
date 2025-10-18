<?php
include('auth.php');
$RNAME = htmlspecialchars($_SESSION['RESTO_NAME'] ?? 'Restaurant');
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Restaurant Panel - <?php echo $RNAME;?></title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" href="../admin/assets/css/materialdesignicons.min.css">
<style>
body{background:#0b1324;color:#e5e7eb}
.navbar, .sidebar{background:#0f172a} .card{background:#111827;border-color:#1f2937}
a, .nav-link{color:#cbd5e1} a:hover{color:#fff}
.badge-soft{background:#1f2937;color:#e5e7eb}
</style>
</head><body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">🍽️ <?php echo $RNAME;?></a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
      <li class="nav-item"><a class="nav-link" href="dishes.php">Dishes</a></li>
      <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
      <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
      <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
    </ul>
  </div>
</nav>
<div class="container py-4">
