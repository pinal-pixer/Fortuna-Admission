<?php

namespace PMUI_Vendor\Soflyy\AddonInstaller;

use Exception;
use Plugin_Installer_Skin;
use Plugin_Upgrader;

/**
 * Thrown when another process already holds the installation lock.
 *
 * Kept in this file so the SDK works whether consumers load it via PSR-4
 * autoloading or by requiring AddonInstaller.php directly.
 */
class AddonInstallLockedException extends Exception {}

/**
 * Soflyy Addon Installer SDK
 *
 * A reusable SDK for installing and managing free plugin dependencies for pro plugins.
 */
class AddonInstaller {

	/**
	 * Configuration for the addon being managed.
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * The detected addon version.
	 *
	 * @var string
	 */
	protected $addon_version = '0';

	/**
	 * The detected addon plugin file.
	 *
	 * @var string
	 */
	protected $addon_file;

	/**
	 * The detected addon directory.
	 *
	 * @var string
	 */
	protected $addon_dir;

	/**
	 * Why the most recent installation attempt failed, for error messages.
	 *
	 * @var string
	 */
	protected $last_failure_message = '';

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration array with the following keys:
	 *   - 'addon_name' (string): Display name of the free addon (e.g., 'ACF Add-On Free')
	 *   - 'addon_slug' (string): WordPress.org plugin slug (e.g., 'csv-xml-import-for-acf')
	 *   - 'addon_author' (string): Plugin author name (e.g., 'Soflyy')
	 *   - 'minimum_version' (string): Minimum required version (e.g., '1.0.4')
	 *   - 'pro_plugin_name' (string): Name of the pro plugin (e.g., 'WP All Import - ACF Add-On Pro')
	 *   - 'pro_plugin_file' (string): Pro plugin main file path
	 *   - 'textdomain' (string): Text domain for translations
	 *   - 'operation_type' (string, optional): 'import' or 'export' — controls hooks, scheduling detection,
	 *        CLI indicators, option prefixes, CSS classes, and error messages. Default: 'import'.
	 *   - 'version_constant' (string, optional): Constant name that holds the addon version
	 *   - 'edition_constant' (string, optional): Constant name that holds the edition type
	 *   - 'expected_edition' (string, optional): Expected edition value (default: 'free')
	 *   - 'free_plugin_file' (string, optional): Free plugin filename if different from plugin.php
	 *   - 'disable_deactivation' (bool, optional): Whether to prevent deactivation of the free plugin
	 *   - 'send_email_alert' (bool, optional): Whether to send email alerts for failures (default: true)
	 *   - 'support_url' (string, optional): Support page linked from failure emails
	 */
	public function __construct( array $config ) {
		$this->config = $this->validate_config( $config );

		// Use custom filename or default to plugin.php
		$plugin_filename = $this->config['free_plugin_file'] ?? 'plugin.php';
		$this->addon_file = $this->config['addon_slug'] . '/' . $plugin_filename;
		$this->addon_dir = WP_PLUGIN_DIR . '/' . $this->config['addon_slug'];

		// Initialize hooks for blocking operations when dependencies are not met
		$this->init_operation_blocking_hooks();

		// Initialize CLI hooks for preventing deactivation
		$this->init_cli_hooks();
	}

	/**
	 * Validates and sets defaults for the configuration.
	 *
	 * @param array $config Configuration array.
	 * @return array Validated configuration.
	 * @throws Exception If required configuration is missing.
	 */
	protected function validate_config( array $config ) {
		$required = [
			'addon_name',
			'addon_slug',
			'addon_author',
			'minimum_version',
			'pro_plugin_name',
			'pro_plugin_file',
			'textdomain'
		];

		foreach ( $required as $key ) {
			if ( empty( $config[ $key ] ) ) {
				throw new Exception( "Required configuration key '{$key}' is missing." );
			}
		}

		// Set defaults for optional parameters
		$defaults = [
			'operation_type' => 'import',
			'expected_edition' => 'free',
			'disable_deactivation' => false,
			'send_email_alert' => true,
			'support_url' => ''
		];

		foreach ( $defaults as $key => $default_value ) {
			if ( ! isset( $config[ $key ] ) ) {
				$config[ $key ] = $default_value;
			}
		}

		// Validate operation_type
		if ( ! in_array( $config['operation_type'], [ 'import', 'export' ], true ) ) {
			throw new Exception( "Invalid operation_type '{$config['operation_type']}'. Must be 'import' or 'export'." );
		}

		// Ensure boolean values are properly cast
		$config['disable_deactivation'] = (bool) $config['disable_deactivation'];
		$config['send_email_alert'] = (bool) $config['send_email_alert'];

		return $config;
	}

	/**
	 * Returns whether this is an import-type operation.
	 *
	 * @return bool
	 */
	protected function is_import_type() {
		return $this->config['operation_type'] === 'import';
	}

	/**
	 * Returns the operation-specific prefix for option keys, transient keys, etc.
	 *
	 * @return string 'pmxi' for import, 'pmxe' for export.
	 */
	protected function get_operation_prefix() {
		return $this->is_import_type() ? 'pmxi' : 'pmxe';
	}

	/**
	 * Returns the operation-specific CSS class prefix for admin notices.
	 *
	 * @return string 'pmxi' for import, 'pmxe' for export.
	 */
	protected function get_css_prefix() {
		return $this->get_operation_prefix();
	}

	/**
	 * Returns the operation label for user-facing messages (e.g., 'import' or 'export').
	 *
	 * @return string
	 */
	protected function get_operation_label() {
		return $this->config['operation_type'];
	}

	/**
	 * Returns the product family name for user-facing messages.
	 *
	 * @return string
	 */
	protected function get_product_family_name() {
		return $this->is_import_type() ? 'WP All Import' : 'WP All Export';
	}

	/**
	 * Returns the companion plugin's wordpress.org page.
	 *
	 * @return string
	 */
	protected function get_companion_directory_url() {
		return sprintf( 'https://wordpress.org/plugins/%s/', $this->config['addon_slug'] );
	}

	/**
	 * Initialize CLI-specific hooks.
	 *
	 * @return void
	 */
	protected function init_cli_hooks() {
		if ( $this->is_cli_context() ) {
			// Hook into CLI plugin deactivation to prevent or immediately reactivate
			add_action( 'deactivated_plugin', array( $this, 'handle_cli_deactivation' ), 10, 2 );

			// Also check on every CLI operation that might run imports/exports
			add_action( 'wp_loaded', array( $this, 'ensure_cli_addon_activation' ), 5 );
		}
	}

