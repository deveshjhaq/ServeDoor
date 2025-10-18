<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---------------------------
// Utility / Debug
// ---------------------------
function pr($arr){
	echo '<pre>';
	print_r($arr);
}
function prx($arr){
	echo '<pre>';
	print_r($arr);
	die();
}

function get_safe_value($str){
	global $con;
    if($str!=''){
		$str=trim($str);
		return mysqli_real_escape_string($con,$str);
	}
    return '';
}

function dateFormat($date){
	$ts = is_numeric($date) ? (int)$date : strtotime((string)$date);
	return $ts ? date('d-m-Y',$ts) : '';
}

function redirect($link){
    if (!headers_sent()) {
        header('Location: '.$link, true, 302);
        exit;
    }
	echo "<script>window.location.href=".json_encode($link).";</script>";
	die();
}

/**
 * Helper to build SQL with a dynamic IN (...) list using prepared statements.
 * Example:
 *   [$sql,$types,$params] = sql_with_in(
 *       "SELECT * FROM users WHERE id IN (?) AND status=?",
 *       [1,2,3], "i", ["active"], "s"
 *   );
 *   $stmt = mysqli_prepare($con,$sql);
 *   mysqli_stmt_bind_param($stmt,$types,...$params);
 */
function sql_with_in($sql, array $inParams, $inTypes, array $tailParams = [], $tailTypes = ''){
    if (empty($inParams)) throw new InvalidArgumentException('IN list cannot be empty');
    $placeholders = implode(',', array_fill(0, count($inParams), '?'));
    $sql = preg_replace('/\(\?\)/', "($placeholders)", $sql, 1);
    $types = str_repeat($inTypes, count($inParams)) . $tailTypes;
    $params = array_merge($inParams, $tailParams);
    return [$sql, $types, $params];
}

// ---------------------------
// Error logging + global handlers
// ---------------------------
function log_custom_error($level, $message, $file, $line) {
    global $con;
    if(!$con){ return; }
    $stmt = mysqli_prepare($con,
        "INSERT INTO `error_log` (error_level, error_message, file_path, line_number, status)
         VALUES (?, ?, ?, ?, 'new')");
    if (!$stmt) { return; } // avoid recursion
    mysqli_stmt_bind_param($stmt, "sssi", $level, $message, $file, $line);
    @mysqli_stmt_execute($stmt);
}

/** Call this ONCE in your bootstrap (after DB connect) */
function setup_error_handlers(){
    set_error_handler(function($errno, $errstr, $errfile, $errline){
        if (!(error_reporting() & $errno)) return false; // respect @
        log_custom_error('PHP_ERROR_'.$errno, $errstr, $errfile, $errline);
        return false; // allow default logging too
    });
    set_exception_handler(function($e){
        log_custom_error('PHP_EXCEPTION', $e->getMessage(), $e->getFile(), $e->getLine());
    });
}

// ---------------------------
// Email (PHPMailer)
// ---------------------------
function send_email($email, $html, $subject){
	$mail = new PHPMailer(true);
	try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = 'SMTP_USER_PLACEHOLDER';
        $mail->Password   = 'SMTP_PASS_PLACEHOLDER';
        $mail->SMTPSecure = 'tls'; // e.g. 'tls'
        $mail->Port       = 587;

        $mail->setFrom('noreply@domain.com', 'ServeDoor');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;

        // Keep verification ON in production
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false
            ]
        ];

        $mail->send();
        return true;
    } catch (Throwable $e){
        log_custom_error('EMAIL_ERROR', 'Mailer: '.$e->getMessage(), __FILE__, __LINE__);
        return false;
    }
}

