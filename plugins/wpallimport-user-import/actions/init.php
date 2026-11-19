<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function pmui_init(){

	// Register a post type for Users

	$labels = array(
	    'name' => __('Users', 'wpallimport-user-import'),
	    'singular_name' => __('User', 'wpallimport-user-import'),
  	);

	$args = array(
	    'labels' => $labels,
	    'public' => false,
	    'publicly_queryable' => false,
	    'show_ui' => true, 
	    'show_in_menu' => false, 
	    'query_var' => false,	    
	    'rewrite' => false,
	    'capability_type' => 'post',
	    'has_archive' => false, 
	    'hierarchical' => false,
	    'menu_position' => null,
	    'supports' => array( 'title', 'editor', 'custom-fields' ),
	    'taxonomies' => array()
	); 
	
	register_post_type('import_users', $args);
}

?>