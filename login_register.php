<?php
// --- SETTINGS FOR THIS PAGE ---
$HIDE_LOGO_ICON = true;      // This will hide the logo icon
$HIDE_CART_ICON = true;      // This will hide the cart icon
$COMPACT_HEADER = true;      // This makes the header smaller
include('header.php');

// If already logged in, redirect
if(isset($_SESSION['FOOD_USER_ID']) && $_SESSION['FOOD_USER_ID'] > 0){
    redirect(FRONT_SITE_PATH.'shop');
}
?>

<div class="flex flex-col items-center justify-center min-h-full px-6 py-8 mx-auto">
    <div class="w-full theme-card rounded-lg shadow-xl sm:max-w-md">
        <div class="p-6 space-y-6">
            <header class="flex items-center justify-center gap-3">
                <h1 class="text-2xl font-bold">ServeDoor</h1>
            </header>

            <div class="text-center">
                <h2 class="text-xl font-bold">Login / Register</h2>
                <p class="theme-muted">Enter your mobile number to get an OTP</p>
            </div>

            <form id="frmSendOTP" class="space-y-4">
                <div>
                    <label for="mobile" class="block mb-2 text-sm font-medium">Mobile Number</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 text-sm theme-surface border theme-border border-r-0 rounded-l-md">+91</span>
                        <input type="tel" id="mobile" name="mobile" maxlength="10" pattern="[0-9]{10}" required
                               class="theme-input rounded-none rounded-r-lg"
                               placeholder="Your mobile number">
                    </div>
                    <p id="otp_info" class="text-xs theme-muted mt-1 hidden">OTP sent. Please check your phone.</p>
                </div>
                <button type="submit"
                    class="w-full text-white font-medium rounded-lg text-sm px-5 py-2.5"
                    style="background:var(--primary-color)">
                    Get OTP
                </button>
            </form>

            <form id="frmVerifyOTP" class="space-y-4 hidden">
                <div class="text-center">
                    <p class="theme-muted">Enter the OTP sent to your mobile</p>
                </div>
                <div class="flex justify-center space-x-2" id="otp_inputs">
                    <?php for($i=0; $i<6; $i++){ ?>
                        <input name="otp[]" type="tel" maxlength="1" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" required
                               class="otp-input w-12 h-12 text-center text-xl font-bold theme-input">
                    <?php } ?>
                </div>
                <div id="name_wrap" class="hidden">
                    <label for="full_name" class="block mb-2 text-sm font-medium">Your Name</label>
                    <input type="text" id="full_name" name="full_name" class="theme-input" placeholder="Required for first-time signup">
                </div>
                <button type="submit"
                    class="w-full text-white font-medium rounded-lg text-sm px-5 py-2.5"
                    style="background:var(--primary-color)">
                    Verify OTP
                </button>
                <div class="text-sm text-center theme-muted">
                    <button type="button" id="resend_btn" class="font-medium hover:underline" style="color:var(--primary-color)" disabled>
                        Resend OTP in <span id="resend_sec">30</span>s
                    </button>
                </div>
                <div id="otp_msg" class="text-center text-sm"></div>
            </form>
        </div>
    </div>

    <footer class="mt-8 text-center theme-muted text-sm">
        <a class="hover:underline" href="#">Terms & Privacy</a>
        <p>© <?php echo date("Y"); ?> ServeDoor</p>
    </footer>
</div>

<script>
const otpInputs = document.querySelectorAll('#otp_inputs .otp-input');
otpInputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
        if (e.target.value && index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
        }
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            otpInputs[index - 1].focus();
        }
    });
});

const frmSend = document.getElementById('frmSendOTP');
const frmVerify = document.getElementById('frmVerifyOTP');
const resendBtn = document.getElementById('resend_btn');
const resendSec = document.getElementById('resend_sec');
const otpInfo = document.getElementById('otp_info');
const nameWrap = document.getElementById('name_wrap');
const otpMsg = document.getElementById('otp_msg');

let resendTimer = null;
function startResendCountdown(s=30){
  resendBtn.disabled = true;
  let left = s;
  resendSec.textContent = left;
  resendTimer = setInterval(()=>{
    left--;
    resendSec.textContent = left;
    if(left<=0){
      clearInterval(resendTimer);
      resendBtn.disabled = false;
      resendSec.textContent = '0';
    }
  },1000);
}

frmSend.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const mobile = document.getElementById('mobile').value.trim();
  if(!/^[0-9]{10}$/.test(mobile)){ alert('Enter valid 10-digit mobile'); return; }

  const r = await fetch('login_register_submit.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ mobile, type: 'send_otp' })
  });
  const j = await r.json();
  if(j.status === 'success'){
    otpInfo.classList.remove('hidden');
    frmSend.classList.add('hidden');
    frmVerify.classList.remove('hidden');
    if(j.need_name){ nameWrap.classList.remove('hidden'); }
    startResendCountdown(30);
  }else{
    alert(j.msg || 'Failed to send OTP');
  }
});

resendBtn.addEventListener('click', async ()=>{
  if(resendBtn.disabled) return;
  const mobile = document.getElementById('mobile').value.trim();
  const r = await fetch('login_register_submit.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ mobile, type: 'send_otp' })
  });
  const j = await r.json();
  if(j.status === 'success'){
    startResendCountdown(30);
  }else{
    alert(j.msg || 'Failed to resend OTP');
  }
});

frmVerify.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const mobile = document.getElementById('mobile').value.trim();
  const otp = Array.from(otpInputs).map(i=>i.value).join('');
  const full_name = document.getElementById('full_name').value.trim();
  if(!/^[0-9]{6}$/.test(otp)){ otpMsg.textContent='Enter 6-digit OTP'; otpMsg.className='text-center text-sm text-red-600'; return; }

  const r = await fetch('login_register_submit.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ mobile, otp, full_name, type: 'verify_otp' })
  });
  const j = await r.json();
  if(j.status === 'success'){
    // THIS IS THE ONLY LINE I CHANGED. I ADDED A SMALL DELAY.
    setTimeout(() => {
        window.location.href = "<?php echo FRONT_SITE_PATH?>shop";
    }, 100);
  }else{
    otpMsg.textContent = j.msg || 'Invalid OTP';
    otpMsg.className = 'text-center text-sm text-red-600';
  }
});
</script>

<?php 
include("footer.php"); 
?>