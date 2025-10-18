<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

include ("header.php");

if(!isset($_SESSION['FOOD_USER_ID'])){
    redirect(FRONT_SITE_PATH.'login_register');
    die();
}
$uid = $_SESSION['FOOD_USER_ID'];

if($website_close==1){
    redirect(FRONT_SITE_PATH.'shop');
}

if(isset($_POST['update_cart'])){
    foreach($_POST['qty'] as $key=>$val){
        $quantity = max(1, intval($val)); 
        manageUserCart($uid, $quantity, intval($key));
    }
    redirect(FRONT_SITE_PATH.'cart');
    die();
}

$cartArr = getUserFullCart();
$totalPrice = 0;
if(!empty($cartArr)){
    foreach($cartArr as $list){
        $totalPrice += (int)$list['qty'] * (float)$list['price'];
    }
}
?>

<style>
/* CSS to hide the default arrows in the number input */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 border-b theme-border pb-3">Your Cart</h1>

    <?php if(count($cartArr) > 0): ?>
        <form method="post">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2">
                    <div class="theme-card p-4 sm:p-6 rounded-lg shadow-lg">
                        <div class="space-y-4">
                            <?php foreach($cartArr as $key=>$list): ?>
                                <div class="flex flex-col sm:flex-row items-center gap-4 border-b theme-border pb-4 last:border-0 last:pb-0">
                                    <img src="<?php echo SITE_DISH_IMAGE.$list['image']?>" alt="<?php echo htmlspecialchars($list['dish']); ?>" class="w-24 h-24 sm:w-20 sm:h-20 rounded-lg object-cover">
                                    
                                    <div class="flex-1 text-center sm:text-left">
                                        <h4 class="font-medium"><?php echo htmlspecialchars($list['dish']); ?></h4>
                                        <p class="text-sm theme-muted"><?php echo htmlspecialchars($list['price']); ?> Rs</p>
                                    </div>

                                    <div class="w-32">
                                        <div class="flex items-center justify-center border theme-border rounded-lg">
                                            <button type="button" onclick="changeQty(this, -1)" class="px-3 py-1 text-lg font-bold cursor-pointer">-</button>
                                            <input type="number" name="qty[<?php echo $key?>]" value="<?php echo htmlspecialchars($list['qty']);?>" class="w-12 text-center bg-transparent border-l border-r theme-border outline-none">
                                            <button type="button" onclick="changeQty(this, 1)" class="px-3 py-1 text-lg font-bold cursor-pointer">+</button>
                                        </div>
                                    </div>
                                    
                                    <div class="w-24 text-center font-medium">
                                        <?php echo (int)$list['qty'] * (float)$list['price']; ?> Rs
                                    </div>

                                    <button type="button" onclick="delete_cart('<?php echo $key?>','load')" class="text-red-500 hover:text-red-400" title="Remove Item">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <a href="<?php echo FRONT_SITE_PATH?>shop" class="font-medium hover:underline" style="color:var(--primary-color);">
                                ← Continue Shopping
                            </a>
                            <button type="submit" name="update_cart" class="text-white font-bold py-2 px-6 rounded-lg" style="background:var(--primary-color)">
                                Update Cart
                            </button>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="theme-card p-6 rounded-lg shadow-lg sticky top-24">
                        <h2 class="text-xl font-bold border-b theme-border pb-3 mb-4">Order Summary</h2>
                        <div class="space-y-2">
                            <div class="flex justify-between theme-muted">
                                <span>Subtotal</span>
                                <span><?php echo $totalPrice?> Rs</span>
                            </div>
                            <div class="flex justify-between theme-muted">
                                <span>Delivery Fee</span>
                                <span>TBD</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg border-t theme-border pt-2 mt-2">
                                <span>Total</span>
                                <span><?php echo $totalPrice?> Rs</span>
                            </div>
                        </div>
                        <div class="d-grid mt-6">
                            <a href="<?php echo FRONT_SITE_PATH?>checkout" class="w-full text-center text-white font-bold py-3 px-6 rounded-lg text-lg" style="background:var(--primary-color)">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    <?php else: ?>
        <div class="theme-card p-8 rounded-lg shadow-lg text-center">
            <span class="material-symbols-outlined text-6xl theme-muted">shopping_cart</span>
            <h2 class="mt-4 text-2xl font-bold">Your Cart is Empty</h2>
            <p class="mt-2 theme-muted">Looks like you haven't added anything to your cart yet.</p>
            <a href="<?php echo FRONT_SITE_PATH?>shop" class="mt-6 inline-block text-white font-bold py-3 px-6 rounded-lg" style="background:var(--primary-color)">
                Shop Now
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function changeQty(button, delta) {
    var inputField = button.parentElement.querySelector('input[type="number"]');
    var currentValue = parseInt(inputField.value);
    var newValue = currentValue + delta;

    if (newValue >= 1) {
        inputField.value = newValue;
    }
}
</script>

<?php
include("footer.php");
?>