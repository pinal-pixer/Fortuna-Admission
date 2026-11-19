<?php

function pmuip_init(){
	load_plugin_textdomain( 'wp_all_import_user_add_on', false, dirname( plugin_basename( PMUI_PRO_ROOT_DIR . '/wpai-user-add-on.php' ) ) . "/i18n/languages" );

	if ( ! pmuip_base_is_active() || ! class_exists('WooCommerce') || ! defined('PMXI_VERSION') || ! defined('PMXI_EDITION') ) return;

	$core_supports_customers = ( PMXI_EDITION == 'paid' && version_compare(PMXI_VERSION, '4.5.6') >= 0 )
		|| ( PMXI_EDITION == 'free' && version_compare(PMXI_VERSION, '3.5.0') >= 0 );

	if ( ! $core_supports_customers ) return;

	register_post_type('shop_customer', array(
		'labels' => array(
			'name' => __('WooCommerce Customers', 'wp_all_import_user_add_on'),
			'singular_name' => __('WooCommerce Customer', 'wp_all_import_user_add_on'),
		),
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
	));
}
