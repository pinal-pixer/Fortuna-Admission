<?php
/**
 * Plugins functionality REST endpoint.
 *
 * @link    http://wpmudev.com
 * @since   4.12.0
 * @author  Joel James <joel@incsub.com>
 * @package WPMUDEV\Dashboard\Endpoints
 */

namespace WPMUDEV\Dashboard\Endpoints;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPMUDEV_Dashboard;

/**
 * Class Plugins
 */
class Plugins extends Endpoint {

	/**
	 * API endpoint for the current endpoint.
	 *
	 * @since 5.0.0
	 * @var string $endpoint
	 */
	private string $endpoint = '/plugins';


	/**
	 * Register the routes for handling settings functionality.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function register_routes() {
		// Plugin list.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_plugins' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'has_update'        => array(
							'required'    => false,
							'type'        => 'boolean',
							'description' => __( 'Filter plugins by updates available.', 'wpmudev' ),
						),
						'is_addon'          => array(
							'required'    => false,
							'type'        => 'boolean',
							'description' => __( 'Filter only addon plugins.', 'wpmudev' ),
						),
						'search'            => array(
							'required'    => false,
							'type'        => 'string',
							'description' => __( 'Search query.', 'wpmudev' ),
						),
						'include_changelog' => array(
							'required'    => false,
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Should fetch and include changelog entries.', 'wpmudev' ),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/(?P<pid>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_plugin' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'pid'               => array(
							'type'        => 'integer',
							'required'    => true,
							'description' => __( 'Plugin ID to get details.', 'wpmudev' ),
						),
						'include_changelog' => array(
							'required'    => false,
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Should fetch and include changelog entries.', 'wpmudev' ),
						),
					),
				),
			)
		);

		// Plugin actions.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/action',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'run_action' ),
					'permission_callback' => array( $this, 'action_permissions_check' ),
					'args'                => array(
						'pid'    => array(
							'required'    => true,
							'type'        => 'integer',
							'description' => __( 'Plugin ID to perform action.', 'wpmudev' ),
						),
						'action' => array(
							'required'    => true,
							'type'        => 'string',
							'enum'        => array(
								'activate',
								'deactivate',
								'install',
								'install_activate',
								'uninstall',
								'update',
								'upgrade',
							),
							'description' => __( 'Action to perform.', 'wpmudev' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Get the list of plugins
	 *
	 * Project data (name, info, description, features, changelog) is returned raw on purpose: this is a
	 * JSON response (application/json + X-Content-Type-Options: nosniff), not an HTML sink, so escaping
	 * here would only double-encode it for the consumer. Poisoned remote metadata cannot XSS our admin
	 * either - the React app renders these values as JSX children (auto-escaped) and never through
	 * dangerouslySetInnerHTML. Access is also limited to manage_options/manage_network_options plus the
	 * Dashboard allowed-users list, see Endpoint::permissions_check().
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_plugins( WP_REST_Request $request ): WP_REST_Response {
		$is_addon          = $request->get_param( 'is_addon' );
		$has_update        = $request->get_param( 'has_update' );
		$search            = $request->get_param( 'search' );
		$include_changelog = (bool) $request->get_param( 'include_changelog' );

		$data = WPMUDEV_Dashboard::$api->get_projects_data();
		// Handle Snapshot duplicates.
		$data     = WPMUDEV_Dashboard::$ui->handle_snapshot_v4( $data );
		$projects = $data['projects'] ?? array();

		$plugins = array();

		foreach ( $projects as $key => $project ) {
			// Only plugin types.
			if ( 'plugin' !== $project['type'] ) {
				continue;
			}
			// Get plugin data.
			$plugin = WPMUDEV_Dashboard::$site->get_project_info( $project['id'], $include_changelog );

			if ( empty( $plugin->pid ?? null ) ) {
				continue;
			}

			if ( $plugin->is_hidden ) {
				continue;
			}

			// Filter by updates.
			if ( null !== $has_update && (bool) $has_update !== $plugin->has_update ) {
				continue;
			}

			// Filter by addon.
			if ( null !== $is_addon && ( $plugin->is_addon ?? false ) !== (bool) $is_addon ) {
				continue;
			}

			// Filter by search.
			if ( ! empty( $search ) && false === stripos( $plugin->name, $search ) ) {
				continue;
			}

			// Add to plugins.
			$plugins[] = $plugin;
		}

		return $this->get_response( $plugins );
	}

	/**
	 * Get a single plugin data.
	 *
	 * Same as get_plugins(): JSON output, no HTML context, escaped by the React consumer.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_plugin( WP_REST_Request $request ) {
		$pid               = $request->get_param( 'pid' );
		$include_changelog = (bool) $request->get_param( 'include_changelog' );

		$project = WPMUDEV_Dashboard::$site->get_project_info( $pid, $include_changelog );

		// Only valid plugins.
		if ( empty( $project->pid ?? null ) || 'plugin' !== $project->type || $project->is_hidden ) {
			return $this->get_error_response( 'invalid_plugin', __( 'Invalid plugin.', 'wpmudev' ) );
		}

		return $this->get_response( $project );
	}

	/**
	 * Shared permission check for the action endpoint.
	 *
	 * The capability is resolved from the requested action.
	 * Beside the default Dashboard check, this requires the same WP core meta capability for the equivalent action.
	 * On multisite, the default check requires manage_network_options for all network-wide actions.
	 *
	 * Unknown actions are rejected by the route "enum" validation, run_action() only maps known ones.
	 *
	 * @since 5.0.3
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool
	 */
	public function action_permissions_check( WP_REST_Request $request ): bool {
		$caps = array(
			'activate'         => 'activate_plugins',
			'deactivate'       => 'activate_plugins',
			'install'          => 'install_plugins',
			'install_activate' => array( 'install_plugins', 'activate_plugins' ),
			'uninstall'        => 'delete_plugins',
			'update'           => 'update_plugins',
			// Free to Pro replacement installs the Pro version and removes the free one.
			'upgrade'          => array( 'install_plugins', 'delete_plugins' ),
		);

		$action = (string) $request->get_param( 'action' );

		if ( ! isset( $caps[ $action ] ) ) {
			return $this->permissions_check();
		}
		// extra cap required for install_activate on multisite, block early.
		if ( is_multisite() && 'install_activate' === $action ) {
			$caps[ $action ][] = 'manage_network_plugins';
		}

		return $this->permissions_check_caps( $caps[ $action ] );
	}