	/**
	 * Handles plugin deactivation in CLI context.
	 *
	 * @param string $plugin Plugin file that was deactivated.
	 * @param bool   $network_deactivating Whether the plugin is being network deactivated.
	 * @return void
	 */
	public function handle_cli_deactivation( $plugin, $network_deactivating ) {
		if ( $plugin === $this->addon_file && $this->config['disable_deactivation'] ) {
			// Immediately reactivate the plugin
			$this->reactivate_addon_silently();
		}
	}

	/**
	 * Ensures addon is activated in CLI context before imports/exports.
	 *
	 * @return void
	 */
	public function ensure_cli_addon_activation() {
		if ( ! $this->is_cli_context() ) {
			return;
		}

		// If we're about to run an operation, ensure addon is active
		if ( ! $this->is_relevant_cli_operation() ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( $this->addon_file ) ) {
			$this->reactivate_addon_silently();
		}
	}

	/**
	 * Reactivates the addon silently without output.
	 *
	 * @return void
	 */
	protected function reactivate_addon_silently() {
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Simple activation - WordPress handles translation timing internally
		$result = activate_plugin( $this->addon_file, '', false, true );

		// If activation failed, try network activation
		if ( is_wp_error( $result ) ) {
			activate_plugin( $this->addon_file, '', true, true );
		}
	}

