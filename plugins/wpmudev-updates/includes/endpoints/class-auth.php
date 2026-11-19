<?php
/**
 * Auth functionality REST endpoint.
 *
 * @link    http://wpmudev.com
 * @since   4.12.0
 * @author  Joel James <joel@incsub.com>
 * @package WPMUDEV\Dashboard\Endpoints
 */

namespace WPMUDEV\Dashboard\Endpoints;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPMUDEV_Dashboard;

/**
 * Class Auth
 */
class Auth extends Endpoint {

	/**
	 * API endpoint for the current endpoint.
	 *
	 * @since 5.0.0
	 * @var string $endpoint
	 */
	private string $endpoint = '/auth';

	/**
	 * Register the routes for handling auth functionality.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function register_routes() {
		// Logout endpoint.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/logout',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'logout' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		// Updates checking endpoint.
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/check-updates',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'check_updates' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		// Hub sync endpoint.
		// Admin only until connected, full Dashboard check after - see permissions_check_hub_sync().
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/hub-sync',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'hub_sync' ),
					'permission_callback' => array( $this, 'permissions_check_hub_sync' ),
					'args'                => array(
						'api_key'        => array(
							'required'          => true,
							'description'       => __( 'WPMU DEV API key.', 'wpmudev' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'auth_nonce'     => array(
							'required'          => true,
							'description'       => __( 'Sync auth nonce.', 'wpmudev' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'is_sso_enabled' => array(
							'required'    => false,
							'description' => __( 'Whether to enable SSO.', 'wpmudev' ),
							'type'        => 'boolean',
							'default'     => true,
						),
					),
				),
			)
		);

		// Get available teams.
		// POST, not GET: the user API key is a secret and must not end up in query strings,
		// Pre-connect only - see permissions_check_teams().
		register_rest_route(
			$this->get_namespace(),
			$this->endpoint . '/teams',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_user_teams_callback' ),
					'permission_callback' => array( $this, 'permissions_check_teams' ),
					'args'                => array(
						'user_api_key' => array(
							'required'          => true,
							'description'       => __( 'WPMU DEV User API key.', 'wpmudev' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'auth_nonce'   => array(
							'required'          => true,
							'description'       => __( 'Sync auth nonce.', 'wpmudev' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Permission check for the hub-sync route.
	 *
	 * Admin only for the initial connect, since there is no allowed-users list yet. Once the site
	 * holds a key this route can re-key it and touch the allowed users, so it needs the full
	 * Dashboard gate - otherwise an admin deliberately kept out of the Dashboard could take over
	 * the Hub connection. An empty list still resolves to any capable admin in
	 * get_allowed_users(), so a site can't lock itself out of re-syncing.
	 *
	 * @since 5.0.3
	 *
	 * @return bool
	 */
	public function permissions_check_hub_sync(): bool {
		// First time connect: no key yet, so there is nothing to hijack.
		if ( ! WPMUDEV_Dashboard::$api->has_key() ) {
			return $this->permissions_check_admin_only();
		}

		return $this->permissions_check();
	}

	/**
	 * Permission check for the teams route.
	 *
	 * This route exists only for the pre-connect team selection on the login screen.
	 *
	 * @since 5.0.3
	 *
	 * @return bool
	 */
	public function permissions_check_teams(): bool {
		// Connected site: route is dead, nothing legitimate calls it.
		if ( WPMUDEV_Dashboard::$api->has_key() ) {
			return false;
		}

		return $this->permissions_check_admin_only();
	}

	/**
	 * Retrieve available teams for a user.
	 *
	 * This endpoint fetches the user's teams using the provided API key.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object containing the user API key.
	 *
	 * @return WP_REST_Response
	 */
	public function get_user_teams_callback( $request ) {
		// Retrieve the user_api_key from the request parameters.
		$api_key    = $request->get_param( 'user_api_key' );
		$auth_nonce = $request->get_param( 'auth_nonce' );

		$auth_verify_nonce = wp_verify_nonce( $auth_nonce, 'auth_nonce' );
		if ( ! $auth_verify_nonce ) {
			return $this->get_error_response(
				'invalid_nonce',
				__( 'Invalid Permissions.', 'wpmudev' ),
				array(
					'redirect' => add_query_arg(
						array( 'invalid_nonce' => '1' ),
						WPMUDEV_Dashboard::$ui->page_urls->dashboard_url
					),
				)
			);
		}

		// Call the get_user_teams method with the provided API key.
		$response = WPMUDEV_Dashboard::$api->get_user_teams( $api_key );

		// Return the response from the API method.
		return $this->get_response( $response );
	}

