<?php
/*
Plugin Name: WP All Import - User Import Add-On
Description: Import Users to WordPress. Requires WP All Import.
Version: 1.0.1
Requires at least: 5.0
Requires PHP: 7.4
Author: Soflyy
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wpallimport-user-import
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// The legacy monolithic pro add-on declares this same class and constants. If it loaded
// first, skip this plugin's bootstrap entirely so the request doesn't fatal on
// redeclaration. The check must use a constant: PHP defers binding of the duplicate class
// declared below only until execution reaches it, and this return runs first.
if ( defined( 'PMUI_ROOT_DIR' ) ) {
	return;
}


/**
 * Plugin root dir with forward slashes as directory separator regardless of actual DIRECTORY_SEPARATOR value
 * @var string
 */
define('PMUI_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
/**
 * Plugin root url for referencing static content
 * @var string
 */
define('PMUI_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));
/**
 * Plugin prefix for making names unique (be aware that this variable is used in conjuction with naming convention,
 * i.e. in order to change it one must not only modify this constant but also rename all constants, classes and functions which
 * names composed using this prefix)
 * @var string
 */
define('PMUI_PREFIX', 'pmui_');

define('PMUI_VERSION', '1.0.1');

define('PMUI_EDITION', 'free');

define('PMUI_MIN_PAID_CORE', '5.1.0');

function pmui_core_is_supported() {
	if ( ! defined('PMXI_VERSION') or ! defined('PMXI_EDITION') ) return true;

	if ( 'paid' === PMXI_EDITION ) return version_compare(PMXI_VERSION, PMUI_MIN_PAID_CORE, '>=');

	return version_compare(PMXI_VERSION, '3.6.9', '>=');
}

function pmui_unsupported_core_notice() {
	$message = ( defined('PMXI_EDITION') and 'paid' === PMXI_EDITION )
		// translators: %s is the minimum WP All Import Pro version.
		? sprintf( __('WP All Import - User Import Add-On requires WP All Import Pro %s or newer. Update WP All Import Pro to use this add-on.', 'wpallimport-user-import'), PMUI_MIN_PAID_CORE )
		: __('WP All Import - User Import Add-On requires WP All Import 3.6.9 or newer. Update WP All Import to use this add-on.', 'wpallimport-user-import');
	?>
	<div class="error"><p><?php echo esc_html( $message ); ?></p></div>
	<?php
}

function pmui_boot() {
	if ( ! pmui_core_is_supported() ) {
		add_action('admin_notices', 'pmui_unsupported_core_notice');
		return;
	}

	require_once PMUI_ROOT_DIR . '/classes/plugin.php';

	PMUI_Plugin::getInstance();
}

// The floor can only be judged once WP All Import has defined its constants. It normally has
// (its directory sorts first), but a network-activated or must-use copy of this plugin loads
// earlier, so the decision waits for plugins_loaded in that case.
if ( defined('PMXI_VERSION') and defined('PMXI_EDITION') ) {
	pmui_boot();
} else {
	add_action('plugins_loaded', 'pmui_boot', 0);
}