// ---------------------------
// Authentication (hardened)
// ---------------------------
function loginUser($user_id, $user_name) {
    global $con;
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    session_regenerate_id(true);

    $_SESSION['FOOD_USER_ID']   = (int)$user_id;
    $_SESSION['FOOD_USER_NAME'] = (string)$user_name;
    $_SESSION['last_activity']  = time();
    $_SESSION['ua_sig']         = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $_SESSION['ip_prefix']      = implode('.', array_slice(explode('.', $_SERVER['REMOTE_ADDR'] ?? ''), 0, 2));

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = mysqli_prepare($con, "UPDATE `user` SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
    if (!$stmt) {
        log_custom_error('DB_PREPARE', 'loginUser prepare failed', __FILE__, __LINE__);
        return;
    }
    mysqli_stmt_bind_param($stmt, "si", $ip_address, $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        log_custom_error('DB_WRITE', 'Failed to update last_login for user: '.$user_id, __FILE__, __LINE__);
    }
    // keep session open for rest of request
}

function logoutUser() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}

function isUserLoggedIn() {
    if (empty($_SESSION['FOOD_USER_ID'])) return false;
    if (($_SESSION['ua_sig'] ?? '') !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '')) return false;
    if (($_SESSION['ip_prefix'] ?? '') !== implode('.', array_slice(explode('.', $_SERVER['REMOTE_ADDR'] ?? ''), 0, 2))) return false;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) return false; // 30 min
    $_SESSION['last_activity'] = time();
    return true;
}

// ---------------------------
// Cart & Dish
// ---------------------------
function manageUserCart($uid, $qty, $attr_id){
    global $con;
    $stmt = mysqli_prepare($con, "SELECT id FROM dish_cart WHERE user_id=? AND dish_detail_id=?");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'manageUserCart select', __FILE__, __LINE__); return; }
    mysqli_stmt_bind_param($stmt, "ii", $uid, $attr_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        if ($qty <= 0){
            $del = mysqli_prepare($con, "DELETE FROM dish_cart WHERE id=?");
            if (!$del) { log_custom_error('DB_PREPARE', 'manageUserCart delete', __FILE__, __LINE__); return; }
            mysqli_stmt_bind_param($del, "i", $row['id']);
            if (!mysqli_stmt_execute($del)) {
                log_custom_error('DB_WRITE', 'Failed to delete cart item id: '.$row['id'], __FILE__, __LINE__);
            }
        } else {
            $upd = mysqli_prepare($con, "UPDATE dish_cart SET qty=? WHERE id=?");
            if (!$upd) { log_custom_error('DB_PREPARE', 'manageUserCart update', __FILE__, __LINE__); return; }
            mysqli_stmt_bind_param($upd, "ii", $qty, $row['id']);
            if (!mysqli_stmt_execute($upd)) {
                log_custom_error('DB_WRITE', 'Failed to update cart qty for user: '.$uid, __FILE__, __LINE__);
            }
        }
    } elseif ($qty > 0){
        $added_on = date('Y-m-d H:i:s');
        $ins = mysqli_prepare($con, "INSERT INTO dish_cart(user_id, dish_detail_id, qty, added_on) VALUES(?,?,?,?)");
        if (!$ins) { log_custom_error('DB_PREPARE', 'manageUserCart insert', __FILE__, __LINE__); return; }
        mysqli_stmt_bind_param($ins, "iiis", $uid, $attr_id, $qty, $added_on);
        if (!mysqli_stmt_execute($ins)) {
            log_custom_error('DB_WRITE', 'Failed to insert item into cart for user: '.$uid, __FILE__, __LINE__);
        }
    }
}

function getUserCart(){
    global $con;
    $arr = [];
    if (!isset($_SESSION['FOOD_USER_ID'])) return $arr;
    $id = (int)$_SESSION['FOOD_USER_ID'];
    $stmt = mysqli_prepare($con, "SELECT * FROM dish_cart WHERE user_id=?");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getUserCart', __FILE__, __LINE__); return $arr; }
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while($row=mysqli_fetch_assoc($res)){
        $arr[] = $row;
    }
    return $arr;
}

