<?php
include("header.php");

/* ===== Cart (for "Added" badge) ===== */
$cartArr = getUserFullCart();

/* ===== Filters (safe) ===== */
$cat_dish = ''; $cat_dish_arr = []; $type = ''; $search_str = ''; $restaurant_id = 0;
if(isset($_GET['cat_dish'])){ $cat_dish = get_safe_value($_GET['cat_dish']); $cat_dish_arr = array_filter(explode(':',$cat_dish)); $cat_dish_arr = array_map('intval', $cat_dish_arr); }
if(isset($_GET['type'])){ $type = get_safe_value($_GET['type']); }
if(isset($_GET['search_str'])){ $search_str = get_safe_value($_GET['search_str']); }
if(isset($_GET['restaurant_id'])){ $restaurant_id = get_safe_value($_GET['restaurant_id']); }
$arrType = array("veg","non-veg","both");

/* ===== Active Order for Logged-in User ===== */
$active_order = null;
if (isset($_SESSION['FOOD_USER_ID'])) {
    $user_id = $_SESSION['FOOD_USER_ID'];
    $sql_order = "SELECT om.*, os.order_status as order_status_str 
                  FROM order_master om
                  JOIN order_status os ON om.order_status = os.id 
                  WHERE om.user_id = ? AND om.order_status IN (1, 2, 3) 
                  ORDER BY om.id DESC LIMIT 1";
    $stmt_order = mysqli_prepare($con, $sql_order);
    if ($stmt_order) {
        mysqli_stmt_bind_param($stmt_order, "i", $user_id);
        mysqli_stmt_execute($stmt_order);
        $active_order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_order));
        mysqli_stmt_close($stmt_order);
    }
}

/* ===== [SECURE] Helper function to get restaurant image ===== */
function getRestaurantDisplayImage($rid, $con){
    $stmt1 = mysqli_prepare($con, "SELECT logo FROM restaurants WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt1, "i", $rid);
    mysqli_stmt_execute($stmt1);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt1));
    if($row && !empty($row['logo'])){
        $site_path = SITE_RESTAURANT_IMAGE . $row['logo'];
        $server_path = SERVER_RESTAURANT_IMAGE . $row['logo'];
        if(file_exists($server_path)){ return $site_path; }
    }
    
    $stmt2 = mysqli_prepare($con, "SELECT d.image FROM dish d WHERE d.restaurant_id = ? AND d.status=1 AND d.image IS NOT NULL AND d.image != '' LIMIT 1");
    mysqli_stmt_bind_param($stmt2, "i", $rid);
    mysqli_stmt_execute($stmt2);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    if($r && !empty($r['image'])){
        $site_dish_path = SITE_DISH_IMAGE . $r['image'];
        $server_dish_path = SERVER_DISH_IMAGE . $r['image'];
        if(file_exists($server_dish_path)){ return $site_dish_path; }
    }
    return FRONT_SITE_PATH.'assets/img/placeholder-restaurant.png';
}

$banner_q = mysqli_query($con,"SELECT * FROM banner WHERE status=1 ORDER BY order_number DESC, added_on DESC");
$banners  = [];
if($banner_q){ while($b = mysqli_fetch_assoc($banner_q)){ $banners[] = $b; } }
?>

<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
<style>
    .swiper-button-next, .swiper-button-prev { color: white !important; --swiper-navigation-size: 30px; }
    .swiper-pagination-bullet-active { background-color: white !important; }
    .order-tracker { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000; box-shadow: 0 -4px 12px rgba(0,0,0,0.1); }
    .progress-bar-container { position: relative; display: flex; justify-content: space-between; align-items: center; }
    .progress-bar-bg, .progress-bar-fg { position: absolute; height: 4px; left: 10%; width: 80%; top: 18px; border-radius: 2px; }
    .progress-bar-bg { background-color: var(--border); }
    .progress-bar-fg { background-color: var(--primary-color); transition: width 0.5s ease; }
    .tracker-step { z-index: 1; text-align: center; }
    .tracker-step .step-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .tracker-step.completed .step-icon { background-color: var(--primary-color); border-color: var(--primary-color); color: white; }
</style>

