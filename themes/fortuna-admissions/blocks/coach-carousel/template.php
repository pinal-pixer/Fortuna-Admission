<?php

/**
 * Coach Carousel
 */
$hide_top = get_field('hide_top_row');
$hide_bottom = get_field('hide_bottom_row');
$coaches_top = get_field('coaches_top');
$coaches_bottom = get_field('coaches_bottom');
?>

<div class="coach-carousel">
  <?php
    if (!$hide_top && $coaches_top) {
      $position = 'top';
      $coaches = $coaches_top;
      $template_path = get_stylesheet_directory() . '/partials/coach-carousel-card.php';

      if (file_exists($template_path)) {
          include $template_path;
      }
    }
  
    if (!$hide_bottom && $coaches_bottom) {
      $position = 'bottom';
      $coaches = $coaches_bottom;
      $template_path = get_stylesheet_directory() . '/partials/coach-carousel-card.php';

      if (file_exists($template_path)) {
          include $template_path;
      }
    }
  ?>
</div>