	/**
	 * Checks if the current CLI command is a relevant operation (import or export).
	 *
	 * @return bool
	 */
	protected function is_relevant_cli_operation() {
		global $argv;

		if ( ! is_array( $argv ) ) {
			return false;
		}

		$command_line = implode( ' ', $argv );

		if ( $this->is_import_type() ) {
			$indicators = [ 'wp-all-import', 'pmxi', 'all-import' ];
		} else {
			$indicators = [ 'wp-all-export', 'pmxe', 'all-export' ];
		}

		foreach ( $indicators as $indicator ) {
			if ( strpos( $command_line, $indicator ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Initialize hooks to block operations when dependencies are not met.
	 *
	 * @return void
	 */
	protected function init_operation_blocking_hooks() {
		if ( $this->is_import_type() ) {
			// Hook to block manual imports
			add_action( 'pmxi_before_xml_import', array( $this, 'block_manual_operation_if_dependency_missing' ), 1, 1 );

			// Additional safety hook for any PMXI operations
			add_action( 'pmxi_before_post_import', array( $this, 'block_operation_if_dependency_missing' ), 1, 2 );

			// Self-heal a scheduled import, but only after WP All Import has authenticated the
			// request. There is deliberately no raw `init` trigger: an unvalidated request must
			// not reach installation.
			add_action( 'pmxi_scheduled_request_authenticated', array( $this, 'ensure_dependency_for_scheduled_request' ), 1 );
		} else {
			// Hook to block manual exports
			add_action( 'pmxe_before_export', array( $this, 'block_manual_operation_if_dependency_missing' ), 1, 1 );

			// Self-heal a scheduled export, but only after WP All Export has authenticated the
			// request (see the import branch above).
			add_action( 'pmxe_scheduled_request_authenticated', array( $this, 'ensure_dependency_for_scheduled_request' ), 1 );
		}

		// Preserve the scheduled failure contract on hosts too old to fire that hook. Blocks only;
		// see block_scheduled_operation_on_legacy_host() for why this cannot install.
		add_action( 'init', array( $this, 'block_scheduled_operation_on_legacy_host' ), 1 );
	}

	/**
	 * Whether the host fires the authenticated scheduled-request hook.
	 *
	 * A capability probe, not a version compare. Hosts that fire the hook define the matching
	 * constant when they load, which is before `init`. Absence means the host predates the hook,
	 * so the SDK must fall back rather than assume.
	 *
	 * @return bool
	 */
	protected function host_fires_authenticated_scheduled_hook() {
		return $this->is_import_type()
			? defined( 'PMXI_SCHEDULED_REQUEST_AUTH_HOOK' )
			: defined( 'PMXE_SCHEDULED_REQUEST_AUTH_HOOK' );
	}

	/**
	 * Whether the request carries the shape of a scheduling request.
	 *
	 * Shape only — the key is NOT validated here, so this can never authorize anything. It is safe
	 * to *refuse* work on an unvalidated shape; it was authorizing installation on one that made
	 * the request forgeable. Keep those two apart.
	 *
	 * @return bool
	 */
	protected function looks_like_scheduling_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- shape test only; grants nothing.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		if ( $this->is_import_type() ) {
			return $action === 'wpai_public_api'
				|| ( isset( $_GET['import_key'] ) && in_array( $action, array( 'processing', 'trigger', 'pipe', 'cancel', 'cleanup' ), true ) );
		}

		return $action === 'wpae_public_api'
			|| ( isset( $_GET['export_key'] ) && in_array( $action, array( 'processing', 'trigger', 'cancel' ), true ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Answers a scheduled request with the 424 contract on hosts that cannot self-heal.
	 *
	 * A host that fires the authenticated hook installs the companion just before its own add-on
	 * gate evaluates, so this stands aside for those. On an older host nothing installs, and the
	 * host's own gate ends the request with a bare `die()` — no status, so monitors read the
	 * skipped run as a completed one. Running at `init` gets ahead of that.
	 *
	 * This deliberately never installs. Request shape is not authorization; refusing work on an
	 * unvalidated shape is safe, granting install rights on one is what issue #33 was.
	 *
	 * @return void
	 */
	public function block_scheduled_operation_on_legacy_host() {
		if ( $this->host_fires_authenticated_scheduled_hook() ) {
			return;
		}

		if ( ! $this->looks_like_scheduling_request() || $this->is_addon_up_to_date() ) {
			return;
		}

		$this->block_scheduled_operation();
	}

	/**
	 * Blocks manual operations if dependency is missing.
	 *
	 * @param int $operation_id Import or export ID.
	 * @return void
	 */
	public function block_manual_operation_if_dependency_missing( $operation_id ) {
		$this->block_if_dependency_missing( 'manual' );
	}

	/**
	 * Installs the dependency for a scheduled operation the host has authenticated.
	 *
	 * Fired from `pmxi_scheduled_request_authenticated` / `pmxe_scheduled_request_authenticated`,
	 * which the core plugin fires only after it has validated the request's import/export key.
	 * Installation is therefore trusted here regardless of the current request's capabilities.
	 *
	 * @param int|null $operation_id Import or export ID (unused; present for hook compatibility).
	 * @return void
	 */
	public function ensure_dependency_for_scheduled_request( $operation_id = null ) {
		if ( $this->is_addon_up_to_date() ) {
			return;
		}

		try {
			if ( $this->attempt_dependency_installation() ) {
				return;
			}
		} catch ( AddonInstallLockedException $e ) {
			// Another process holds the lock. Returning here would let this run continue with the
			// companion still missing, and the caller reports that as a 200. Answer 424 instead:
			// the dependency genuinely is not satisfied yet, and the run is safe to retry.
			$this->block_scheduled_operation();
		}

		// Still missing after an authorized attempt: fail the scheduled run loudly.
		$this->send_failure_notification_once();
		$this->block_scheduled_operation();
	}

	/**
	 * Additional safety block for any operations (import-only: pmxi_before_post_import).
	 *
	 * @param int        $operation_id Import ID.
	 * @param array|null $data Operation data.
	 * @return void
	 */
	public function block_operation_if_dependency_missing( $operation_id, $data = null ) {
		$this->block_if_dependency_missing( 'operation' );
	}

	/**
	 * Unified method to block operations if dependency is missing.
	 *
	 * @param string $operation_type Type of operation being blocked.
	 * @return void
	 */
	protected function block_if_dependency_missing( $operation_type ) {
		if ( ! $this->is_addon_up_to_date() ) {
			// Only attempt installation if user has proper permissions (or we're in CLI)
			if ( $this->can_install_automatically() ) {
				try {
					// Attempt to install dependency - if successful, allow operation to continue
					if ( $this->attempt_dependency_installation() ) {
						// Dependencies are now satisfied, allow operation to continue
						return;
					}
				} catch ( AddonInstallLockedException $e ) {
					// Another process is installing; treat as work in progress and let it finish.
					return;
				}
			}

			// Installation failed or not permitted, block the operation
			$this->send_failure_notification_once();

			$this->handle_operation_blocking( $operation_type );
		}
	}

	/**
	 * Handles blocking of different operation types.
	 *
	 * @param string $operation_type Type of operation being blocked.
	 * @return void
	 */
	protected function handle_operation_blocking( $operation_type ) {
		switch ( $operation_type ) {
			case 'manual':
				$this->block_manual_operation();
				break;
			case 'operation':
				throw new Exception( $this->get_dependency_error_message() );
		}
	}

	/**
	 * Blocks manual operations (import or export).
	 *
	 * @return void
	 */
	protected function block_manual_operation() {
		// For CLI context, emit a proper WP-CLI error so the message is visible.
		if ( $this->is_cli_context() ) {
			if ( class_exists( 'WP_CLI' ) ) {
				\WP_CLI::error( $this->get_dependency_error_message() );
			}
			exit( 1 );
		}

		// For admin UI, show HTML error
		if ( is_admin() && ! wp_doing_ajax() ) {
			wp_die(
				$this->get_dependency_error_html(),
				$this->safe_translate( 'Dependency Missing', 'Dependency Missing' ),
				array( 'response' => 400 )
			);
		}

		// For AJAX requests, send JSON response
		if ( wp_doing_ajax() ) {
			wp_send_json_error( array(
				'message' => $this->get_dependency_error_message()
			), 400 );
		}

		// Fallback - just die with message
		die( $this->get_dependency_error_message() );
	}

	/**
	 * Blocks scheduled operations (import or export).
	 *
	 * @return void
	 */
	protected function block_scheduled_operation() {
		// Send proper JSON response for scheduling
		if ( ! headers_sent() ) {
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			http_response_code( 424 ); // Failed Dependency
		}

		echo wp_json_encode( array(
			'status' => 424,
			'message' => $this->get_dependency_error_message(),
			'error' => 'dependency_missing'
		) );

		exit;
	}

	/**
	 * Safely translates a string or returns fallback if translations cause issues.
	 *
	 * @param string $text Text to translate.
	 * @param string $fallback Fallback text if translation isn't safe.
	 * @return string
	 */
	protected function safe_translate( $text, $fallback ) {
		// Only translate if we're past the early loading phase
		if ( did_action( 'init' ) && function_exists( '__' ) ) {
			return __( $text, $this->config['textdomain'] );
		}

		return $fallback;
	}

	/**
	 * Attempts to install missing dependency with retry logic for multiple dependencies.
	 *
	 * @return bool True if all dependencies are now satisfied, false otherwise.
	 */
	protected function attempt_dependency_installation() {
		$max_attempts = 3;
		$attempts = 0;

		$this->last_failure_message = '';

		while ( $attempts < $max_attempts && ! $this->is_addon_up_to_date() ) {
			try {
				// Clear any existing status to force fresh installation
				delete_option( $this->get_option_key() );

				// Attempt installation with immediate activation and loading
				$this->install_and_activate_immediately();

				// Give time for the installation to register
				if ( ! $this->is_cli_context() ) {
					sleep( 1 );
				}

				$attempts++;

				// Break early if we've successfully installed
				if ( $this->is_addon_up_to_date() ) {
					break;
				}

			} catch ( AddonInstallLockedException $e ) {
				// Another process holds the install lock. Do not retry or record a failure;
				// let the caller treat this as work in progress.
				throw $e;
			} catch ( Exception $e ) {
				$this->last_failure_message = $e->getMessage();
				$attempts++;
				if ( $attempts >= $max_attempts ) {
					return false;
				}
			}
		}

		$satisfied = $this->is_addon_up_to_date();
		if ( $satisfied ) {
			$this->last_failure_message = '';
		}

		return $satisfied;
	}

	/**
	 * Installs and immediately activates the addon, then forces loading of constants.
	 *
	 * @return void
	 * @throws Exception If installation or activation fails.
	 */
	protected function install_and_activate_immediately() {
		$this->perform_installation_steps();

		// Mark installation as completed
		update_option( $this->get_option_key(), 'completed', true );
	}

	/**
	 * Serializes installation behind a slug-keyed lock, then runs the core steps.
	 *
	 * This is the single entry point for every installer path (web, scheduled, CLI, and
	 * auto-install), so no caller can mutate the plugin directory without the lock. The lock is
	 * site-local (WP_Upgrader::create_lock), so it serializes concurrent installs within a single
	 * site only; cross-subsite coordination on multisite is out of scope here.
	 *
	 * @return void
	 * @throws AddonInstallLockedException If another process holds the install lock.
	 * @throws Exception If installation or activation fails.
	 */
	protected function perform_installation_steps() {
		// Keyed by companion slug (no import/export prefix) so import and export writers to the
		// same plugin directory serialize against each other.
		$lock_name = 'addon_installer_' . sanitize_key( $this->config['addon_slug'] );

		$this->include_required_files();

		if ( ! \WP_Upgrader::create_lock( $lock_name, 300 ) ) {
			throw new AddonInstallLockedException( 'Companion installation is already in progress.' );
		}

		try {
			$this->run_installation_steps();
		} finally {
			\WP_Upgrader::release_lock( $lock_name );
		}
	}

	/**
	 * Performs the core installation steps. Only reached through perform_installation_steps(),
	 * which holds the install lock.
	 *
	 * @return void
	 * @throws Exception If installation or activation fails.
	 */
	protected function run_installation_steps() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->detect_addon();

		// Check if we need to install or update
		if ( version_compare( $this->addon_version, $this->config['minimum_version'], '<' ) ) {
			$this->install_or_upgrade_plugin();

			// Re-detect the addon after installation
			$this->detect_addon();
		}

		// Ensure addon is activated immediately
		$this->ensure_addon_is_activated();

		// Force load the addon plugin file to make constants available immediately
		$this->force_load_addon();

		// Transfer auto-update settings
		$this->transfer_auto_update_settings();
	}

	/**
	 * Installs or upgrades the plugin.
	 *
	 * @return void
	 * @throws Exception If installation fails.
	 */
	protected function install_or_upgrade_plugin() {
		// Ensure we have required WordPress functions available
		$this->include_required_files();

		// Silent installer skin
		$skin = $this->get_silent_installer_skin();

		// Get plugin download URL
		$url = $this->get_plugin_download_url();

		$upgrader = new Plugin_Upgrader( $skin );

		// If the addon already exists on disk, overwrite it (WP 5.5+).
		// Without this, install() fails with "destination folder already exists".
		$args = array();
		if ( $this->addon_version !== '0' ) {
			$args['overwrite_package'] = true;
		}

		$installed = $upgrader->install( $url, $args );

		if ( is_wp_error( $installed ) || ! $installed ) {
			if ( is_wp_error( $installed ) ) {
				$cause = $installed->get_error_message();
				// WordPress puts the underlying cause (e.g. the cURL error
				// behind "Download failed.", or the filesystem error behind
				// "Filesystem error.") in the error data.
				$data = $installed->get_error_data();
				if ( is_string( $data ) && $data !== '' ) {
					$cause .= ' ' . $data;
				} elseif ( is_wp_error( $data ) && $data->get_error_message() ) {
					$cause .= ' ' . $data->get_error_message();
				}
			} elseif ( ! empty( $skin->captured_errors ) ) {
				$cause = implode( ' ', $skin->captured_errors );
			} else {
				$cause = 'WordPress did not report an error.';
			}

			throw new Exception( sprintf( 'Could not install %s: %s', $this->config['addon_name'], $cause ) );
		}

		// Force plugin cache refresh after installation
		$this->refresh_plugin_cache();
	}

	/**
	 * Includes required WordPress files for installation.
	 *
	 * @return void
	 */
	protected function include_required_files() {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include_once ABSPATH . 'wp-includes/pluggable.php';
		}
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	/**
	 * Gets a silent installer skin.
	 *
	 * @return object Anonymous class extending Plugin_Installer_Skin.
	 */
	protected function get_silent_installer_skin() {
		return new class() extends Plugin_Installer_Skin {
			public $captured_errors = array();

			public function header() {}
			public function footer() {}

			public function error( $errors ) {
				if ( is_wp_error( $errors ) ) {
					$this->capture_wp_error( $errors );
				} elseif ( is_string( $errors ) && $errors !== '' ) {
					// WordPress may pass a key into the upgrader's strings.
					$this->captured_errors[] = isset( $this->upgrader->strings[ $errors ] )
						? $this->upgrader->strings[ $errors ]
						: $errors;
				}
			}

			public function feedback( $feedback, ...$args ) {}

			// Filesystem-connection failures are routed here, not to error().
			public function request_filesystem_credentials( $error = false, $context = '', $allow_relaxed_file_ownership = false ) {
				if ( is_wp_error( $error ) ) {
					$this->capture_wp_error( $error );
				} elseif ( true === $error ) {
					$this->captured_errors[] = 'Could not access the filesystem.';
				}

				return parent::request_filesystem_credentials( $error, $context, $allow_relaxed_file_ownership );
			}

			private function capture_wp_error( $error ) {
				$message = $error->get_error_message();
				if ( ! $message ) {
					return;
				}

				$data = $error->get_error_data();
				if ( is_string( $data ) && $data !== '' ) {
					$message .= ' ' . $data;
				} elseif ( is_wp_error( $data ) && $data->get_error_message() ) {
					$message .= ' ' . $data->get_error_message();
				}

				$this->captured_errors[] = $message;
			}
		};
	}

	/**
	 * Gets the plugin download URL.
	 *
	 * @return string Plugin download URL.
	 */
	protected function get_plugin_download_url() {
		// Always download the latest version from WordPress.org.
		return sprintf(
			'https://downloads.wordpress.org/plugin/%s.zip',
			$this->config['addon_slug']
		);
	}

	/**
	 * Refreshes the plugin cache.
	 *
	 * @return void
	 */
	protected function refresh_plugin_cache() {
		if ( function_exists( 'wp_clean_plugins_cache' ) ) {
			wp_clean_plugins_cache();
		}

		// Also clear general cache in CLI context
		if ( $this->is_cli_context() ) {
			wp_cache_flush();
		}
	}

	/**
	 * Gets the dependency error message.
	 *
	 * @param bool $check_admin Whether to include admin check message.
	 * @return string
	 */
	protected function get_dependency_error_message( $check_admin = true ) {
		$message = sprintf(
			'%1$s could not install and activate its required companion plugin, %2$s. This %3$s cannot run without it.',
			$this->config['pro_plugin_name'],
			$this->config['addon_name'],
			$this->get_operation_label()
		);

		$cause = $this->get_last_failure_message();
		if ( $cause !== '' ) {
			$message .= " Details: {$cause}";
		}

		if ( $check_admin ) {
			$message .= sprintf(
				' To fix it, install and activate "%1$s" from %2$s or from Plugins → Add Plugin.',
				$this->config['addon_name'],
				$this->get_companion_directory_url()
			);
		}

		return $message;
	}

	/**
	 * Returns the most recent failure cause as safe plain text.
	 *
	 * @return string
	 */
	protected function get_last_failure_message() {
		$cause = trim( wp_strip_all_tags( $this->last_failure_message ) );

		if ( strlen( $cause ) > 500 ) {
			// mb_substr keeps the cut on a character boundary; invalid UTF-8
			// would make esc_html() discard the whole string.
			$cause = mb_substr( $cause, 0, 500 ) . '...';
		}

		return $cause;
	}

	/**
	 * Gets the dependency error as HTML for admin display.
	 *
	 * @return string
	 */
	protected function get_dependency_error_html() {
		$message = $this->get_dependency_error_message( false );

		$install_link = '';
		if ( current_user_can( 'activate_plugins' ) ) {
			$install_link = sprintf(
				'<p><a href="%s" class="button button-primary">%s</a></p>',
				esc_url( self_admin_url( 'plugins.php' ) ),
				esc_html( $this->safe_translate( 'Check Installed Plugins', 'Check Installed Plugins' ) )
			);
		}

		return sprintf(
			'<div style="padding: 20px;"><h2>%s</h2><p>%s</p>%s</div>',
			esc_html( $this->safe_translate( 'Missing Dependency', 'Missing Dependency' ) ),
			esc_html( $message ),
			$install_link
		);
	}

	/**
	 * Sends failure notification email to admin (only once).
	 *
	 * @return void
	 */
	protected function send_failure_notification_once() {
		// Check if email alerts are disabled
		if ( ! $this->config['send_email_alert'] ) {
			return;
		}

		$notification_key = $this->get_operation_prefix() . '_addon_failure_notified_' . sanitize_key( $this->config['addon_slug'] );

		// Check if we've already sent notification for this addon
		if ( get_option( $notification_key ) ) {
			return;
		}

		// Mark as notified
		update_option( $notification_key, time(), false );

		// Ensure wp_mail is available
		if ( ! function_exists( 'wp_mail' ) ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}

		// Get admin email
		$admin_email = get_option( 'admin_email' );
		if ( ! $admin_email ) {
			return;
		}

		$product   = $this->get_product_family_name();
		$pro       = $this->config['pro_plugin_name'];
		$companion = $this->config['addon_name'];
		$label     = $this->get_operation_label() . 's';
		$details   = $this->get_last_failure_message();

		$subject = sprintf(
			'[%s] %s has encountered an error and needs your attention',
			get_bloginfo( 'name' ),
			$product
		);

		$message  = "Hello,\n\n";
		$message .= sprintf( "This email was sent by %s, running on %s.\n\n", $product, home_url() );
		$message .= sprintf(
			"%s requires a free companion plugin, %s, which it normally installs automatically from wordpress.org. It could not be installed and activated.\n\n",
			$pro,
			$companion
		);
		if ( $details !== '' ) {
			$message .= "Details: {$details}\n\n";
		}
		$message .= sprintf( "Until this is fixed, %s that rely on %s may not run.\n\n", $label, $pro );
		$message .= sprintf(
			'To fix it: in your WordPress admin, go to Plugins → Add Plugin, then install and activate "%s", or get it directly from %s',
			$companion,
			$this->get_companion_directory_url()
		);
		if ( $this->config['support_url'] !== '' ) {
			$message .= sprintf( "\n\nIf you are still having issues, please contact us at: %s", $this->config['support_url'] );
		}

		// Send email
		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Gets the option key for tracking installer status.
	 *
	 * @return string
	 */
	protected function get_option_key() {
		return $this->get_operation_prefix() . '_addon_installer_' . sanitize_key( $this->config['addon_slug'] );
	}

	/**
	 * Performs the installer if it hasn't been done yet.
	 *
	 * @return void
	 */
	public function install_addon_from_repository() {
		// Defer activation if we're too early in the WordPress loading process
		if ( ! did_action( 'plugins_loaded' ) && ! $this->is_cli_context() ) {
			add_action( 'plugins_loaded', array( $this, 'install_addon_from_repository' ) );
			return;
		}

		// If we're in CLI context, handle dependencies immediately and synchronously
		if ( $this->is_cli_context() ) {
			$this->handle_cli_installation();
			return;
		}

		// Normal web interface installation
		$this->setup_web_interface_hooks();

		// Check current installation status
		$status = $this->get_status();

		if ( ! $status ) {
			// Decide permissions now, before install() loads companion code
			// that could change capability checks.
			$can_install_automatically = $this->can_install_automatically();

			$this->last_failure_message = '';

			// Only capability-authorized (or CLI) requests may install here. Unauthorized
			// requests fall through; a scheduled run self-heals through its authenticated hook.
			if ( $can_install_automatically ) {
				try {
					$this->install();
				} catch ( AddonInstallLockedException $e ) {
					// Another process is installing; leave it to complete.
				} catch ( Exception $e ) {
					// Installation failed. The email decision happens below,
					// based on the actual plugin state.
					$this->last_failure_message = $e->getMessage();
				}
			}

			// Never report failure while the companion is installed, active,
			// and current. Email only when the dependency is genuinely still
			// unsatisfied and this request wasn't allowed to fix it.
			if ( ! $this->is_addon_up_to_date() && ! $can_install_automatically ) {
				$this->send_failure_notification_once();
			}
		} elseif ( $status === 'started' ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$this->detect_addon();
			if ( is_plugin_active( $this->addon_file ) ) {
				// Addon is active so mark installation as successful.
				update_option( $this->get_option_key(), 'completed', true );
			}
		}
	}

	/**
	 * Sets up hooks for web interface.
	 *
	 * @return void
	 */
	protected function setup_web_interface_hooks() {
		// Only add notification hooks if translations are ready
		if ( did_action( 'init' ) ) {
			add_action( 'admin_notices', array( $this, 'show_install_addon_notification' ) );
			add_action( 'network_admin_notices', array( $this, 'show_install_addon_notification' ) );
		} else {
			// Defer notification hooks until translations are ready
			add_action( 'init', function() {
				add_action( 'admin_notices', array( $this, 'show_install_addon_notification' ) );
				add_action( 'network_admin_notices', array( $this, 'show_install_addon_notification' ) );
			} );
		}

		add_action( 'plugins_loaded', array( $this, 'validate_installation_status' ) );

		// Auto-install dependencies when WordPress is ready
		add_action( 'wp_loaded', array( $this, 'auto_install_dependencies' ) );

		if ( $this->config['disable_deactivation'] ) {
			add_filter( 'plugin_action_links', array( $this, 'disable_deactivation_link' ), 10, 2 );
			add_filter( 'network_admin_plugin_action_links', array( $this, 'disable_deactivation_link' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'prevent_deactivation' ) );
			add_action( 'admin_notices', array( $this, 'show_kept_addon_notice' ) );
			add_action( 'network_admin_notices', array( $this, 'show_kept_addon_notice' ) );
			add_filter( 'removable_query_args', array( $this, 'register_kept_addon_query_arg' ) );
		}
	}

	/**
	 * Auto-installs dependencies when WordPress is loaded.
	 *
	 * @return void
	 */
	public function auto_install_dependencies() {
		// Skip if we're in admin and doing AJAX (to avoid conflicts)
		if ( is_admin() && wp_doing_ajax() ) {
			return;
		}

		// Skip if already up to date
		if ( $this->is_addon_up_to_date() ) {
			return;
		}

		// Only auto-install if we have the necessary permissions (or we're in CLI)
		if ( ! $this->can_install_automatically() ) {
			return;
		}

		// Serialization is handled inside perform_installation_steps(); acquiring a second
		// lock here would deadlock against it.
		try {
			$this->attempt_dependency_installation();
		} catch ( AddonInstallLockedException $e ) {
			// Another process is installing; nothing to do.
		} catch ( Exception $e ) {
			// Silent failure for auto-installation
		}
	}

	/**
	 * Prevents deactivation of the addon plugin.
	 *
	 * @return void
	 */
	public function prevent_deactivation() {
		if ( $this->is_bulk_deactivation_request() ) {
			$this->prevent_bulk_deactivation();
			return;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'deactivate' ) {
			return;
		}

		if ( ! isset( $_GET['plugin'] ) ) {
			return;
		}

		$plugin = sanitize_text_field( wp_unslash( $_GET['plugin'] ) );

		if ( $plugin === $this->addon_file ) {
			$this->die_deactivation_prevented();
		}
	}

	/**
	 * Checks whether the Plugins screen is handling a bulk deactivation.
	 *
	 * @return bool
	 */
	protected function is_bulk_deactivation_request() {
		// Mirrors WP_List_Table::current_action(): the top selector wins unless it is unset.
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '-1';
		if ( '-1' === (string) $action && isset( $_REQUEST['action2'] ) ) {
			$action = $_REQUEST['action2'];
		}

		return 'deactivate-selected' === $action;
	}

	/**
	 * Removes the addon from a bulk deactivation so the other selected plugins still deactivate.
	 *
	 * @return void
	 */
	protected function prevent_bulk_deactivation() {
		if ( empty( $_POST['checked'] ) || ! is_array( $_POST['checked'] ) ) {
			return;
		}

		$checked = array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['checked'] ) );

		if ( ! in_array( $this->addon_file, $checked, true ) ) {
			return;
		}

		$remaining = array_values( array_diff( $checked, array( $this->addon_file ) ) );

		if ( empty( $remaining ) ) {
			$this->die_deactivation_prevented();
		}

		$_POST['checked']    = $remaining;
		$_REQUEST['checked'] = $remaining;

		add_filter( 'wp_redirect', array( $this, 'flag_kept_addon_on_redirect' ) );
	}

	/**
	 * Carries the kept-addon notice across the redirect that follows a bulk deactivation.
	 *
	 * @param string $location Redirect URL.
	 * @return string
	 */
	public function flag_kept_addon_on_redirect( $location ) {
		return add_query_arg( $this->get_kept_addon_query_arg(), '1', $location );
	}

	/**
	 * Shows why the addon stayed active after a bulk deactivation.
	 *
	 * @return void
	 */
	public function show_kept_addon_notice() {
		if ( empty( $_GET[ $this->get_kept_addon_query_arg() ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			$this->get_deactivation_prevented_message()
		);
	}

	/**
	 * Lets WordPress strip the notice flag from the address bar so a reload does not repeat it.
	 *
	 * @param array $args Removable query args.
	 * @return array
	 */
	public function register_kept_addon_query_arg( $args ) {
		$args[] = $this->get_kept_addon_query_arg();
		return $args;
	}

	/**
	 * @return string
	 */
	protected function get_kept_addon_query_arg() {
		return 'addon_installer_kept_' . sanitize_key( $this->config['addon_slug'] );
	}

	/**
	 * @return string Escaped message.
	 */
	protected function get_deactivation_prevented_message() {
		return sprintf(
			'%1$s cannot be deactivated because it is required by %2$s.',
			esc_html( $this->config['addon_name'] ),
			esc_html( $this->config['pro_plugin_name'] )
		);
	}

	/**
	 * @return void
	 */
	protected function die_deactivation_prevented() {
		wp_die(
			$this->get_deactivation_prevented_message(),
			'Plugin Deactivation Prevented',
			array( 'back_link' => true )
		);
	}

	/**
	 * Checks if automatic installation is possible.
	 *
	 * @return bool
	 */
	protected function can_install_automatically() {
		// In CLI context, allow installation regardless of user permissions since CLI runs with
		// elevated system permissions. Scheduling requests are NOT exempted here: an unvalidated
		// request must not gain install authority. A scheduled request installs only through
		// ensure_dependency_for_scheduled_request(), fired after the host authenticates the key.
		if ( $this->is_cli_context() ) {
			return true;
		}

		// For web/manual context, strictly require both permissions
		$can_install = current_user_can( 'install_plugins' );
		$can_activate = current_user_can( 'activate_plugins' );

		return $can_install && $can_activate;
	}

	/**
	 * Checks if we're in CLI context.
	 *
	 * @return bool
	 */
	protected function is_cli_context() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Handles installation in CLI context with immediate dependency resolution.
	 *
	 * If installation fails and the current command is not a relevant operation
	 * (e.g. not `wp all-import run`), a warning is shown and execution continues.
	 * The hard block is reserved for actual import/export run commands.
	 *
	 * @return void
	 * @throws Exception If dependencies cannot be satisfied during a relevant operation.
	 */
	protected function handle_cli_installation() {
		try {
			if ( $this->is_addon_up_to_date() ) {
				// The version check force-loads an inactive companion, so it can pass while the companion is still inactive.
				if ( did_action( 'init' ) ) {
					$this->activate_current_addon_in_cli();
				} else {
					// activate_plugin() translates plugin headers, which must wait for init.
					add_action( 'init', array( $this, 'activate_current_addon_in_cli' ), 999 );
				}
				return;
			}

			// Clear any existing status to force fresh installation
			delete_option( $this->get_option_key() );

			// For CLI, defer activation until after WordPress is fully loaded
			if ( did_action( 'init' ) ) {
				// We're past init, safe to install synchronously
				$this->install_synchronous();
			} else {
				// Defer until after init to avoid translation loading issues
				add_action( 'init', function() {
					try {
						$this->install_synchronous();
						$this->refresh_plugin_cache();
						sleep( 1 );
						if ( ! $this->is_addon_up_to_date() ) {
							$this->handle_cli_dependency_failure();
						}
					} catch ( Exception $e ) {
						$this->handle_cli_dependency_failure( $e->getMessage() );
					}
				}, 999 );
				return; // Exit early, installation will happen on init
			}

			// Force WordPress to refresh plugin cache
			$this->refresh_plugin_cache();

			// Wait a moment for filesystem operations to complete
			sleep( 1 );

			// Verify installation succeeded
			if ( ! $this->is_addon_up_to_date() ) {
				$this->handle_cli_dependency_failure();
			}
		} catch ( Exception $e ) {
			$this->handle_cli_dependency_failure( $e->getMessage() );
		}
	}

	/**
	 * Activates a companion that is installed and current but inactive.
	 *
	 * @return void
	 */
	public function activate_current_addon_in_cli() {
		try {
			$this->detect_addon();
			if ( file_exists( WP_PLUGIN_DIR . '/' . $this->addon_file ) ) {
				$this->ensure_addon_is_activated();
			}
		} catch ( Exception $e ) {
			$this->handle_cli_dependency_failure( $e->getMessage() );
		}
	}

	/**
	 * Handles a dependency failure in CLI context.
	 *
	 * Emits a fatal WP-CLI error (halting execution) only when the current command
	 * is a relevant operation such as `wp all-import run`. For all other CLI
	 * commands a non-fatal warning is shown and execution continues normally.
	 *
	 * @param string|null $message Optional override message; falls back to the
	 *                             standard dependency error message.
	 * @return void
	 * @throws Exception When not in a WP-CLI environment and the operation is relevant.
	 */
	protected function handle_cli_dependency_failure( $message = null ) {
		if ( $message !== null && $message !== '' ) {
			// Feed the specific failure into the shared message.
			$this->last_failure_message = $message;
		}

		$error_message = $this->get_dependency_error_message();

		if ( class_exists( 'WP_CLI' ) ) {
			if ( $this->is_relevant_cli_operation() ) {
				// Hard stop: the command is an import/export run and cannot proceed.
				\WP_CLI::error( $error_message );
			} else {
				// Soft warning: unrelated commands (e.g. `wp plugin list`) should
				// still work even when the dependency could not be installed.
				\WP_CLI::warning( $error_message );
				return;
			}
		}

		throw new Exception( $error_message );
	}

	/**
	 * Performs synchronous installation for CLI context.
	 *
	 * @return void
	 * @throws Exception If installation fails.
	 */
	protected function install_synchronous() {
		$this->perform_installation_steps();

		// Mark installation as completed
		update_option( $this->get_option_key(), 'completed', true );
	}

	/**
	 * Disables the deactivation link for the free addon and shows a required message.
	 *
	 * @param array  $actions Plugin action links.
	 * @param string $plugin_file Plugin file path.
	 * @return array Modified action links.
	 */
	public function disable_deactivation_link( array $actions, $plugin_file ) {
		if ( $plugin_file === $this->addon_file ) {
			if ( isset( $actions['deactivate'] ) ) {
				$actions['deactivate'] = sprintf(
					'<span style="color: #666;">%s</span>',
					sprintf(
						'Required by %s',
						esc_html( $this->config['pro_plugin_name'] )
					)
				);
			}
		}
		return $actions;
	}

	/**
	 * Displays a notification to install the addon.
	 *
	 * @return void
	 */
	public function show_install_addon_notification() {
		if ( ! $this->should_show_notification() ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$this->detect_addon();

		$action = $this->get_notification_action();

		if ( ! $action ) {
			return;
		}

		$css = esc_attr( $this->get_css_prefix() );
		$details = $this->get_last_failure_message();
		$details_html = $details !== ''
			? '<p>' . esc_html( "Automatic installation failed: {$details}" ) . '</p>'
			: '';
		printf(
			'<div class="error ' . $css . '-addon-notice">
				<h4 class="' . $css . '-notice-header">%s</h4>
				<div class="notice-' . $css . '-content">
					<p>%s</p>
					%s
					<p>%s</p>
				</div>
			</div>',
			sprintf(
				$this->safe_translate( 'Install latest %s', 'Install latest %s' ),
				esc_html( $this->config['addon_name'] )
			),
			sprintf(
				$this->safe_translate( '%1$s %2$s must be installed and activated in order to use %3$s.', '%1$s %2$s must be installed and activated in order to use %3$s.' ),
				esc_html( $this->config['addon_name'] ),
				esc_html( $this->config['minimum_version'] ),
				esc_html( $this->config['pro_plugin_name'] )
			),
			$details_html,
			$action // Already escaped in get_notification_action()
		);
	}

	/**
	 * Returns the notification action to display.
	 *
	 * @return string|false The notification action or false if no action should be taken.
	 */
	protected function get_notification_action() {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$minimum_version_met = version_compare( $this->addon_version, $this->config['minimum_version'], '>=' );
		$network_active = is_plugin_active_for_network( plugin_basename( $this->config['pro_plugin_file'] ) );
		$addon_active = ( $network_active ) ? is_plugin_active_for_network( $this->addon_file ) : is_plugin_active( $this->addon_file );

		if ( $minimum_version_met && $addon_active ) {
			return false;
		}

		if ( $minimum_version_met ) {
			$permission = 'activate_plugins';
		} elseif ( $this->addon_version !== '0' ) {
			$permission = 'update_plugins';
		} else {
			$permission = 'install_plugins';
		}

		if ( current_user_can( $permission ) ) {
			switch ( $permission ) {
				case 'activate_plugins':
					if ( $network_active ) {
						$base_url = network_admin_url( 'plugins.php?action=activate&plugin=' . $this->addon_file );
						$button_content = $this->safe_translate( '%2$sNetwork Activate %1$s now%3$s', '%2$sNetwork Activate %1$s now%3$s' );
					} else {
						$base_url = self_admin_url( 'plugins.php?action=activate&plugin=' . $this->addon_file );
						$button_content = $this->safe_translate( '%2$sActivate %1$s now%3$s', '%2$sActivate %1$s now%3$s' );
					}
					$url = wp_nonce_url( $base_url, 'activate-plugin_' . $this->addon_file );
					break;
				case 'update_plugins':
					$url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $this->addon_file ), 'upgrade-plugin_' . $this->addon_file );
					$button_content = $this->safe_translate( '%2$sUpgrade %1$s now%3$s', '%2$sUpgrade %1$s now%3$s' );
					break;
				case 'install_plugins':
					$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $this->config['addon_slug'] ), 'install-plugin_' . $this->config['addon_slug'] );
					$button_content = $this->safe_translate( '%2$sInstall %1$s now%3$s', '%2$sInstall %1$s now%3$s' );
					break;
			}
			return sprintf(
				esc_html( $button_content ),
				esc_html( $this->config['addon_name'] ),
				'<a class="button" href="' . esc_url( $url ) . '">',
				'</a>'
			);
		}

		if ( is_multisite() ) {
			$message = $this->safe_translate( 'Please contact a network administrator to install %1$s %2$s.', 'Please contact a network administrator to install %1$s %2$s.' );
		} else {
			$message = $this->safe_translate( 'Please contact an administrator to install %1$s %2$s.', 'Please contact an administrator to install %1$s %2$s.' );
		}
		return sprintf(
			esc_html( $message ),
			esc_html( $this->config['addon_name'] ),
			esc_html( $this->config['minimum_version'] )
		);
	}

	/**
	 * Checks if the addon is at the minimum required version.
	 *
	 * @return bool True if addon is at the minimal required version
	 */
	public function is_addon_up_to_date() {
		// If version and edition constants are provided, check them
		if ( ! empty( $this->config['version_constant'] ) ) {
			if ( ! defined( $this->config['version_constant'] ) ) {
				// In CLI context, try to force load the addon to make constants available
				if ( $this->is_cli_context() ) {
					$this->detect_addon();
					$this->force_load_addon();
				}

				// Check again after force loading
				if ( ! defined( $this->config['version_constant'] ) ) {
					return false;
				}
			}

			$version_check = version_compare(
				constant( $this->config['version_constant'] ),
				$this->config['minimum_version'],
				'>='
			);

			// Also check edition if specified
			if ( ! empty( $this->config['edition_constant'] ) ) {
				$edition_check = defined( $this->config['edition_constant'] ) &&
					constant( $this->config['edition_constant'] ) === $this->config['expected_edition'];
				return $version_check && $edition_check;
			}

			return $version_check;
		}

		// Fallback to plugin detection
		$this->detect_addon();

		// Check if plugin is active
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( ! is_plugin_active( $this->addon_file ) ) {
			return false;
		}

		return version_compare( $this->addon_version, $this->config['minimum_version'], '>=' );
	}

	/**
	 * Resets the installation status if addon is not installed or outdated.
	 *
	 * @return void
	 */
	public function validate_installation_status() {
		if ( ! $this->is_addon_up_to_date() ) {
			delete_option( $this->get_option_key() );
		}
	}

	/**
	 * Returns the status of the installer.
	 *
	 * @return string|false false if the installer hasn't been started.
	 *                      "started" if it has but hasn't completed.
	 *                      "completed" if it has been completed.
	 */
	protected function get_status() {
		return get_option( $this->get_option_key() );
	}

	/**
	 * Installs the addon.
	 *
	 * @return void
	 * @throws Exception If the installer failed.
	 */
	protected function install() {
		if ( $this->get_status() ) {
			return;
		}

		// Mark the installer as having been started but not completed.
		update_option( $this->get_option_key(), 'started', true );

		$this->perform_installation_steps();

		// Mark the installer as having been completed.
		update_option( $this->get_option_key(), 'completed', true );
	}

	/**
	 * Force loads the addon plugin file to make constants available immediately.
	 *
	 * @return void
	 */
	protected function force_load_addon() {
		if ( ! empty( $this->addon_file ) ) {
			$addon_path = WP_PLUGIN_DIR . '/' . $this->addon_file;
			if ( file_exists( $addon_path ) && is_readable( $addon_path ) ) {
				// Include the plugin file to make constants available
				include_once $addon_path;

				// Also try to include the main plugin file if it has a different name
				$addon_dir = dirname( $addon_path );
				$main_files = array(
					$addon_dir . '/' . $this->config['addon_slug'] . '.php',
					$addon_dir . '/plugin.php',
					$addon_dir . '/index.php',
					$addon_dir . '/main.php'
				);

				foreach ( $main_files as $main_file ) {
					if ( file_exists( $main_file ) && is_readable( $main_file ) && $main_file !== $addon_path ) {
						include_once $main_file;
						break;
					}
				}
			}
		}
	}

	/**
	 * Detects the addon plugin file and version.
	 *
	 * @return void
	 */
	protected function detect_addon() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Make sure addon isn't already installed in another directory.
		foreach ( get_plugins() as $file => $plugin ) {
			// Look for the exact plugin name and author to identify it correctly.
			if (
				isset( $plugin['Name'] ) && $plugin['Name'] === $this->config['addon_name']
				&& isset( $plugin['Author'] ) && $plugin['Author'] === $this->config['addon_author']
			) {
				$this->addon_file = $file;
				$this->addon_version = isset( $plugin['Version'] ) ? $plugin['Version'] : '0';
				$this->addon_dir = WP_PLUGIN_DIR . '/' . dirname( $file );
				break;
			}
		}
	}

	/**
	 * Activates the addon.
	 *
	 * @return void
	 * @throws Exception If addon could not be activated.
	 */
	protected function ensure_addon_is_activated() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( $this->addon_file ) ) {
			$network_active = is_plugin_active_for_network( plugin_basename( $this->config['pro_plugin_file'] ) );
			// If we're not active at all it means we're being activated.
			if ( ! $network_active && ! is_plugin_active( plugin_basename( $this->config['pro_plugin_file'] ) ) ) {
				// So set network active to whether or not we're in the network admin.
				// In WP-CLI there is no request, so networkwide can only come from activate_plugin() handling --network.
				$network_active = is_network_admin() || ( $this->is_cli_context() && ! empty( $_GET['networkwide'] ) );
			}

			// Activate addon. If pro plugin is network active then make sure addon is as well.
			$activation = activate_plugin( $this->addon_file, '', $network_active, true );

			if ( is_wp_error( $activation ) ) {
				throw new Exception( sprintf(
					'Could not activate %s: %s',
					$this->config['addon_name'],
					$activation->get_error_message()
				) );
			}
		}
	}