function getUserFullCart($attr_id = ''){
    global $con;
    $cartArr = [];
    $ids = [];

    if(isset($_SESSION['FOOD_USER_ID'])){
        foreach(getUserCart() as $list){
            $cartArr[$list['dish_detail_id']]['qty'] = (int)$list['qty'];
            $ids[] = (int)$list['dish_detail_id'];
        }
    } else if (!empty($_SESSION['cart'])){
        foreach($_SESSION['cart'] as $key => $val){
            $cartArr[(int)$key]['qty'] = (int)($val['qty'] ?? 1);
            $ids[] = (int)$key;
        }
    }

    if(empty($ids)){ return $attr_id !== '' ? 0 : []; }

    try{
        [$sql, $types, $params] = sql_with_in(
            "SELECT dd.id, dd.price, d.dish, d.image, d.restaurant_id
             FROM dish_details dd JOIN dish d ON dd.dish_id = d.id
             WHERE dd.id IN (?)",
            $ids, 'i'
        );
    } catch (Throwable $e){
        log_custom_error('UTIL_ERROR', 'sql_with_in: '.$e->getMessage(), __FILE__, __LINE__);
        return $attr_id !== '' ? 0 : [];
    }

    $stmt_details = mysqli_prepare($con, $sql);
    if (!$stmt_details) { log_custom_error('DB_PREPARE', 'getUserFullCart details', __FILE__, __LINE__); return $attr_id !== '' ? 0 : []; }
    mysqli_stmt_bind_param($stmt_details, $types, ...$params);
    mysqli_stmt_execute($stmt_details);
    $details_res = mysqli_stmt_get_result($stmt_details);

    while($r = mysqli_fetch_assoc($details_res)){
        $id = (int)$r['id'];
        if(isset($cartArr[$id])){
            $cartArr[$id]['price'] = (float)$r['price'];
            $cartArr[$id]['dish']  = $r['dish'];
            $cartArr[$id]['image'] = $r['image'];
            $cartArr[$id]['restaurant_id'] = (int)$r['restaurant_id'];
        }
    }
    return $attr_id !== '' ? (int)($cartArr[$attr_id]['qty'] ?? 0) : $cartArr;
}

function getcartTotalPrice(){
    $cartArr = getUserFullCart();
    $totalPrice = 0.0;
    foreach($cartArr as $list){
        $totalPrice += ((int)($list['qty'] ?? 1)) * ((float)($list['price'] ?? 0));
    }
    return $totalPrice;
}

function getDishDetailById($id){
    global $con;
    $stmt = mysqli_prepare($con, "SELECT d.dish, d.image, dd.price FROM dish_details dd JOIN dish d ON d.id=dd.dish_id WHERE dd.id=?");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getDishDetailById', __FILE__, __LINE__); return []; }
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($res) ?: [];
}

function removeDishFromCartByid($id){
    if(isset($_SESSION['FOOD_USER_ID'])){
        global $con;
        $uid = (int)$_SESSION['FOOD_USER_ID'];
        $stmt = mysqli_prepare($con, "DELETE FROM dish_cart WHERE dish_detail_id = ? AND user_id = ?");
        if (!$stmt) { log_custom_error('DB_PREPARE', 'removeDishFromCartByid', __FILE__, __LINE__); return; }
        mysqli_stmt_bind_param($stmt, "ii", $id, $uid);
        if (!mysqli_stmt_execute($stmt)) {
            log_custom_error('DB_WRITE', 'Failed to remove dish from cart', __FILE__, __LINE__);
        }
    } else {
        unset($_SESSION['cart'][$id]);
    }
}

function emptyCart(){
    if(isset($_SESSION['FOOD_USER_ID'])){
        global $con;
        $uid = (int)$_SESSION['FOOD_USER_ID'];
        $stmt = mysqli_prepare($con, "DELETE FROM dish_cart WHERE user_id = ?");
        if (!$stmt) { log_custom_error('DB_PREPARE', 'emptyCart', __FILE__, __LINE__); return; }
        mysqli_stmt_bind_param($stmt, "i", $uid);
        if (!mysqli_stmt_execute($stmt)) {
            log_custom_error('DB_WRITE', 'Failed to empty cart', __FILE__, __LINE__);
        }
    } else {
        unset($_SESSION['cart']);
    }
}

