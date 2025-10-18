<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include('database.inc.php');
include('function.inc.php');
include('constant.inc.php');

/* Page flags (can be set by page before include) */
$HIDE_GLOBAL_SEARCH = isset($HIDE_GLOBAL_SEARCH) ? (bool)$HIDE_GLOBAL_SEARCH : true;
$HIDE_GLOBAL_NAV    = isset($HIDE_GLOBAL_NAV) ? (bool)$HIDE_GLOBAL_NAV : false;
$COMPACT_HEADER     = isset($COMPACT_HEADER) ? (bool)$COMPACT_HEADER : false;

/* Settings */
$getSetting         = getSetting();
$website_close      = $getSetting['website_close'] ?? 0;
$website_close_msg  = $getSetting['website_close_msg'] ?? '';

/* Cart + Wallet (functions retained) */
getDishCartStatus();
$cartArr        = getUserFullCart();
$totalPrice     = getcartTotalPrice();
$totalCartDish  = 0;
if (!empty($cartArr)) { foreach($cartArr as $it){ $totalCartDish += (int)$it['qty']; } }
$getWalletAmt = 0;
if(isset($_SESSION['FOOD_USER_ID'])){ $getWalletAmt = getWalletAmt($_SESSION['FOOD_USER_ID']); }
$userName = $_SESSION['FOOD_USER_NAME'] ?? 'Guest';