	/**
	 * Callback for action endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_action( WP_REST_Request $request ) {
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		$pid = (int) $request->get_param( 'pid' );
		if ( empty( $pid ) ) {
			return $this->get_error_response( 'invalid_plugin', __( 'Invalid plugin ID.', 'wpmudev' ) );
		}

		switch ( $request->get_param( 'action' ) ) {
			case 'activate':
				return $this->activate_plugin( $request );
			case 'deactivate':
				return $this->deactivate_plugin( $request );
			case 'install':
				return $this->install_plugin( $request );
			case 'install_activate':
				return $this->install_activate_plugin( $request );
			case 'uninstall':
				return $this->uninstall_plugin( $request );
			case 'update':
				return $this->update_plugin( $request );
			case 'upgrade':
				return $this->upgrade_plugin( $request );
		}

		return $this->get_error_response( 'unknown_action', __( 'Unknown action.', 'wpmudev' ) );
	}

	/**
	 * Activate plugin.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	private function activate_plugin( WP_REST_Request $request ) {
		$pid = (int) $request->get_param( 'pid' );

		$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
		if ( empty( $local ) ) {
			return $this->get_error_response( 'not_installed', __( 'Plugin not installed.', 'wpmudev' ) );
		}

		// Only plugins.
		if ( 'plugin' !== $local['type'] ) {
			return $this->get_error_response( 'invalid_plugin', __( 'Invalid plugin.', 'wpmudev' ) );
		}

		// Make sure functions exist.
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		/** This filter is documented in includes/class-wpmudev-dashboard-upgrader.php */
		$is_network = (bool) apply_filters( 'wpmudev_dashboard_plugin_network_activation', is_multisite(), $local['filename'] );
		// Core trusts the network-wide flag, so authorize that scope explicitly.
		if ( $is_network && ! current_user_can( 'manage_network_plugins' ) ) {
			return $this->get_error_response( 'rest_forbidden', __( 'Sorry, you are not allowed to manage network plugins.', 'wpmudev' ) );
		}