	/**
	 * Check for updates with Hub.
	 *
	 * This will do a full sync with Hub.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function check_updates( WP_REST_Request $request ) {
		WPMUDEV_Dashboard::$settings->set( 'refresh_profile', true, 'flags' );
		WPMUDEV_Dashboard::$api->refresh_projects_data();
		WPMUDEV_Dashboard::$site->refresh_local_projects( 'remote', true );

		return $this->get_response(
			array(
				'is_logged_in' => WPMUDEV_Dashboard::$api->has_key(),
			)
		);
	}

	/**
	 * Perform sync with Hub.
	 *
	 * This endpoint should be used for authentication.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function hub_sync( WP_REST_Request $request ) {
		$api_key        = $request->get_param( 'api_key' );
		$is_sso_enabled = $request->get_param( 'is_sso_enabled' );
		$auth_nonce     = $request->get_param( 'auth_nonce' );

		$auth_verify_nonce = wp_verify_nonce( $auth_nonce, 'auth_nonce' );

		if ( ! $auth_verify_nonce ) {
			return $this->get_error_response(
				'invalid_nonce',
				__( 'Invalid Permissions.', 'wpmudev' ),
				array(
					'redirect' => add_query_arg(
						array( 'invalid_nonce' => '1' ),
						WPMUDEV_Dashboard::$ui->page_urls->dashboard_url
					),
				)
			);
		}

		/*
		 * Refuse to re-point a connected site at a different account. No UI can do it - the login
		 * screen only renders while has_key() is false, and the Lambda auto-install runner uses
		 * the admin-ajax autologin instead - so switching accounts always goes through Disconnect,
		 * which also resets the allowed users. The same key is still accepted so a duplicate
		 * submit re-syncs instead of erroring out.
		 */
		if (
			! empty( $api_key )
			&& WPMUDEV_Dashboard::$api->has_key()
			&& ! hash_equals( (string) WPMUDEV_Dashboard::$api->get_key(), (string) $api_key )
		) {
			return $this->get_error_response(
				'already_connected',
				__( 'This site is already connected to a WPMU DEV account. Disconnect it first to connect a different account.', 'wpmudev' )
			);
		}

		// not the place to logout ( clearing api key ).
		if ( empty( $api_key ) ) {
			return $this->get_error_response(
				'invalid_key',
				__( 'Could not sync with Hub. Please check the API key.', 'wpmudev' )
			);
		}

		// Set API key.
		WPMUDEV_Dashboard::$api->set_key( $api_key );

		// always get projects on first login / sync.
		WPMUDEV_Dashboard::$api->refresh_projects_data();

		WPMUDEV_Dashboard::$settings->set( 'enabled', $is_sso_enabled, 'sso' );
		if ( $is_sso_enabled ) {
			WPMUDEV_Dashboard::$settings->set( 'userid', get_current_user_id(), 'sso' );
		}

		// Sync with Hub. Ignore transient, due to first authentication.
		$result = WPMUDEV_Dashboard::$api->hub_sync( false, true, false );