function getDishCartStatus(){
    global $con;
    $dishDetailsID=[];
    if(isset($_SESSION['FOOD_USER_ID'])){
        foreach(getUserCart() as $list){
            $dishDetailsID[]=(int)$list['dish_detail_id'];
        }
    } elseif(isset($_SESSION['cart']) && count($_SESSION['cart'])>0){
        foreach($_SESSION['cart'] as $key=>$val){
            $dishDetailsID[]=(int)$key;
        }
    }
    foreach($dishDetailsID as $id){
        $stmt = mysqli_prepare($con, "SELECT dd.status, d.status as dish_status, d.id FROM dish_details dd, dish d WHERE dd.id=? AND dd.dish_id=d.id");
        if (!$stmt) { log_custom_error('DB_PREPARE', 'getDishCartStatus select', __FILE__, __LINE__); continue; }
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if(!$row) continue;

        if((int)$row['dish_status']===0){
            $dish_id=(int)$row['id'];
            $stmt2 = mysqli_prepare($con, "SELECT id FROM dish_details WHERE dish_id=?");
            if ($stmt2){
                mysqli_stmt_bind_param($stmt2, "i", $dish_id);
                mysqli_stmt_execute($stmt2);
                $res1 = mysqli_stmt_get_result($stmt2);
                while($row1=mysqli_fetch_assoc($res1)){
                    removeDishFromCartByid((int)$row1['id']);
                }
            }
        }
        if ((int)$row['status']===0){
            removeDishFromCartByid($id);
        }
    }
}

// ---------------------------
// User / Orders / Ratings / Settings
// ---------------------------
function getUserDetailsByid($uid=''){
    global $con;
    $data = ['name'=>'', 'email'=>'', 'mobile'=>'', 'referral_code'=>''];
    if(isset($_SESSION['FOOD_USER_ID'])){ $uid = (int)$_SESSION['FOOD_USER_ID']; }
    if(empty($uid)){ return $data; }
    $stmt = mysqli_prepare($con, "SELECT `name`,`email`,`mobile`,`referral_code` FROM `user` WHERE `id` = ?");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getUserDetailsByid', __FILE__, __LINE__); return $data; }
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if($res && mysqli_num_rows($res) > 0){ $data = mysqli_fetch_assoc($res); }
    return $data;
}

