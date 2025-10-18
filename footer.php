<?php /* footer.php (Tailwind dark theme) */ ?>
</main>

<footer class="px-4 md:px-8 py-8 border-t theme-border theme-surface mt-8">
  <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
    <div class="text-center md:text-left">
      <p class="text-sm theme-muted">
        © <?php echo date('Y');?> <span class="font-semibold"><?php echo FRONT_SITE_NAME; ?></span>. All rights reserved.
      </p>
    </div>

    <div class="flex justify-center gap-6 text-sm">
      <a class="theme-muted hover:text-white" href="<?php echo FRONT_SITE_PATH?>about-us">About</a>
      <a class="theme-muted hover:text-white" href="<?php echo FRONT_SITE_PATH?>contact-us">Contact</a>
      <a class="theme-muted hover:text-white" href="<?php echo FRONT_SITE_PATH?>privacy">Privacy</a>
      <a class="theme-muted hover:text-white" href="<?php echo FRONT_SITE_PATH?>terms">Terms</a>
    </div>

    <div class="flex md:justify-end justify-center gap-3">
      <a href="https://www.facebook.com/share/1D2DjoN6i6" class="rounded-full size-9 flex items-center justify-center theme-surface border theme-border hover:bg-blue-600 hover:border-blue-600 transition-colors" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
        <i class="fab fa-facebook-f text-sm"></i>
      </a>
      <a href="https://www.instagram.com/servedoor" class="rounded-full size-9 flex items-center justify-center theme-surface border theme-border hover:bg-pink-600 hover:border-pink-600 transition-colors" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
        <i class="fab fa-instagram text-sm"></i>
      </a>
      <a href="https://www.youtube.com/@serveDoor" class="rounded-full size-9 flex items-center justify-center theme-surface border theme-border hover:bg-red-600 hover:border-red-600 transition-colors" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
        <i class="fab fa-youtube text-sm"></i>
      </a>
    </div>
  </div>
</footer>

</div>

<!-- Add Font Awesome CDN in head or before using icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script src="<?php echo FRONT_SITE_PATH?>assets/js/vendor/jquery-1.12.0.min.js"></script>

<script>
  var FRONT_SITE_PATH = "<?php echo FRONT_SITE_PATH?>";
  var SITE_DISH_IMAGE = "<?php echo SITE_DISH_IMAGE?>";
</script>

<script src="<?php echo FRONT_SITE_PATH?>assets/js/custom.js?v=101"></script>

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

</body>
</html>