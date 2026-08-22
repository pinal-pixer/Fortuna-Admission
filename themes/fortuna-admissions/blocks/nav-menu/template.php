<?php

/**
 * Nav Menu
 */

  $menu = get_field('menu');

  wp_nav_menu(array(
      'menu'          => $menu,
      'menu_class'    => 'nav-menu',
      'container'     => false,
      'walker'        => new FA_Nav_Menu_Walker()
  ));
?>