	/**
	 * Transfers the auto update settings from pro plugin to addon.
	 *
	 * @return void
	 */
	protected function transfer_auto_update_settings() {
		$auto_updates = (array) get_site_option( 'auto_update_plugins', array() );

		if ( in_array( plugin_basename( $this->config['pro_plugin_file'] ), $auto_updates, true ) ) {
			$auto_updates[] = $this->addon_file;
			$auto_updates = array_unique( $auto_updates );
			update_site_option( 'auto_update_plugins', $auto_updates );
		}
	}

	/**
	 * Whether or not the notification to install addon should be shown.
	 *
	 * @return bool
	 */
	protected function should_show_notification() {
		global $pagenow;

		// Do not output on plugin / theme upgrade pages or when WordPress is upgrading.
		if ( ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) || wp_installing() ) {
			return false;
		}

		// IFRAME_REQUEST is not defined on these pages, though these action pages do show when upgrading themes or plugins.
		$actions = array( 'do-theme-upgrade', 'do-plugin-upgrade', 'do-core-upgrade', 'do-core-reinstall' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], $actions, true ) ) {
			return false;
		}

		if ( (isset( $_GET['action'] ) && $_GET['action'] == 'install-plugin') && (isset( $_GET['plugin'] ) && $_GET['plugin'] == $this->config['addon_slug'])) {
			return false;
		}

		// Show on ALL admin pages when dependency is missing
		if ( is_admin() && ! $this->is_addon_up_to_date() ) {
			return true;
		}

		return false;
	}
}