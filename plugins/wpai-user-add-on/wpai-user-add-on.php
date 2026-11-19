<?php
/*
Plugin Name: WP All Import - User Import Add-On Pro
Plugin URI: http://www.wpallimport.com/
Description: Import Users & User metadata to WordPress. Requires WP All Import.
Version: 2.0.0
Author: Soflyy
*/
/**
 * Plugin root dir with forward slashes as directory separator regardless of actual DIRECTORY_SEPARATOR value
 * @var string
 */
define('PMUI_PRO_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
/**
 * Plugin root url for referencing static content
 * @var string
 */
define('PMUI_PRO_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));

define('PMUI_PRO_VERSION', '2.0.0');

// Load Composer autoloader and initialize the PMXI Addon Installer SDK for the User Import free base plugin.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';

	$wpai_addon_installer = new \PMUI_Vendor\Soflyy\AddonInstaller\AddonInstaller([
		'addon_name'           => 'WP All Import - User Import Add-On',
		'addon_slug'           => 'wpallimport-user-import',
		'addon_author'         => 'Soflyy',
		'minimum_version'      => '1.0.1',
		'pro_plugin_name'      => 'WP All Import - User Import Add-On Pro',
		'pro_plugin_file'      => __FILE__,
		'textdomain'           => 'wp_all_import_user_add_on',
		'version_constant'     => 'PMUI_VERSION',
		'edition_constant'     => 'PMUI_EDITION',
		'expected_edition'     => 'free',
		'free_plugin_file'     => 'wpallimport-user-import.php',
		'disable_deactivation' => true,
		'send_email_alert'     => true,
		'operation_type'       => 'import',
		'support_url'          => 'https://www.wpallimport.com/support/',
	]);
	$wpai_addon_installer->install_addon_from_repository();

	// The SDK blocks every import while the free base is missing, and nothing when the base is installed but
	// could not boot (WP All Import Pro below its floor); users and customers imports need the block in both cases.
	remove_action('pmxi_before_xml_import', array($wpai_addon_installer, 'block_manual_operation_if_dependency_missing'), 1);
	remove_action('pmxi_before_post_import', array($wpai_addon_installer, 'block_operation_if_dependency_missing'), 1);
	remove_action('pmxi_scheduled_request_authenticated', array($wpai_addon_installer, 'ensure_dependency_for_scheduled_request'), 1);
	remove_action('init', array($wpai_addon_installer, 'block_scheduled_operation_on_legacy_host'), 1);

	add_action('pmxi_before_xml_import', function($import_id) use ($wpai_addon_installer) {
		if (pmuip_base_is_active() || !pmuip_import_needs_base($import_id)) return;
		// A refused run would otherwise stay marked executing and lock out the import's next runs.
		register_shutdown_function(function() use ($import_id) {
			if (!pmuip_base_is_active()) pmuip_release_import($import_id);
		});
		if (!$wpai_addon_installer->is_addon_up_to_date()) $wpai_addon_installer->block_manual_operation_if_dependency_missing($import_id);
		if (!pmuip_base_is_active()) pmuip_refuse_import('manual', $wpai_addon_installer->is_addon_up_to_date());
	}, 1, 1);
	add_action('pmxi_before_post_import', function($import_id, $data = null) use ($wpai_addon_installer) {
		if (pmuip_base_is_active() || !pmuip_import_needs_base($import_id)) return;
		try {
			if (!$wpai_addon_installer->is_addon_up_to_date()) $wpai_addon_installer->block_operation_if_dependency_missing($import_id, $data);
			if (!pmuip_base_is_active()) pmuip_refuse_import('record', $wpai_addon_installer->is_addon_up_to_date());
		} catch (Exception $e) {
			// The refusal aborts the whole run, which would otherwise leave the import locked as processing.
			pmuip_release_import($import_id);
			throw $e;
		}
	}, 1, 2);
	add_action('pmxi_scheduled_request_authenticated', function($import_id = null) use ($wpai_addon_installer) {
		if (pmuip_base_is_active() || pmuip_is_recovery_request() || !pmuip_import_needs_base(isset($_GET['import_id']) ? sanitize_text_field(wp_unslash($_GET['import_id'])) : $import_id)) return;
		if (!$wpai_addon_installer->is_addon_up_to_date()) $wpai_addon_installer->ensure_dependency_for_scheduled_request($import_id);
		if (!pmuip_base_is_active()) pmuip_refuse_import('scheduled', $wpai_addon_installer->is_addon_up_to_date());
	}, 1);
	add_action('init', function() use ($wpai_addon_installer) {
		if (!isset($_GET['import_key']) || empty($_GET['import_id']) || pmuip_base_is_active() || pmuip_is_recovery_request() || !pmuip_import_needs_base(sanitize_text_field(wp_unslash($_GET['import_id'])))) return;
		if (!$wpai_addon_installer->is_addon_up_to_date()) {
			$wpai_addon_installer->block_scheduled_operation_on_legacy_host();
			return;
		}
		pmuip_refuse_import('scheduled', true);
	}, 1);
}

// The free base plugin owns the PMUI_* classes and the pmui_-prefixed handlers it
// registers through its filename convention, so the pro handlers use a pmuip_ prefix
// and are registered here. Handlers that build on the free base plugin check that
// PMUI_EDITION is 'free' when they run and do nothing otherwise.
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_base_is_active.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_default_import_options.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_do_not_send_password_notification.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_filter_has_cap_unfiltered_html.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_get_existing_meta_keys.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_import_needs_base.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_is_recovery_request.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_meta_keys_lookup.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_refuse_import.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_release_import.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_section_closed_class.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_send_email_change_email.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/helpers/pmuip_send_new_user_notification.php';

require_once PMUI_PRO_ROOT_DIR . '/pro/actions/admin_enqueue_scripts.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/actions/admin_notices.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/actions/init.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/actions/pmxi_after_post_import.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/actions/pmxi_before_post_import.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/actions/pmxi_confirm_data_to_import.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/actions/pmxi_extend_options_main.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/actions/pmxi_options_tab.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/actions/pmxi_reimport.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/actions/pmxi_saved_post.php';

require_once PMUI_PRO_ROOT_DIR . '/pro/filters/pmui_options_validation.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/filters/pmui_parsed_data.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/filters/pmui_save_options.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/filters/pmui_default_import_options.php';
require_once PMUI_PRO_ROOT_DIR . '/pro/filters/pmxi_visible_template_sections.php';

add_action('admin_enqueue_scripts', 'pmuip_admin_enqueue_scripts');
add_action('admin_notices', 'pmuip_admin_notices');
add_action('init', 'pmuip_init');
add_action('pmxi_after_post_import', 'pmuip_pmxi_after_post_import', 10, 1);
add_action('pmxi_before_post_import', 'pmuip_pmxi_before_post_import', 10, 1);
add_action('pmxi_confirm_data_to_import', 'pmuip_pmxi_confirm_data_to_import', 20, 2);
add_action('pmxi_extend_options_main', 'pmuip_pmxi_extend_options_main', 20, 2);
add_action('pmxi_options_tab', 'pmuip_pmxi_options_tab', 20, 2);
add_action('pmxi_reimport', 'pmuip_pmxi_reimport', 9, 2);
add_action('pmxi_saved_post', 'pmuip_pmxi_saved_post', 10, 3);

add_filter('pmui_options_validation', 'pmuip_pmui_options_validation', 10, 2);
add_filter('pmui_parsed_data', 'pmuip_pmui_parsed_data', 10, 3);
add_filter('pmui_save_options', 'pmuip_pmui_save_options', 10, 1);
add_filter('pmui_default_import_options', 'pmuip_pmui_default_import_options', 10, 1);
add_filter('pmxi_visible_template_sections', 'pmuip_pmxi_visible_template_sections', 20, 2);

// The free base plugin renders upgrade notices in place of the pro features; suppress them.
add_filter('pmui_is_show_pro_notice', '__return_false');

// retrieve our license key from the DB
$wpai_user_addon_options = get_option('PMXI_Plugin_Options');

// Favor new API URL, but fallback to old if needed.
if( !empty($wpai_user_addon_options['info_api_url_new'])){
    $api_url = $wpai_user_addon_options['info_api_url_new'];
}elseif( !empty($wpai_user_addon_options['info_api_url'])){
    $api_url = $wpai_user_addon_options['info_api_url'];
}else{
    $api_url = null;
}

if (!empty($api_url)){
	require_once PMUI_PRO_ROOT_DIR . '/classes/updater.php';
	// setup the updater
	$updater = new PMUI_Updater( $api_url, __FILE__, array(
			'version' 	=> PMUI_PRO_VERSION,		// current version number
			'license' 	=> false, // license key (used get_option above to retrieve from DB)
			'item_name' => 'User Import Add-On', 	// name of this plugin
			'author' 	=> 'Soflyy'  // author of this plugin
		)
	);
}