		$activated = activate_plugin( $local['filename'], '', $is_network );
		if ( is_wp_error( $activated ) ) {
			return $this->get_error_response( $activated->get_error_code(), $activated->get_error_message(), $activated->get_error_data() );
		}

		// Clear cache.
		WPMUDEV_Dashboard::$site->clear_local_file_cache();

		// Success response, activate_plugin: Null on success, WP_Error on invalid file.
		return $this->get_response( array( 'pid' => $pid ), is_null( $activated ) );
	}

	/**
	 * Deactivate plugin.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	private function deactivate_plugin( WP_REST_Request $request ) {
		$pid = (int) $request->get_param( 'pid' );

		// No self deactivation please.
		if ( 119 === $pid ) {
			return $this->get_error_response( 'invalid_plugin', __( 'Can not deactivate WPMU DEV Dashboard.', 'wpmudev' ) );
		}

		$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
		if ( empty( $local ) ) {
			return $this->get_error_response( 'not_installed', __( 'Plugin not installed.', 'wpmudev' ) );
		}

		if ( 'plugin' !== $local['type'] ) {
			return $this->get_error_response( 'invalid_plugin', __( 'Invalid plugin.', 'wpmudev' ) );
		}

		// Make sure functions exist.
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		/** This filter is documented in includes/class-wpmudev-dashboard-upgrader.php */
		$is_network = (bool) apply_filters( 'wpmudev_dashboard_plugin_network_activation', is_multisite(), $local['filename'] );
		// Core trusts the network-wide flag, so authorize that scope explicitly.
		if ( $is_network && ! current_user_can( 'manage_network_plugins' ) ) {
			return $this->get_error_response( 'rest_forbidden', __( 'Sorry, you are not allowed to manage network plugins.', 'wpmudev' ) );
		}

		deactivate_plugins( $local['filename'], '', $is_network ); // no return value, can't identify failure.

		WPMUDEV_Dashboard::$site->clear_local_file_cache();

		return $this->get_response( array( 'pid' => $pid ) );
	}

	/**
	 * Install plugins.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	private function install_plugin( WP_REST_Request $request ) {
		$pid = (int) $request->get_param( 'pid' );

		$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
		if ( ! empty( $local ) ) {
			return $this->get_error_response( 'already_installed', __( 'Plugin already installed.', 'wpmudev' ) );
		}

		// Attempt to install.
		$success = WPMUDEV_Dashboard::$site->maybe_replace_free_with_pro( $pid, false, $err );
		if ( ! $success ) {
			return $this->get_upgrader_error_response( $pid, new WP_Error( 'install_failed', 'Plugin installation failed.' ), $err );
		}

		return $this->get_response( array( 'pid' => $pid ) );
	}

	/**
	 * Install and activate plugin.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	private function install_activate_plugin( WP_REST_Request $request ) {
		$pid = (int) $request->get_param( 'pid' );
		if ( is_multisite() && ! current_user_can( 'manage_network_plugins' ) ) {
			return $this->get_error_response( 'rest_forbidden', __( 'Sorry, you are not allowed to manage network plugins.', 'wpmudev' ) );
		}

		// Check if project is already installed.
		$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
		if ( empty( $local ) ) {
			// Install if not installed.
			$installed = WPMUDEV_Dashboard::$site->maybe_replace_free_with_pro( $pid, false, $err );
			if ( ! $installed ) {
				return $this->get_upgrader_error_response( $pid, new WP_Error( 'install_failed', 'Could not install plugin.' ), $err );
			}
			// Get project data.
			$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
		}

		// Can not continue.
		if ( empty( $local['filename'] ) ) {
			return $this->get_error_response( 'install_failed', __( 'Could not install plugin.', 'wpmudev' ) );
		}

		/** This filter is documented in includes/class-wpmudev-dashboard-upgrader.php */
		$is_network = (bool) apply_filters( 'wpmudev_dashboard_plugin_network_activation', is_multisite(), $local['filename'] );
		// Core trusts the network-wide flag, so authorize that scope explicitly.
		if ( $is_network && ! current_user_can( 'manage_network_plugins' ) ) {
			return $this->get_error_response( 'rest_forbidden', __( 'Sorry, you are not allowed to manage network plugins.', 'wpmudev' ) );
		}

		// Activate the plugin.
		$activated = activate_plugin( $local['filename'], '', $is_network );
		// Clear cache.
		WPMUDEV_Dashboard::$site->clear_local_file_cache();

		// Success response, activate_plugin: Null on success, WP_Error on invalid file.
		return $this->get_response( array( 'pid' => $pid ), is_null( $activated ) );
	}

	/**
	 * Uninstall plugin.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	private function uninstall_plugin( WP_REST_Request $request ) {
		$pid = (int) $request->get_param( 'pid' );

		$deleted = WPMUDEV_Dashboard::$upgrader->delete_plugin( $pid );
		if ( ! $deleted ) {
			$error = WPMUDEV_Dashboard::$upgrader->get_error();

			return $this->get_upgrader_error_response( $pid, new WP_Error( 'uninstall_failed', 'Plugin uninstall failed.' ), $error );
		}
		WPMUDEV_Dashboard::$site->clear_local_file_cache();

		return $this->get_response( array( 'pid' => $pid ) );
	}

	/**
	 * Update plugin.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	private function update_plugin( WP_REST_Request $request ) {
		$pid = (int) $request->get_param( 'pid' );

		$success = WPMUDEV_Dashboard::$upgrader->upgrade( $pid );

		if ( ! $success ) {
			$error = WPMUDEV_Dashboard::$upgrader->get_error();

			return $this->get_upgrader_error_response( $pid, new WP_Error( 'update_failed', 'Plugin update failed.' ), $error );
		}

		WPMUDEV_Dashboard::$site->clear_local_file_cache();

		return $this->get_response( array( 'pid' => $pid ) );
	}

	/**
	 * Upgrade a plugin from free to Pro.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	private function upgrade_plugin( WP_REST_Request $request ) {
		$pid = (int) $request->get_param( 'pid' );

		if ( empty( $pid ) ) {
			return $this->get_error_response( 'invalid_plugin', __( 'Invalid plugin ID.', 'wpmudev' ) );
		}

		$success = WPMUDEV_Dashboard::$site->maybe_replace_free_with_pro( $pid, false, $err );
		if ( ! $success ) {
			return $this->get_upgrader_error_response( $pid, new WP_Error( 'upgrade_failed', 'Could not upgrade plugin.' ), $err );
		}

		return $this->get_response( array( 'pid' => $pid ) );
	}

	/**
	 * Normalize Upgrader error into REST response.
	 *
	 * @since 5.0.0
	 *
	 * @param int        $pid              Current Project ID that being processed.
	 * @param WP_Error   $default_wp_error Fallback WP Error, will be used if Upgrader Error could be identified or parsed.
	 * @param array|null $error_data       Error data from WPMUDEV_Dashboard_Upgrader.
	 *
	 * @return WP_REST_Response
	 * @see   \WPMUDEV_Dashboard_Upgrader::set_error()
	 */
	private function get_upgrader_error_response( int $pid, WP_Error $default_wp_error, ?array $error_data ): WP_REST_Response {
		$error_data = is_array( $error_data ) ? $error_data : array();
		$error_data = wp_parse_args(
			$error_data,
			array(
				'pid'     => $pid,
				'code'    => $default_wp_error->get_error_code(),
				'message' => $default_wp_error->get_error_message(),
			)
		);

		return $this->get_error_response(
			$error_data['code'],
			$error_data['message'],
			array(
				'error_code' => $error_data['code'],
				'pid'        => $error_data['pid'],
			)
		);
	}
}