/* THEME: per-user preference (DB > cookie) */
$PHP_THEME = 'dark';
if(isset($_SESSION['FOOD_USER_ID'])){
    $u = mysqli_query($con,"SELECT theme_pref FROM user WHERE id='".intval($_SESSION['FOOD_USER_ID'])."'");
    if($u && mysqli_num_rows($u)){
        $row = mysqli_fetch_assoc($u);
        if($row['theme_pref']=='light'||$row['theme_pref']=='dark'){ $PHP_THEME=$row['theme_pref']; }
    }
} else if(isset($_COOKIE['servedoor_theme'])) {
    if($_COOKIE['servedoor_theme']=='light'||$_COOKIE['servedoor_theme']=='dark'){ $PHP_THEME=$_COOKIE['servedoor_theme']; }
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $PHP_THEME==='light'?'light':'dark'; ?>">
<head>
<meta charset="utf-8"/>
<meta http-equiv="x-ua-compatible" content="ie=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title><?php echo FRONT_SITE_NAME;?></title>

<!-- No favicon/webicon tag included as requested -->

<link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
<link href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<style type="text/tailwindcss">
:root {
  --primary-color:#1DB954;
  --background-dark:#121212; --text-dark:#FFFFFF; --surface-dark:#1E1E1E; --card-dark:#181818; --border-dark:#1f2937; --text-muted-dark:#9aa3af;
  --background-light:#FFFFFF; --text-light:#111827; --surface-light:#F3F4F6; --card-light:#FFFFFF; --border-light:#E5E7EB; --text-muted-light:#6b7280;
}
.dark  { --bg:var(--background-dark); --fg:var(--text-dark);  --surface:var(--surface-dark);  --card:var(--card-dark);  --border:var(--border-dark);  --muted:var(--text-muted-dark); }
.light { --bg:var(--background-light); --fg:var(--text-light); --surface:var(--surface-light); --card:var(--card-light); --border:var(--border-light); --muted:var(--text-muted-light); }

body { background-color:var(--bg); color:var(--fg); transition:background-color .15s,color .15s; font-family:"Space Grotesk","Noto Sans",sans-serif; }
.theme-surface { background-color:var(--surface); }
.theme-card { background-color:var(--card); }
.theme-border { border-color:var(--border); }
.theme-muted { color:var(--muted); }
.theme-input { @apply rounded-lg border px-3 py-2 w-full; border-color:var(--border); background-color:var(--surface); color:var(--fg); }
.badge { @apply absolute -top-1 -right-1 text-[10px] min-w-4 h-4 px-1 rounded-full flex items-center justify-center text-white; background-color:var(--primary-color); }

/* Dropdown fix */
.dropdown { @apply absolute right-0 mt-2 w-56 theme-card border theme-border rounded-lg shadow-xl p-2; display:none; }
.dropdown.active { display:block; }

@media (max-width: 768px){
  .hide-on-compact { display: <?php echo $COMPACT_HEADER?'none':'block'; ?>; }
}
</style>

<script>
(function(){
  try{
    var c = document.cookie.match(/(?:^|;\s*)servedoor_theme=([^;]+)/);
    if(c && (c[1]==='light'||c[1]==='dark')){
      document.documentElement.classList.remove('light','dark');
      document.documentElement.classList.add(c[1]);
    }
  }catch(e){}
})();
</script>
</head>

<body class="min-h-screen">
<div class="min-h-screen flex flex-col">

<header class="theme-surface border-b theme-border sticky top-0 z-40">
  <!-- Top strip -->
  <div class="px-4 md:px-8 py-2 text-sm flex items-center justify-between <?php echo $COMPACT_HEADER?'hidden':''; ?>">
    <div class="flex items-center gap-2 theme-muted">
      <span class="material-symbols-outlined text-base">delivery_dining</span>
      <span>Fast delivery in your area</span>
    </div>
    <div class="flex items-center gap-4">
      <?php if(isset($_SESSION['FOOD_USER_ID'])){ ?>
        <a href="<?php echo FRONT_SITE_PATH?>wallet" class="theme-muted hover:text-white">
          Wallet: <b class="text-white"><?php echo (int)$getWalletAmt;?></b>
        </a>
      <?php } ?>
      <?php if(isset($_SESSION['FOOD_USER_ID'])){ ?>
        <span class="theme-muted hidden md:inline">Welcome, <b class="text-white" id="user_top_name"><?php echo ucwords(htmlspecialchars($userName));?></b></span>
      <?php } ?>
    </div>
  </div>

  <!-- Main bar -->
  <div class="px-4 md:px-8 <?php echo $COMPACT_HEADER?'h-14':'h-16'; ?> flex items-center justify-between">
    <a href="<?php echo FRONT_SITE_PATH;?>shop" class="flex items-center gap-2">
      <span class="font-bold text-lg tracking-wide"><?php echo FRONT_SITE_NAME;?></span>
    </a>

    <?php if(!$HIDE_GLOBAL_SEARCH){ ?>
    <div class="hidden md:block flex-1 max-w-xl mx-4">
      <form action="<?php echo FRONT_SITE_PATH;?>shop" method="get">
        <div class="flex items-stretch rounded-lg h-10 theme-surface border theme-border">
          <div class="flex items-center justify-center pl-3 theme-muted">
            <span class="material-symbols-outlined">search</span>
          </div>
          <input name="search_str" class="flex-1 bg-transparent px-2 outline-none" placeholder="Search for dishes or restaurants" value="<?php echo isset($_GET['search_str'])?htmlspecialchars($_GET['search_str']):'';?>">
          <button class="px-3 rounded-r-lg text-white" style="background:var(--primary-color)">Search</button>
        </div>
      </form>
    </div>
    <?php } ?>

    <div class="flex items-center gap-2 md:gap-3">
      <?php if(!$HIDE_GLOBAL_NAV){ ?>
      <nav class="hidden md:flex items-center gap-4 text-sm">
        <a href="<?php echo FRONT_SITE_PATH?>shop" class="theme-muted hover:text-white">Shop</a>
        <a href="<?php echo FRONT_SITE_PATH?>about-us" class="theme-muted hover:text-white">About</a>
        <a href="<?php echo FRONT_SITE_PATH?>contact-us" class="theme-muted hover:text-white">Contact</a>
      </nav>
      <?php } ?>

      <?php if(!$COMPACT_HEADER){ ?>
      <a href="<?php echo FRONT_SITE_PATH?>my_order" class="relative flex items-center justify-center rounded-full size-10 theme-surface border theme-border" title="My Orders">
        <span class="material-symbols-outlined">receipt_long</span>
      </a>
      <?php } ?>

      <!-- Cart -->
      <div class="relative">
        <a href="<?php echo FRONT_SITE_PATH?>cart" class="relative flex items-center justify-center rounded-full size-10 theme-surface border theme-border" id="cart-button">
          <span class="material-symbols-outlined">shopping_cart</span>
          <?php if($totalCartDish>0){ ?><span class="badge"><?php echo $totalCartDish;?></span><?php } ?>
        </a>
        <?php if($totalPrice>0 && !$COMPACT_HEADER){ ?>
        <div class="dropdown" id="cart-dropdown">
          <div class="max-h-72 overflow-auto pr-1">
            <ul id="cart_ul">
              <?php foreach($cartArr as $key=>$list){ ?>
              <li class="flex gap-3 py-2 border-b theme-border last:border-0" id="attr_<?php echo $key?>">
                <img src="<?php echo SITE_DISH_IMAGE.$list['image']?>" class="w-12 h-12 rounded object-cover" alt="">
                <div class="flex-1">
                  <div class="text-sm font-medium"><?php echo htmlspecialchars($list['dish']);?></div>
                  <div class="text-xs theme-muted">Qty: <?php echo (int)$list['qty'];?></div>
                  <div class="text-sm"><?php echo (int)$list['qty']*(float)$list['price'];?> Rs</div>
                </div>
                <button onclick="delete_cart('<?php echo $key?>')" class="text-red-400 hover:text-red-300" title="Remove">
                  <span class="material-symbols-outlined text-base">close</span>
                </button>
              </li>
              <?php } ?>
            </ul>
          </div>
          <div class="flex items-center justify-between pt-2">
            <span class="text-sm theme-muted">Total</span>
            <span class="font-bold" id="shopTotal"><?php echo (float)$totalPrice;?> Rs</span>
          </div>
          <div class="grid grid-cols-2 gap-2 mt-2">
            <a href="<?php echo FRONT_SITE_PATH?>cart" class="text-center py-2 rounded-lg border theme-border theme-muted hover:text-white">View cart</a>
            <a href="<?php echo FRONT_SITE_PATH?>checkout.php" class="text-center py-2 rounded-lg text-white" style="background:var(--primary-color)">Checkout</a>
          </div>
        </div>
        <?php } ?>
      </div>

      <!-- Theme toggle -->
      <button class="relative flex items-center justify-center rounded-full size-10 theme-surface border theme-border" id="theme-toggle" title="Toggle theme">
        <span class="material-symbols-outlined hidden dark:inline">dark_mode</span>
        <span class="material-symbols-outlined dark:hidden">light_mode</span>
      </button>

      <!-- Profile -->
      <div class="relative">
        <button class="flex items-center" id="profile-button">
          <div class="rounded-full size-10 bg-cover bg-center" style='background-image:url("<?php echo FRONT_SITE_PATH;?>assets/img/user.png");'></div>
        </button>
        <div class="dropdown" id="profile-dropdown">
          <?php if(isset($_SESSION['FOOD_USER_ID'])){ ?>
            <div class="px-3 py-2 text-xs theme-muted">Hi, <?php echo ucwords(htmlspecialchars($userName));?></div>
            <a class="block px-3 py-2 rounded hover:theme-surface" href="<?php echo FRONT_SITE_PATH?>profile">My Profile</a>
            <a class="block px-3 py-2 rounded hover:theme-surface" href="<?php echo FRONT_SITE_PATH?>my_order">My Orders</a>
            <a class="block px-3 py-2 rounded hover:theme-surface" href="<?php echo FRONT_SITE_PATH?>logout">Logout</a>
          <?php } else { ?>
            <a class="block px-3 py-2 rounded hover:theme-surface" href="<?php echo FRONT_SITE_PATH?>login_register">Login / Register</a>
          <?php } ?>
        </div>
      </div>

    </div>
  </div>

  <?php if(!$HIDE_GLOBAL_SEARCH){ ?>
  <div class="px-4 md:hidden pb-3">
    <form action="<?php echo FRONT_SITE_PATH;?>shop" method="get" class="flex gap-2">
      <input name="search_str" class="theme-input" placeholder="Search for dishes..." value="<?php echo isset($_GET['search_str'])?htmlspecialchars($_GET['search_str']):'';?>">
      <button class="px-4 rounded-lg text-white" style="background:var(--primary-color)">Go</button>
    </form>
    <?php if(!$HIDE_GLOBAL_NAV){ ?>
    <div class="mt-3 flex items-center gap-4 text-sm">
      <a href="<?php echo FRONT_SITE_PATH?>shop" class="theme-muted hover:text-white">Shop</a>
      <a href="<?php echo FRONT_SITE_PATH?>about-us" class="theme-muted hover:text-white">About</a>
      <a href="<?php echo FRONT_SITE_PATH?>contact-us" class="theme-muted hover:text-white">Contact</a>
    </div>
    <?php } ?>
  </div>
  <?php } ?>
</header>

<main class="flex-1">
<script>
/* Theme toggle with DB/cookie persistence */
(function(){
  const keyCookie = 'servedoor_theme';
  function setCookie(name,val,days){
    const d=new Date(); d.setTime(d.getTime()+ (days*24*60*60*1000));
    document.cookie = name+"="+val+"; expires="+d.toUTCString()+"; path=/";
  }
  async function persistTheme(theme){
    try{
      <?php if(isset($_SESSION['FOOD_USER_ID'])){ ?>
      await fetch('<?php echo FRONT_SITE_PATH?>set_theme.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'theme='+encodeURIComponent(theme)
      });
      <?php } else { ?>
      setCookie(keyCookie, theme, 365);
      <?php } ?>
    }catch(e){}
  }
  document.getElementById('theme-toggle')?.addEventListener('click', ()=>{
    const html = document.documentElement;
    if(html.classList.contains('dark')){
      html.classList.remove('dark'); html.classList.add('light'); persistTheme('light');
    } else {
      html.classList.remove('light'); html.classList.add('dark'); persistTheme('dark');
    }
  });
})();

/* Dropdowns (cart & profile) */
document.addEventListener('DOMContentLoaded', function() {
    const cartBtn = document.getElementById('cart-button');
    const cartDrop = document.getElementById('cart-dropdown');
    const profileButton = document.getElementById('profile-button');
    const profileDropdown = document.getElementById('profile-dropdown');

    function toggle(el){ if(el) el.classList.toggle('active'); }
    function close(el){ if(el) el.classList.remove('active'); }

    if (cartBtn && cartDrop) {
      cartBtn.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); toggle(cartDrop); close(profileDropdown); });
    }
    if (profileButton && profileDropdown) {
      profileButton.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); toggle(profileDropdown); close(cartDrop); });
    }
    window.addEventListener('click', function(){ close(cartDrop); close(profileDropdown); });
});
</script>
