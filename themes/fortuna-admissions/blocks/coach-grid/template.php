<?php
/**
 * Coach Grid
 */

$layout = get_field('layout');
$coaches = get_field('coaches');
?>

<div class="coach-grid">
  <div class="inner">
      <?php if ($coaches): $i = 0; ?>
        <div class="coach-grid__grid coach-grid--<?= $layout ?>">

          <?php foreach ($coaches as $post):
            setup_postdata($post);
            $image = get_post_thumbnail_id($post);
            $position_title = get_field('position_title', $post);
            $featured_credentials = get_field('featured_credentials', $post);
            $featured_testimonial = get_field('featured_testimonial', $post);
            $highlights = get_field('highlights', $post);
            $slug = $post->post_name;
          ?>

            <div class="coach-card coach-card--<?= $layout ?>" data-micromodal-trigger="modal-profile-<?= $slug ?>-<?= $i ?>">

                <?php if ($layout === 'simple'): ?>
                  <div class="coach-card__image">
                    <img <?php responsive_image($image); ?> alt="Coach Image">
                  </div>
                  <div class="coach-card__main">
                    <p class="coach-card__title"><?= get_the_title($post) ?></p>
                    <p class="coach-card__featured-credentials"><?= $featured_credentials ?></p>
                  </div>
                <?php endif; ?>

                <?php if ($layout === 'detailed'): ?>
                  <div class="coach-card__image">
                    <img <?php responsive_image($image); ?> alt="Coach Image">
                  </div>

                  <?php if (have_rows('related_organizations', $post)): ?>
                    <div class="coach-card__organizations">
                      <?php while (have_rows('related_organizations', $post)):
                        the_row();
                        $logo = get_sub_field('organization_logo');
                      ?>
                        <?php if ($logo): ?>
                          <div class="coach-card__logo">
                            <img <?php responsive_image($logo['id']); ?> alt="Coach Logo">
                          </div>
                        <?php endif; ?>
                      <?php endwhile; ?>
                    </div>
                  <?php endif; ?>

                  <p class="coach-card__title"><?= get_the_title($post) ?></p>
                  <p class="coach-card__position-title"><?= $position_title ?></p>
                  <p class="coach-card__featured-credentials"><?= $featured_credentials ?></p>
                <?php endif; ?>

                <div class="modal micromodal-slide" id="modal-profile-<?= $slug ?>-<?= $i ?>" aria-hidden="true">
                  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
                    <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-profile-<?= $slug ?>-<?= $i ?>-title" >
                      <header class="modal__header">
                        <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                      </header>

                      <div class="modal__content" id="modal-profile-<?= $slug ?>-<?= $i ?>-content">
                        <div class="coach-profile__left">
                          <div class="coach-profile__image">
                            <img <?php responsive_image($image); ?> alt="Coach Image">
                          </div>
                          <?php if ($featured_testimonial && $featured_testimonial['testimonial']): ?>
                            <div class="coach-profile__testimonial">
                              <p class="coach-profile__testimonial__eyebrow">From Our Clients</p>
                              <p class="coach-profile__testimonial__body"><?= $featured_testimonial['testimonial'] ?></p>
                              <p class="coach-profile__testimonial__attribution"><?= $featured_testimonial['attribution'] ?></p>
                            </div>
                          <?php endif; ?>
                        </div>
                        <div class="coach-profile__right">
                          <div class="coach-profile__header">
                            <p class="coach-profile__name"><?= get_the_title($post) ?></p>
                            <?php if ($position_title): ?>
                              <p class="coach-profile__position-title"><?= $position_title ?></p>
                            <?php endif; ?>
                            <?php if ($featured_credentials): ?>
                              <p class="coach-profile__featured-credentials"><?= $featured_credentials ?></p>
                            <?php endif; ?>

                            <?php if (have_rows('related_organizations', $post)): ?>
                              <div class="coach-card__organizations">
                                <?php while (have_rows('related_organizations', $post)):
                                  the_row();
                                  $logo = get_sub_field('organization_logo');
                                ?>
                                  <?php if ($logo): ?>
                                    <div class="coach-card__logo">
                                      <img <?php responsive_image($logo['id']); ?> alt="Coach Logo">
                                    </div>
                                  <?php endif; ?>
                                <?php endwhile; ?>
                              </div>
                            <?php endif; ?>
                          </div>
                          <div class="coach-profile__main">
                            <?php if ($highlights): ?>
                              <div class="coach-profile__highlights">
                                <p class="coach-profile__highlights__title">Highlights & <span>Achievements</span></p>
                                <div class="coach-profile__highlights__body"><?= $highlights ?></div>
                              </div>
                            <?php endif; ?>
                            <div class="uagb-button__wrapper">
                            <a class="fa-button" aria-label="" href="<?= get_permalink($post) ?> "rel="follow noopener" target="_self" role="button">
                              <span>View Full Profile</span>
                              <span class="fa-button__icon">
                                <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true" focussable="false">
                                  <path d="M438.6 278.6l-160 160C272.4 444.9 264.2 448 256 448s-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L338.8 288H32C14.33 288 .0016 273.7 .0016 256S14.33 224 32 224h306.8l-105.4-105.4c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l160 160C451.1 245.9 451.1 266.1 438.6 278.6z"></path>
                                </svg>
                              </span>
                            </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

            </div>
          <?php $i++; endforeach; ?>

        </div>
        <?php wp_reset_postdata(); ?>
      <?php endif; ?>

  </div>
</div>