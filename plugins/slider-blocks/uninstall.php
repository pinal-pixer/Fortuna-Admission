<?php
/**
 * Uninstall script for GutSliderBlocks.
 *
 * Generated stylesheets are always removed. Stored options are only
 * removed when "Delete data on uninstall" is enabled in the settings.
 *
 * @package GutSliderBlocks
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$gutslider_settings    = get_option( 'gutslider_settings', array() );
$gutslider_remove_data = is_array( $gutslider_settings ) && ! empty( $gutslider_settings['remove_data'] );

// Load the main plugin file to access the cleanup method.
require_once plugin_dir_path( __FILE__ ) . 'slider-blocks.php';

// Remove generated stylesheets.
GutSlider\Plugin::cleanup();

if ( ! $gutslider_remove_data ) {
	return;
}

// Remove the plugin's own options.
$gutslider_options = array(
	'gutslider_settings',
	'gutslider_blocks',
	'gutslider_blocks_version',
	'gutsliderblocks_do_activation_redirect',
);

$gutslider_blocks_file = plugin_dir_path( __FILE__ ) . 'includes/Api/blocks.php';

if ( file_exists( $gutslider_blocks_file ) ) {
	$gutslider_block_list = include $gutslider_blocks_file;

	if ( is_array( $gutslider_block_list ) ) {
		foreach ( $gutslider_block_list as $gutslider_block ) {
			if ( isset( $gutslider_block['name'] ) ) {
				$gutslider_options[] = 'gut_' . str_replace( '-', '_', $gutslider_block['name'] );
			}
		}
	}
}

foreach ( array_unique( $gutslider_options ) as $gutslider_option ) {
	delete_option( $gutslider_option );
}
