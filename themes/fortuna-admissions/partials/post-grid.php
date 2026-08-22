<?php if (!defined('ABSPATH')) exit; ?>

<div class="post-grid-wrapper">
  <?php if ($title || $search_enabled): ?>
    <div class="post-grid-header">
      <?php if ($title): ?>
        <h3 class="post-grid-title"><?= $title ?></h3>
      <?php endif; ?>

      <?php if ($search_enabled): ?>
        <form method="get" class="post-search-form">
          <div class="search-input-wrapper">
            <input type="text" name="post_search" value="<?php echo esc_attr($search_query); ?>" placeholder="Search articles" />
            <button type="submit" class="search-icon" aria-label="Search">
              <?php
              $icon_path = get_stylesheet_directory() . '/assets/search-icon.svg';
              if (file_exists($icon_path)) {
                echo file_get_contents($icon_path);
              }
              ?>
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($query->have_posts()): ?>

    <div class="post-grid">
      <?php while ($query->have_posts()): $query->the_post(); ?>
        <a href="<?php the_permalink(); ?>">
          <div class="post-item">
            <?php if (has_post_thumbnail()): ?>
              <div class="post-item__image">
                <?php the_post_thumbnail('medium'); ?>
                <div class="post-item__image__date">
                <?php echo get_the_date('F j, Y'); ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="post-item__content">
              <p class="post-item__title">
                <?php the_title(); ?>
              </p>

              <div class="post-item__excerpt">
                <?php
                $excerpt = get_the_excerpt();
                $trimmed = wp_trim_words($excerpt, 30, '...');
                echo esc_html($trimmed);
                ?>
              </div>
            </div>
          </div>

        </a>
      <?php endwhile; ?>
    </div>

    <div class="post-grid-pagination">
      <?php
      $is_archive = is_archive() || is_home();

      // Determine correct format and base
      $format = $is_archive ? '?page=%#%' : '?paged=%#%';
      $base = $is_archive
        ? add_query_arg('page', '%#%')
        : str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999)));

      echo paginate_links([
        'base'      => $base,
        'format'    => $format,
        'current' => max(1, $paged),
        'total'   => $query->max_num_pages,
        'type'    => 'list',
        'prev_text' => '<',
        'next_text' => '>',
      ]);
      ?>
    </div>

  <?php else: ?>
    <p>No posts found.</p>
  <?php endif; ?>
</div>