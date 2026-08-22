<?php

class FA_Nav_Menu_Walker extends Walker_Nav_Menu
{
  public function start_lvl(&$output, $depth = 0, $args = array())
  {
    // Prevent default <ul> output
    $output .= "";
  }

  public function end_lvl(&$output, $depth = 0, $args = array())
  {
    $output .= "";
  }

  public function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
  {
    $submenu_shortcode = get_field('submenu_template', $item);

    // Skip child items that have a shortcode
    if ($depth > 0 && $submenu_shortcode) {
      return;
    }

    $classes = empty($item->classes) ? array() : (array) $item->classes;
    $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item));

    $output .= '<li class="' . esc_attr($class_names) . '">';

    $title = apply_filters('the_title', $item->title, $item->ID);
    $attributes = ' href="' . esc_attr($item->url) . '"';

    $output .= '<a' . $attributes . '>' . $title;

    // Add down chevron if the item has a dropdown shortcode
    if ($depth === 0 && $submenu_shortcode) {
      $output .= ' <span class="menu-chevron"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
<path d="M13.3535 6.35403L8.35354 11.354C8.3071 11.4005 8.25196 11.4374 8.19126 11.4626C8.13056 11.4877 8.0655 11.5007 7.99979 11.5007C7.93408 11.5007 7.86902 11.4877 7.80832 11.4626C7.74762 11.4374 7.69248 11.4005 7.64604 11.354L2.64604 6.35403C2.55222 6.26021 2.49951 6.13296 2.49951 6.00028C2.49951 5.8676 2.55222 5.74035 2.64604 5.64653C2.73986 5.55271 2.86711 5.5 2.99979 5.5C3.13247 5.5 3.25972 5.55271 3.35354 5.64653L7.99979 10.2934L12.646 5.64653C12.6925 5.60007 12.7476 5.56322 12.8083 5.53808C12.869 5.51294 12.9341 5.5 12.9998 5.5C13.0655 5.5 13.1305 5.51294 13.1912 5.53808C13.2519 5.56322 13.3071 5.60007 13.3535 5.64653C13.4 5.69298 13.4368 5.74813 13.462 5.80883C13.4871 5.86953 13.5001 5.93458 13.5001 6.00028C13.5001 6.06598 13.4871 6.13103 13.462 6.19173C13.4368 6.25242 13.4 6.30757 13.3535 6.35403Z" fill="#92909B"/>
</svg></span>';
    }

    $output .= '</a>';

    if ($depth === 0 && $submenu_shortcode) {
      $output .= '<div class="dropdown-content">';
      $output .= do_shortcode($submenu_shortcode);
      $output .= '</div>';
    }

    $output .= '</li>';
  }


  public function end_el(&$output, $item, $depth = 0, $args = array())
  {
    $output .= "";
  }
}
