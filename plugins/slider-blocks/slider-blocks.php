<?php
/**
 * Plugin Name:       GutSlider - All in One Slider Blocks for Gutenberg
 * Description:       A collection of custom Gutenberg Slider Blocks to slide your content.
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Version:           3.2.0
 * Author:            Binsaifullah
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       slider-blocks
 *
 * @package GutSlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version constant.
 *
 * @since 1.0.0
 * @var string
 */
define( 'GUTSLIDER_VERSION', '3.2.0' );

/**
 * Plugin directory URL with trailing slash.
 *
 * @since 1.0.0
 * @var string
 */
define( 'GUTSLIDER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin directory path with trailing slash.
 *
 * @since 1.0.0
 * @var string
 */
define( 'GUTSLIDER_DIR_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory path without trailing slash.
 *
 * @since 1.0.0
 * @var string
 */
define( 'GUTSLIDER_DIR', __DIR__ );

// Load PSR-4 autoloader.
require_once GUTSLIDER_DIR . '/includes/Autoloader.php';
( new GutSlider_Autoloader( GUTSLIDER_DIR . '/includes/' ) )->register();

// Load backward-compatibility global functions.
require_once GUTSLIDER_DIR . '/includes/compat.php';

// Load admin (outside refactoring scope).
require_once GUTSLIDER_DIR . '/admin/admin.php';

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, array( GutSlider\Plugin::class, 'on_activation' ) );
register_deactivation_hook( __FILE__, array( GutSlider\Plugin::class, 'cleanup' ) );

/**
 * Return the main plugin singleton instance.
 *
 * @since 1.0.0
 *
 * @return GutSlider\Plugin The plugin instance.
 */
function gutsliderblocks(): GutSlider\Plugin {
	return GutSlider\Plugin::get_instance();
}

// Boot the plugin.
gutsliderblocks();