<section class="px-4 md:px-8 pt-4">
    <div class="max-w-6xl mx-auto">
        <?php if(count($banners) > 0): ?>
            <div class="swiper mySwiper rounded-2xl overflow-hidden">
                <div class="swiper-wrapper">
                    <?php foreach($banners as $banner): ?>
                        <div class="swiper-slide">
                            <?php $bg_image = SITE_BANNER_IMAGE . $banner['image']; ?>
                            <div class="w-full h-48 md:h-64 bg-cover bg-center" style="background-image:url('<?php echo htmlspecialchars($bg_image);?>')">
                                <div class="w-full h-full bg-black/40 flex items-center">
                                    <div class="p-6 md:p-10 text-white">
                                        <h2 class="text-2xl md:text-3xl font-bold"><?php echo htmlspecialchars($banner['heading']);?></h2>
                                        <p class="mt-1 opacity-80"><?php echo htmlspecialchars($banner['sub_heading']);?></p>
                                        <?php if(!empty($banner['link'])): ?>
                                            <a href="<?php echo FRONT_SITE_PATH.htmlspecialchars($banner['link']);?>" class="inline-block mt-4 px-5 py-2 rounded-lg text-white" style="background:var(--primary-color)"><?php echo htmlspecialchars($banner['link_txt'] ?: 'Order Now'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if(!empty($website_close) && (int)$website_close===1): ?>
  <div class="max-w-6xl mx-auto px-4 md:px-8"><div class="mt-10 text-center"><h3 class="text-xl font-semibold"><?php echo htmlspecialchars($website_close_msg);?></h3></div></div>
<?php endif; ?>

<section class="px-4 md:px-8 py-6">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-6">
        <aside class="md:col-span-3 space-y-6">
            <div class="theme-card border theme-border rounded-xl p-4">
                <div class="text-sm font-semibold mb-2">Search</div>
                <div class="flex items-center gap-2">
                    <input type="text" id="search" class="theme-input" placeholder="Search dishes..." value="<?php echo htmlspecialchars($search_str);?>">
                    <button class="px-3 h-10 rounded-lg text-white" style="background:var(--primary-color)" onclick="setSearch()">Go</button>
                </div>
            </div>
            <div class="theme-card border theme-border rounded-xl p-4">
                <div class="text-sm font-semibold mb-2">Type</div>
                <ul class="space-y-1 text-sm">
                    <?php foreach($arrType as $list){ $checked = ($list==$type) ? 'checked' : ''; ?>
                    <li><label class="inline-flex items-center gap-2 cursor-pointer"><input type="radio" class="accent-[var(--primary-color)]" name="type_sidebar" <?php echo $checked;?> onclick="setFoodType('<?php echo $list;?>')"><span><?php echo strtoupper($list);?></span></label></li>
                    <?php } ?>
                </ul>
            </div>
            <div class="theme-card border theme-border rounded-xl p-4">
                <div class="text-sm font-semibold mb-2">Restaurants</div>
                <ul class="space-y-2">
                    <li>
                        <a href="shop.php" class="flex items-center gap-2 theme-muted hover:text-white">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center bg-gray-200">
                                <span class="material-symbols-outlined text-lg text-gray-600">storefront</span>
                            </div>
                            <span><u>All Restaurants</u></span>
                        </a>
                    </li>
                    <?php
                    $rest_side = mysqli_query($con,"SELECT id,name FROM restaurants WHERE status=1 ORDER BY name");
                    while($rs = mysqli_fetch_assoc($rest_side)){
                        $active = ($restaurant_id==$rs['id']);
                        $url = "shop.php?restaurant_id=".$rs['id'];
                        $img = getRestaurantDisplayImage($rs['id'],$con);
                    ?>
                    <li><a href="<?php echo $url;?>" class="flex items-center gap-2 <?php echo $active?'font-semibold':'';?> theme-muted hover:text-white"><img src="<?php echo htmlspecialchars($img);?>" class="w-7 h-7 rounded-full object-cover" alt="<?php echo htmlspecialchars($rs['name']); ?>"><span><?php echo htmlspecialchars($rs['name']);?></span></a></li>
                    <?php } ?>
                </ul>
            </div>
            <div class="theme-card border theme-border rounded-xl p-4">
                <div class="text-sm font-semibold mb-2">Shop By Categories</div>
                <ul class="space-y-1">
                    <li><a href="shop.php" class="theme-muted hover:text-white"><u>clear</u></a></li>
                    <?php
                    $cat_res = mysqli_query($con,"SELECT * FROM category WHERE status=1 ORDER BY order_number DESC");
                    while($cat_row=mysqli_fetch_assoc($cat_res)){
                        $is_checked = in_array($cat_row['id'],$cat_dish_arr) ? "checked='checked'" : '';
                    ?>
                    <li><label class="inline-flex items-center gap-2 text-sm cursor-pointer"><input <?php echo $is_checked;?> onclick="set_checkbox('<?php echo $cat_row['id'];?>')" type="checkbox"><span><?php echo htmlspecialchars($cat_row['category']);?></span></label></li>
                    <?php } ?>
                </ul>
            </div>
        </aside>

        <div class="md:col-span-9">
            <?php
            // --- [SECURE & FAST] This is the new, secure, and fast way to fetch dishes ---
            $product_sql = "SELECT d.*, r.name AS restaurant_name FROM dish d LEFT JOIN restaurants r ON r.id = d.restaurant_id WHERE d.status=1 ";
            $params = [];
            $types = "";

            if(!empty($cat_dish_arr)){
                $placeholders = implode(',', array_fill(0, count($cat_dish_arr), '?'));
                $product_sql .= " AND d.category_id IN ($placeholders) ";
                $params = array_merge($params, $cat_dish_arr);
                $types .= str_repeat('i', count($cat_dish_arr));
            }
            if($type != '' && $type != 'both'){
                $product_sql .= " AND d.type = ? ";
                $params[] = $type;
                $types .= "s";
            }
            if($search_str != ''){
                $product_sql .= " AND (d.dish LIKE ? OR d.dish_detail LIKE ?) ";
                $searchTerm = "%" . $search_str . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "ss";
            }
            if($restaurant_id > 0){
                $product_sql .= " AND d.restaurant_id = ? ";
                $params[] = $restaurant_id;
                $types .= "i";
            }
            $product_sql .= " ORDER BY d.dish ASC";

            $stmt_prod = mysqli_prepare($con, $product_sql);
            if ($stmt_prod && !empty($types)) {
                mysqli_stmt_bind_param($stmt_prod, $types, ...$params);
            }
            mysqli_stmt_execute($stmt_prod);
            $product_res = mysqli_stmt_get_result($stmt_prod);
            
            // --- [PERFORMANCE FIX] Pre-fetch all dish details in one go ---
            $products = [];
            $dish_ids = [];
            if ($product_res && mysqli_num_rows($product_res) > 0) {
                while($row = mysqli_fetch_assoc($product_res)){
                    $products[] = $row;
                    $dish_ids[] = $row['id'];
                }
            }
            $dish_details_by_id = [];
            if(!empty($dish_ids)){
                $ids_str = implode(',', $dish_ids);
                $details_res = mysqli_query($con, "SELECT * FROM dish_details WHERE status='1' AND dish_id IN ($ids_str) ORDER BY price ASC");
                while($detail_row = mysqli_fetch_assoc($details_res)){
                    $dish_details_by_id[$detail_row['dish_id']][] = $detail_row;
                }
            }
            ?>

            <?php if(count($products) > 0): ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach($products as $product_row): ?>
                <div class="theme-card border theme-border rounded-xl overflow-hidden flex flex-col">
                    <div class="relative">
                        <img src="<?php echo SITE_DISH_IMAGE.htmlspecialchars($product_row['image']);?>" alt="<?php echo htmlspecialchars($product_row['dish']); ?>" class="w-full h-40 object-cover">
                        <?php if($product_row['type']=='veg'){ ?><img src="assets/img/icon-img/veg.png" class="absolute top-2 left-2 w-6 h-6" alt="veg"><?php } else { ?><img src="assets/img/icon-img/non-veg.png" class="absolute top-2 left-2 w-6 h-6" alt="non-veg"><?php } ?>
                    </div>
                    <div class="p-4 flex flex-col flex-grow">
                        <a href="javascript:void(0)" class="font-semibold leading-snug"><?php echo htmlspecialchars($product_row['dish']); ?> <?php // getRatingByDishId($product_row['id']); ?></a>
                        <?php if(!empty($product_row['restaurant_name'])){ ?><div class="theme-muted text-xs mt-1"><?php echo htmlspecialchars($product_row['restaurant_name']);?></div><?php } ?>
                        <div class="mt-3 space-y-1 flex-grow">
                            <?php
                            $dish_attributes = $dish_details_by_id[$product_row['id']] ?? [];
                            $is_first_attr = true;
                            foreach($dish_attributes as $dish_attr_row):
                                $attrId = (int)$dish_attr_row['id'];
                                $added_msg = "";
                                if(array_key_exists($attrId, $cartArr)){
                                    $added_qty = $cartArr[$attrId]['qty'];
                                    $added_msg = "(Added - $added_qty)";
                                }
                            ?>
                            <label class="flex items-center justify-between gap-3 text-sm">
                                <span class="flex items-center gap-2">
                                    <?php if(empty($website_close)){ ?><input type="radio" class="accent-[var(--primary-color)]" name="radio_<?php echo $product_row['id'];?>" value="<?php echo $attrId;?>" <?php if($is_first_attr) { echo 'checked'; $is_first_attr = false; } ?>><?php } ?>
                                    <span><?php echo htmlspecialchars($dish_attr_row['attribute']);?></span>
                                </span>
                                <span>
                                    <span class="font-medium"><?php echo (float)$dish_attr_row['price'];?> Rs</span>
                                    <span class="theme-muted ml-1 text-xs" id="shop_added_msg_<?php echo $attrId;?>"><?php echo $added_msg;?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4">
                            <?php if(empty($website_close)){ ?>
                                <button class="w-full px-4 py-2 rounded-lg text-white" style="background:var(--primary-color)" onclick="add_to_cart('<?php echo $product_row['id'];?>','add')">Add to cart</button>
                            <?php } else { ?>
                                <div class="text-sm font-semibold"><?php echo htmlspecialchars($website_close_msg);?></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="theme-card border theme-border rounded-xl p-8 text-center">No dish found matching your criteria.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<form method="get" id="frmCatDish">
    <input type="hidden" name="cat_dish" id="cat_dish" value='<?php echo htmlspecialchars($cat_dish,ENT_QUOTES);?>'/>
    <input type="hidden" name="type" id="type" value='<?php echo htmlspecialchars($type,ENT_QUOTES);?>'/>
    <input type="hidden" name="search_str" id="search_str" value='<?php echo htmlspecialchars($search_str,ENT_QUOTES);?>'/>
    <input type="hidden" name="restaurant_id" id="restaurant_id" value='<?php echo htmlspecialchars($restaurant_id,ENT_QUOTES);?>'/>
</form>

<script>
function set_checkbox(id){
    var cat_dish_val=document.getElementById('cat_dish').value;
    var cat_dish_arr=cat_dish_val.split(':');
    var final_cat_dish_arr=[];
    var is_found=false;
    for(var i=0;i<cat_dish_arr.length;i++){
        if(cat_dish_arr[i]==id){is_found=true;} else{if(cat_dish_arr[i]!=''){final_cat_dish_arr.push(cat_dish_arr[i]);}}
    }
    if(is_found==false){final_cat_dish_arr.push(id);}
    document.getElementById('cat_dish').value=final_cat_dish_arr.join(':');
    document.getElementById('frmCatDish').submit();
}
function setFoodType(type){
    document.getElementById('type').value=type;
    document.getElementById('frmCatDish').submit();
}
function setSearch(){
    document.getElementById('search_str').value=document.getElementById('search').value;
    document.getElementById('frmCatDish').submit();
}
</script>

<?php if ($active_order): ?>
    <?php
        $progress_width = '0%';
        if ($active_order['order_status'] >= 2) $progress_width = '50%';
        if ($active_order['order_status'] >= 3) $progress_width = '100%';
    ?>
    <div class="order-tracker theme-surface p-4">
        <div class="container mx-auto">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <span class="font-bold">Order #<?php echo $active_order['id']; ?></span>
                    <span class="text-sm theme-muted ml-2">Status: <strong><?php echo htmlspecialchars($active_order['order_status_str']); ?></strong></span>
                </div>
                <a href="<?php echo FRONT_SITE_PATH.'my_order'; ?>" class="text-sm font-medium" style="color:var(--primary-color);">View Details</a>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-bg"></div>
                <div class="progress-bar-fg" style="width: <?php echo $progress_width; ?>;"></div>
                <div class="tracker-step completed text-center">
                    <div class="step-icon material-symbols-outlined theme-border border">check</div><div class="text-xs mt-1">Placed</div>
                </div>
                <div class="tracker-step <?php echo ($active_order['order_status'] >= 2) ? 'completed' : ''; ?> text-center">
                    <div class="step-icon material-symbols-outlined theme-border border">lunch_dining</div><div class="text-xs mt-1">Preparing</div>
                </div>
                <div class="tracker-step <?php echo ($active_order['order_status'] >= 3) ? 'completed' : ''; ?> text-center">
                    <div class="step-icon material-symbols-outlined theme-border border">delivery_dining</div><div class="text-xs mt-1">On The Way</div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
    var swiper = new Swiper(".mySwiper", {
        loop: true,
        autoplay: { delay: 5000, disableOnInteraction: false },
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
    });
</script>

<?php include("footer.php"); ?>