function getOrderById($oid){
    global $con;
    if(empty($oid)){ return null; }
    $stmt = mysqli_prepare($con, "SELECT * FROM `order_master` WHERE `id` = ?");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getOrderById', __FILE__, __LINE__); return null; }
    mysqli_stmt_bind_param($stmt, "i", $oid);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

function getOrderDetails($oid){
    global $con;
    if(empty($oid)){ return []; }
    $sql = "SELECT od.price, od.qty, dd.attribute, d.dish
            FROM `order_detail` od
            JOIN `dish_details` dd ON od.dish_details_id = dd.id
            JOIN `dish` d ON dd.dish_id = d.id
            WHERE od.order_id = ?";
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getOrderDetails', __FILE__, __LINE__); return []; }
    mysqli_stmt_bind_param($stmt, "i", $oid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = [];
    while($row = mysqli_fetch_assoc($res)){ $data[] = $row; }
    return $data;
}

function getDeliveryBoyNameById($id){
    global $con;
    if(empty($id)){ return 'Not Assigned'; }
    $stmt = mysqli_prepare($con, "SELECT name, mobile FROM delivery_boy WHERE id = ?");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getDeliveryBoyNameById', __FILE__, __LINE__); return 'Not Assigned'; }
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if($res && mysqli_num_rows($res) > 0){
        $row = mysqli_fetch_assoc($res);
        return $row['name'].' ('.$row['mobile'].')';
    }
    return 'Not Assigned';
}

function getSetting(){
    global $con;
    $one = 1;
    $stmt = mysqli_prepare($con, "SELECT * FROM setting WHERE id=?");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getSetting', __FILE__, __LINE__); return []; }
    mysqli_stmt_bind_param($stmt, "i", $one);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
}

function getRatingList($did,$oid){
    $arr=array('Bad','Below Average','Average','Good','Very Good');
    $html='<select onchange=updaterating("'.$did.'","'.$oid.'") id="rate'.$did.'">';
        $html.='<option value="">Select Rating</option>';
        foreach($arr as $key=>$val){
            $id=$key+1;
            $html.="<option value='$id'>$val</option>";
        }
    $html.='</select>';
    return $html;
}

function getRating($did, $oid){
    global $con;
    $stmt = mysqli_prepare($con, "SELECT rating FROM rating WHERE order_id=? AND dish_detail_id=?");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getRating', __FILE__, __LINE__); return; }
    mysqli_stmt_bind_param($stmt, "ii", $oid, $did);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if($res && mysqli_num_rows($res) > 0){
        $row = mysqli_fetch_assoc($res);
        $rating = (int)$row['rating'];
        $arr = array('','Bad','Below Average','Average','Good','Very Good');
        echo "<div class='set_rating'>".($arr[$rating] ?? 'Rated')."</div>";
    } else {
        echo getRatingList($did,$oid);
    }
}

function getRatingByDishId($id){
    global $con;
    $stmt_details = mysqli_prepare($con, "SELECT id FROM dish_details WHERE dish_id=?");
    if (!$stmt_details) { log_custom_error('DB_PREPARE', 'getRatingByDishId details', __FILE__, __LINE__); return; }
    mysqli_stmt_bind_param($stmt_details, "i", $id);
    mysqli_stmt_execute($stmt_details);
    $res_details = mysqli_stmt_get_result($stmt_details);
    $dish_detail_ids = [];
    while($row = mysqli_fetch_assoc($res_details)){ $dish_detail_ids[] = (int)$row['id']; }
    if(empty($dish_detail_ids)){ return; }

    try{
        [$sql_rating, $types, $params] = sql_with_in(
            "SELECT SUM(rating) as rating, COUNT(*) as total FROM rating WHERE dish_detail_id IN (?)",
            $dish_detail_ids, 'i'
        );
    } catch (Throwable $e){
        log_custom_error('UTIL_ERROR', 'sql_with_in rating: '.$e->getMessage(), __FILE__, __LINE__);
        return;
    }

    $stmt_rating = mysqli_prepare($con, $sql_rating);
    if (!$stmt_rating) { log_custom_error('DB_PREPARE', 'getRatingByDishId rating', __FILE__, __LINE__); return; }
    mysqli_stmt_bind_param($stmt_rating, $types, ...$params);
    mysqli_stmt_execute($stmt_rating);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_rating));

    if($row && (int)$row['total'] > 0){
        $totalRate = $row['rating'] / $row['total'];
        $arr = array('','Bad','Below Average','Average','Good','Very Good');
        echo "<span class='rating'> (".$arr[(int)round($totalRate)]." rated by ".(int)$row['total']." users)</span>";
    }
}

