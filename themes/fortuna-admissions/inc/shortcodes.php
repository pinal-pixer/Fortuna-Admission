<?php

function fa_post_grid_shortcode($atts) {
  ob_start();

  // Shortcode attributes with defaults
  $atts = shortcode_atts([
    'search' => 'false',
    'posts_per_page' => 3,
    'title' => '',
    'service' => '',
  ], $atts, 'post-grid');

  $search_enabled = ($atts['search'] === 'true');
  $search_query   = isset($_GET['post_search']) ? sanitize_text_field($_GET['post_search']) : '';
  $paged          = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
  $posts_per_page = intval($atts['posts_per_page']);
  $title          = sanitize_text_field($atts['title']);

  // Build query args
  if ($search_enabled && !empty($search_query)) {
    // Search query
    $query_args = [
      'post_type' => 'post',
      's' => $search_query,
      'posts_per_page' => $posts_per_page,
      'paged' => $paged,
    ];
  } else if (is_archive() || is_home()) {
    // Use main query vars with pagination override
    global $wp_query;
    $main_query_vars = $wp_query->query_vars;

    $query_args = array_merge($main_query_vars, [
      'posts_per_page' => $posts_per_page,
      'paged' => $paged,
    ]);
    
    // Set category name as title
    $title = get_queried_object()->name;
  } else {
    // Default query
    $query_args = [
      'post_type' => 'post',
      'posts_per_page' => $posts_per_page,
      'paged' => $paged,
    ];
  }

  $service = sanitize_text_field($atts['service']);
  if (!empty($service)) {
    $query_args['tax_query'] = [
      [
        'taxonomy' => 'service',
        'field' => 'slug',
        'terms' => $service,
      ]
    ];
  }

  $query = new WP_Query($query_args);

  // Pass vars to template
  $template_vars = [
    'query' => $query,
    'search_query' => $search_query,
    'search_enabled' => $search_enabled,
    'paged' => $paged,
    'title' => $title,
  ];

  extract($template_vars);

  $template_path = get_stylesheet_directory() . '/partials/post-grid.php';

  if (file_exists($template_path)) {
      include $template_path;
  } else {
      echo '<p>Template not found.</p>';
  }

  wp_reset_postdata();

  return ob_get_clean();
}

add_shortcode('post-grid', 'fa_post_grid_shortcode');
