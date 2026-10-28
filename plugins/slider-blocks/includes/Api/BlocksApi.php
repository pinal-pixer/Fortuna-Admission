<?php
declare( strict_types=1 );

namespace GutSlider\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for managing GutSlider block settings.
 *
 * Provides endpoints for retrieving block definitions and updating
 * individual block enabled/disabled status via the WordPress REST API.
 *
 * @package GutSlider\Api
 * @since   3.0.0
 */
final class BlocksApi {

	/**
	 * Constructor.
	 *
	 * Registers WordPress hooks for REST API routes and default block syncing.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		add_action( 'init', array( $this, 'save_default_blocks' ) );
	}

	/**
	 * Register REST API endpoints.
	 *
	 * Creates separate GET and POST routes for the blocks endpoint
	 * with appropriate permission callbacks and argument schemas.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_endpoints(): void {
		register_rest_route(
			'gutslider/v1',
			'/blocks',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_blocks' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_block_status' ),
					'permission_callback' => array( $this, 'check_write_permission' ),
					'args'                => array(
						'nonce'  => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'name'   => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'names'  => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'active' => array(
							'required' => true,
							'type'     => 'boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Retrieve all block definitions.
	 *
	 * @since 3.0.0
	 *
	 * @return \WP_REST_Response Block definitions array wrapped in a REST response.
	 */
	public function get_blocks(): \WP_REST_Response {
		return rest_ensure_response( get_option( 'gutslider_blocks' ) );
	}

	/**
	 * Update the active status of one or more blocks.
	 *
	 * Accepts a single block name or an array of block names and sets
	 * their active status. Protected by nonce verification.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error Updated blocks or error response.
	 */
	public function update_block_status( \WP_REST_Request $request ) {
		$nonce = $request->get_param( 'nonce' );

		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'gutslider_nonce' ) ) {
			return new \WP_Error(
				'invalid_nonce',
				__( 'Invalid security token.', 'slider-blocks' ),
				array( 'status' => 403 )
			);
		}

		$single_block_name = $request->get_param( 'name' );
		$block_names       = $request->get_param( 'names' );
		$active_status     = (bool) $request->get_param( 'active' );

		if ( ! empty( $single_block_name ) ) {
			$block_names = array( sanitize_text_field( (string) $single_block_name ) );
		} elseif ( is_array( $block_names ) ) {
			$block_names = array_map( 'sanitize_text_field', $block_names );
		} else {
			return new \WP_Error(
				'invalid_request',
				__( 'Invalid block name(s) provided.', 'slider-blocks' ),
				array( 'status' => 400 )
			);
		}

		$blocks = get_option( 'gutslider_blocks' );

		if ( ! is_array( $blocks ) ) {
			return new \WP_Error(
				'no_blocks',
				__( 'No blocks found.', 'slider-blocks' ),
				array( 'status' => 404 )
			);
		}

		foreach ( $blocks as &$block ) {
			if ( in_array( $block['name'], $block_names, true ) ) {
				$block['active'] = $active_status;
			}
		}
		unset( $block );

		update_option( 'gutslider_blocks', $blocks );

		return rest_ensure_response( $blocks );
	}

	/**
	 * Check read permission for the blocks endpoint.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether the current user can read block data.
	 */
	public function check_read_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check write permission for the blocks endpoint.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether the current user can modify block settings.
	 */
	public function check_write_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Synchronize default block definitions with stored options.
	 *
	 * Merges new block definitions with existing stored blocks while
	 * preserving user-set active statuses. Only runs when the plugin
	 * version changes to avoid unnecessary database writes.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function save_default_blocks(): void {
		$saved_version = get_option( 'gutslider_blocks_version', '' );

		if ( $saved_version === GUTSLIDER_VERSION ) {
			return;
		}

		$existing_blocks = get_option( 'gutslider_blocks', array() );

		if ( ! is_array( $existing_blocks ) ) {
			$existing_blocks = array();
		}

		$new_blocks    = $this->get_gutslider_blocks();
		$merged_blocks = array();

		foreach ( $new_blocks as $new_block ) {
			$found = false;

			foreach ( $existing_blocks as $existing_block ) {
				if ( $existing_block['name'] === $new_block['name'] ) {
					$merged_blocks[] = array_merge( $new_block, array( 'active' => $existing_block['active'] ) );
					$found           = true;
					break;
				}
			}

			if ( ! $found ) {
				$merged_blocks[] = $new_block;
			}
		}

		$merged_blocks = array_values( $merged_blocks );

		update_option( 'gutslider_blocks', $merged_blocks );
		update_option( 'gutslider_blocks_version', GUTSLIDER_VERSION );
	}

	/**
	 * Get the default GutSlider block definitions from the data file.
	 *
	 * @since 3.0.0
	 *
	 * @return array<int, array<string, mixed>> Array of block definition arrays.
	 */
	private function get_gutslider_blocks(): array {
		return require GUTSLIDER_DIR_PATH . 'includes/Api/blocks.php';
	}
}