// ---------------------------
// Wallet & Sales - FIXED VERSION
// ---------------------------
function manageWallet($uid, $amt, $type, $msg, $payment_id=''){
    global $con;
    
    // Debug logging
    error_log("manageWallet called: uid=$uid, amt=$amt, type=$type, msg=$msg, payment_id=$payment_id");
    
    $type = strtolower($type);
    if (!in_array($type, ['in','out'], true)) {
        log_custom_error('WALLET_INPUT', "Invalid wallet type: $type", __FILE__, __LINE__);
        return false;
    }
    if (!is_numeric($amt) || $amt < 0) {
        log_custom_error('WALLET_INPUT', "Invalid wallet amount: $amt", __FILE__, __LINE__);
        return false;
    }
    
    $added_on = date('Y-m-d H:i:s');
    
    // If payment_id is empty, set it to NULL to avoid unique constraint violation
    if (empty($payment_id)) {
        $payment_id = NULL;
        $sql = "INSERT INTO wallet(user_id, amt, msg, type, added_on, payment_id) VALUES (?, ?, ?, ?, ?, NULL)";
    } else {
        $sql = "INSERT INTO wallet(user_id, amt, msg, type, added_on, payment_id) VALUES (?, ?, ?, ?, ?, ?)";
    }
    
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) { 
        $error_msg = mysqli_error($con);
        log_custom_error('DB_PREPARE', 'manageWallet prepare failed: ' . $error_msg, __FILE__, __LINE__); 
        error_log("SQL Prepare failed: " . $error_msg);
        return false; 
    }
    
    if (empty($payment_id)) {
        // Bind without payment_id (it will be NULL)
        $bind_result = mysqli_stmt_bind_param($stmt, "idsss", $uid, $amt, $msg, $type, $added_on);
    } else {
        // Bind with payment_id
        $bind_result = mysqli_stmt_bind_param($stmt, "idssss", $uid, $amt, $msg, $type, $added_on, $payment_id);
    }
    
    if (!$bind_result) {
        $bind_error = mysqli_stmt_error($stmt);
        log_custom_error('DB_BIND', 'manageWallet bind failed: ' . $bind_error, __FILE__, __LINE__);
        error_log("Bind failed: " . $bind_error);
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $execute_result = mysqli_stmt_execute($stmt);
    if (!$execute_result) {
        $execute_error = mysqli_stmt_error($stmt);
        log_custom_error('DB_WRITE', 'Failed to add wallet txn for user: '.$uid.' - Error: ' . $execute_error, __FILE__, __LINE__);
        error_log("Execute failed: " . $execute_error);
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    error_log("Wallet transaction successful. Affected rows: " . $affected_rows);
    return true;
}

function getWallet($uid){
    global $con;
    $stmt = mysqli_prepare($con, "SELECT * FROM wallet WHERE user_id = ? ORDER BY id DESC");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getWallet', __FILE__, __LINE__); return []; }
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $arr = [];
    while($row = mysqli_fetch_assoc($res)){ $arr[] = $row; }
    return $arr;
}

function getWalletAmt($uid){
    global $con;
    $stmt = mysqli_prepare($con, "SELECT type, amt FROM wallet WHERE user_id = ?");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getWalletAmt', __FILE__, __LINE__); return 0.0; }
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $in = 0.0; $out = 0.0;
    while($row = mysqli_fetch_assoc($res)){
        if($row['type'] === 'in')  $in  += (float)$row['amt'];
        if($row['type'] === 'out') $out += (float)$row['amt'];
    }
    return $in - $out;
}

function getSale($start, $end){
    global $con;
    $stmt = mysqli_prepare($con, "SELECT SUM(final_price) as final_price FROM order_master WHERE added_on BETWEEN ? AND ? AND order_status=4");
    if (!$stmt) { log_custom_error('DB_PREPARE', 'getSale', __FILE__, __LINE__); return '0 Rs'; }
    mysqli_stmt_bind_param($stmt, "ss", $start, $end);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $sum = (float)($row['final_price'] ?? 0);
    return $sum.' Rs'; // keep backwards compatible
}

// ---------------------------
// Order email (complete HTML template)
// ---------------------------
function orderEmail($oid, $uid=''){
    $user = getUserDetailsByid($uid);
    $name = $user['name'] ?? '';
    $ord  = getOrderById($oid);
    if(!$ord) return '';

    $order_id = (int)$ord['id'];
    $details  = getOrderDetails($oid);

    $total_price = 0.0;
    foreach($details as $it){ $total_price += ((int)$it['qty']) * ((float)$it['price']); }

    $coupon_row = '';
    if(!empty($ord['coupon_code'])){
        $coupon_value = ((float)$ord['total_price']) - ((float)$ord['final_price']);
        $coupon_row = '<tr><td colspan="2">Coupon ('.htmlspecialchars($ord['coupon_code'],ENT_QUOTES,'UTF-8').')</td><td>-'.number_format($coupon_value,2).'</td></tr>'
                    . '<tr><td colspan="2"><strong>Final Total</strong></td><td><strong>'.number_format((float)$ord['final_price'],2).'</strong></td></tr>';
    }

    $rows = '';
    foreach($details as $it){
        $amt = ((int)$it['qty']) * ((float)$it['price']);
        $rows .= '<tr>'
            . '<td>'.htmlspecialchars($it['dish'].' ('.$it['attribute'].')',ENT_QUOTES,'UTF-8').'</td>'
            . '<td>'.(int)$it['qty'].'</td>'
            . '<td>'.number_format($amt,2).'</td>'
        . '</tr>';
    }

    $html = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0" />'
          . '<style>body{font-family:Arial,sans-serif;margin:0;padding:16px;background:#f7f7f7}'
          . '.card{max-width:640px;margin:auto;background:#fff;border:1px solid #eee;border-radius:8px;overflow:hidden}'
          . '.header{background:#111;color:#fff;padding:16px 20px;font-size:18px;font-weight:bold}'
          . '.content{padding:20px}'
          . 'table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}'
          . 'th{background:#fafafa} .total-row td{font-weight:bold}'
          . '</style></head><body>'
          . '<div class="card">'
          . '<div class="header">ServeDoor • Order Invoice</div>'
          . '<div class="content">'
          . '<p>Hi '.htmlspecialchars($name,ENT_QUOTES,'UTF-8').',</p>'
          . '<p>Thank you for your order <strong>#'.htmlspecialchars($order_id,ENT_QUOTES,'UTF-8').'</strong>.</p>'
          . '<table><thead><tr><th>Description</th><th>Qty</th><th>Amount</th></tr></thead><tbody>'
          . $rows
          . '<tr class="total-row"><td colspan="2">Total</td><td>'.number_format($total_price,2).'</td></tr>'
          . $coupon_row
          . '</tbody></table>'
          . '<p style="color:#666">This is an automated invoice. For support, reply to this email.</p>'
          . '</div></div></body></html>';

    return $html;
}

// ---------------------------
// WhatsApp (Fast2SMS)
// ---------------------------
function sendWhatsAppNotification($toNumber, $orderId, $price, $paymentMode) {
    $apiKey     = FAST2SMS_AUTH;
    $templateId = '6159'; // your approved template id
    $variables  = implode('|', [$orderId, $price, ucfirst($paymentMode)]);

    $apiUrl = "https://www.fast2sms.com/dev/whatsapp"
            . "?authorization="    . rawurlencode($apiKey)
            . "&message_id="       . rawurlencode($templateId)
            . "&numbers="          . rawurlencode($toNumber)
            . "&variables_values=" . rawurlencode($variables);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,  // keep ON
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err || $httpcode !== 200) {
        log_custom_error('API_ERROR', "WhatsApp API failed. HTTP:$httpcode; To:$toNumber; Err:$err; Resp:$response", __FILE__, __LINE__);
        return false;
    }
    $json = json_decode($response, true);
    if (!is_array($json)) {
        log_custom_error('API_ERROR', "WhatsApp response not JSON: ".$response, __FILE__, __LINE__);
        return false;
    }
    return $json; // or true if you don't need payload
}

