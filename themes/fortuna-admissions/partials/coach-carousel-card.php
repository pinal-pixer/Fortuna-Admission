<?php if (!defined('ABSPATH')) exit; ?>

<div class="coach-slider coach-slider--<?= $position ?>">

  <?php foreach ($coaches as $post):
    setup_postdata($post);
    $image = get_post_thumbnail_id($post);
    $organization_name = get_field('organization_name', $post);
    $related_organizations = get_field('related_organizations', $post);
  ?>
    <div class="coach-card">
      <div class="coach-card__image">
        <img <?php responsive_image($image); ?> alt="Coach Image">
        <?php if ($related_organizations): 
          $org_logo = $related_organizations[0]['organization_logo'];
        ?>
          <div class="coach-card__logo">
            <img <?php responsive_image($org_logo['ID']); ?> alt="Coach Logo">
          </div>
        <?php endif; ?>
      </div>
      <div class="coach-card__main">
        <p class="coach-card__title"><?= get_the_title($post) ?></p>
        <p class="coach-card__organization"><?= $organization_name ?></p>
      </div>
    </div>
  <?php endforeach; ?>

</div>
<?php wp_reset_postdata(); ?>