		// Handle error.
		if ( ! $result || empty( $result['membership'] ) ) {
			// Clear API key.
			WPMUDEV_Dashboard::$api->set_key( '' );

			if ( false === $result || ! is_array( $result ) ) {
				return $this->get_error_response(
					'connection_error',
					sprintf(
					/* translators: %s: Error message. */
						__(
							'Your server had a problem connecting to WPMU DEV: "%s">. Please try again.',
							'wpmudev'
						),
						esc_html( WPMUDEV_Dashboard::$api->api_error ),
					)
					. '<br>' // new line.
					. sprintf(
					/* translators: %s: API URL. */
						__(
							'If this problem continues, please contact your host with this error message and ask: "Is php on my server properly configured to be able to contact %s with a POST HTTP request via fsockopen or CURL?"',
							'wpmudev'
						),
						esc_html( WPMUDEV_Dashboard::$api->rest_url( '' ) )
					)
				);
			}

			if ( ! empty( $result['limit_exceeded_no_hosting_sites'] ) ) {
				$limit_data = $result['limit_data'] ?? array();
				$limit_data = is_array( $limit_data ) ? $limit_data : array();
				// null-safety.
				$limit_data = wp_parse_args(
					$limit_data,
					array(
						'site_limit'   => 1,
						// we did have DEV hosting_limit in the past for some membership. And we calculate "available hosting sites" slot that can use, display it in this notice.
						// the option / settings / data still there, but realistically this will have 0 value ( no DEV Hosting Limit / Unlimited ).
						// therefore calculating + displaying "available hosting sites" is somewhat obsolete if not leads to confusions.
						// TODO: we should instead have another line / note in this notice to recommend them migrate to our DEV Hosting ( little bit too aggressive though, but seems that is where we are going ).
						'hosted_limit' => 0,
						'total_hosted' => 0,
					)
				);

				return $this->get_error_response(
					'site_limit_exceeded',
					sprintf(
					/* translators: %d: Site limit. */
						__(
							'You have reached your plan’s limit of <strong>%d site(s), not hosted with us, connected to The Hub</strong>.',
							'wpmudev'
						),
						$limit_data['site_limit'],
					)
					. ' ' // space.
					. sprintf(
					/* translators: %1$s: Hub account URL, %2$s: Hub URL. */
						__(
							'<a href="%1$s" target="_blank" rel="noopener noreferrer">Upgrade your membership</a> or <a href="%2$s" target="_blank" rel="noopener noreferrer">remove a site</a> before adding another.',
							'wpmudev'
						),
						esc_url( WPMUDEV_Dashboard::$ui->page_urls->hub_account_url ),
						esc_url( WPMUDEV_Dashboard::$ui->page_urls->hub_url ),
					)
					. '<br/>' // new line.
					. sprintf(
					/* translators: %s: Support URL. */
						__(
							'<a href="%s" target="_blank" rel="noopener noreferrer">Contact Support</a> for assistance.',
							'wpmudev'
						),
						esc_url( WPMUDEV_Dashboard::$ui->page_urls->hub_support_url . '#get-support' )
					),
					$limit_data
				);
			}

			return $this->get_error_response(
				'invalid_key',
				__( 'Could not sync with Hub. Please check the API key.', 'wpmudev' )
			);
		} else {
			// Valid key.
			global $current_user;
			// Connecting resets the list to the connecting admin, as it always has: this only
			// runs from the login screen, i.e. on a disconnected site where logout() already
			// cleared it. Re-keying a connected site is not a UI flow and is refused by
			// permissions_check_hub_sync(), so nothing legitimate loses its Dashboard users here.
			WPMUDEV_Dashboard::$settings->set( 'limit_to_user', array( $current_user->ID ), 'general' );
			WPMUDEV_Dashboard::$api->refresh_profile();

			/***
			 * Action hook that run after login with WPMUDEV account is successful.
			 *
			 * @since 4.11.2
			 *
			 * @param int $user_id Current user ID.
			 */
			do_action( 'wpmudev_dashboard_after_login_success', $current_user->ID );
		}

		WPMUDEV_Dashboard::$ui->page_urls->reload();

		return $this->get_response(
			array(
				'hub_site_url'           => sprintf(
					'%s/site/%d/overview/quick-setup',
					WPMUDEV_Dashboard::$ui->page_urls->hub_url,
					WPMUDEV_Dashboard::$api->get_site_id()
				),
				'urls'                   => WPMUDEV_Dashboard::$ui->page_urls,
				'free_plugins_installed' => $this->get_installed_free_plugins(),
				'features'               => WPMUDEV_Dashboard::$ui->get_features(),
			)
		);
	}

	/**
	 * Logout from Hub and disconnect site.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function logout( WP_REST_Request $request ) {
		// Logout.
		WPMUDEV_Dashboard::$site->logout( false );

		return $this->get_response();
	}

	/**
	 * Get list of free plugins installed.
	 *
	 * @return array
	 */
	private function get_installed_free_plugins(): array {
		$installed = array();
		// Membership type.
		$type = WPMUDEV_Dashboard::$api->get_membership_status();
		// Free and expired types are not valid.
		if ( in_array( $type, array( 'expired', 'paused', 'free' ), true ) ) {
			return $installed;
		}

		// Get membership details.
		$membership_data = WPMUDEV_Dashboard::$api->get_membership_data();
		$free_projects   = WPMUDEV_Dashboard::$site->get_installed_free_projects();

		if ( 'single' === $type ) {
			foreach ( $free_projects as $free_project ) {
				if ( absint( $free_project['id'] ) === absint( $membership_data['membership'] ) ) {
					$installed[] = $free_project;
				}
			}
		} elseif ( 'full' === $type ) {
			$installed = $free_projects;
		} elseif ( 'unit' === $type ) {
			foreach ( $free_projects as $free_project ) {
				$membership_projects = $membership_data['membership_projects'] ?? array();
				$membership_projects = array_map( 'intval', $membership_projects );
				if ( in_array( (int) $free_project, $membership_projects, true ) ) {
					$installed[] = $free_project;
				}
			}
		}

		// Get only id and name.
		if ( ! empty( $installed ) ) {
			$installed = wp_list_pluck( $installed, 'name', 'id' );
		}

		return $installed;
	}
}