// ---------------------------
// Address Management Functions
// ---------------------------
function getUserAddresses($uid) {
    global $con;
    $stmt = mysqli_prepare($con, "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    if (!$stmt) { 
        log_custom_error('DB_PREPARE', 'getUserAddresses', __FILE__, __LINE__); 
        return []; 
    }
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $addresses = [];
    while($row = mysqli_fetch_assoc($res)) {
        $addresses[] = $row;
    }
    return $addresses;
}

function getDefaultUserAddress($uid) {
    global $con;
    $stmt = mysqli_prepare($con, "SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1 ORDER BY id DESC LIMIT 1");
    if (!$stmt) { 
        log_custom_error('DB_PREPARE', 'getDefaultUserAddress', __FILE__, __LINE__); 
        return null; 
    }
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($res) ?: null;
}

// ---------------------------
// Restaurant Functions
// ---------------------------
function getRestaurantById($id) {
    global $con;
    $stmt = mysqli_prepare($con, "SELECT * FROM restaurants WHERE id = ?");
    if (!$stmt) { 
        log_custom_error('DB_PREPARE', 'getRestaurantById', __FILE__, __LINE__); 
        return null; 
    }
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($res) ?: null;
}

function getRestaurantCoordinates($restaurant_id) {
    global $con;
    $stmt = mysqli_prepare($con, "SELECT lat, lng FROM restaurants WHERE id = ?");
    if (!$stmt) { 
        log_custom_error('DB_PREPARE', 'getRestaurantCoordinates', __FILE__, __LINE__); 
        return ['lat' => 0, 'lng' => 0]; 
    }
    mysqli_stmt_bind_param($stmt, "i", $restaurant_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    return $row ?: ['lat' => 0, 'lng' => 0];
}
?>