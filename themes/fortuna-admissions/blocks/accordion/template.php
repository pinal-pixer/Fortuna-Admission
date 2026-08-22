<?php

/**
 * Accordion
 */

  $style = get_field('style');
  $numbered = get_field('numbered');
?>

<?php if ( have_rows('accordion') ): ?>
  <div class="accordion<?= $numbered ? ' accordions-numbered' : '' ?><?= $style === 'boxed' ? ' accordion-boxed' : ' accordion-inline' ?>">
    <?php while ( have_rows('accordion') ):
      the_row();
      $title = get_sub_field('title');
      $body = get_sub_field('body');
    ?>
      <div class="accordion-item">
        <div class="accordion-item__toggle">
          <div class="accordion-item__title">
            <?= $title ?>
          </div>
          <div class="accordion-item__icon">
            <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"></path></svg>
          </div>
        </div>
        <div class="accordion-item__body">
          <div class="accordion-item__content-wrapper">
            <div class="accordion-item__content">
              <?= $body ?>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
<?php endif; ?>