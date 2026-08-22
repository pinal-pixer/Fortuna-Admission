<?php

/**
 * Mobile Menu
 */

$mobile_menu = get_field('mobile_menu');
?>

<div class="mobile-menu">
  <button class="mobile-menu-toggle" data-micromodal-trigger="mobile-menu-modal" aria-label="Open mobile menu">
    <img src="<?= get_stylesheet_directory_uri() ?>/assets/menu.svg" alt="Hamburger Menu Icon" />
  </button>
  <button class="mobile-menu-close hide" aria-label="Close mobile menu"></button>
  <div class="modal micromodal-slide mobile-menu-modal" id="mobile-menu-modal" aria-hidden="true">
    <div class="modal__overlay" tabindex="-1" data-micromodal-close>
      <div class="modal__container" role="dialog" aria-modal="true">
        <div class="modal__content">
          <?php echo do_shortcode($mobile_menu); ?>
        </div>
      </div>
    </div>
  </div>
</div>