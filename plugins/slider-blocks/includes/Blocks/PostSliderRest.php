<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets the Blog Post block preview in the editor match the published page.
 *
 * The page queries posts with WP_Query (render.php); the editor previews them
 * through the REST API, which can't order randomly or by comment count, filter
 * to posts with a featured image, or report a post's comment count. This adds
 * exactly those:
 *
 * - `orderby=rand` and `orderby=comment_count`;
 * - `gutslider_has_image=1`: only posts with a featured image;
 * - a read-only `gutslider_comments` field: the approved comment count.
 *
 * Applied to every public post type in the REST API (the block can show any of them).
 *
 * @package GutSlider\Blocks
 * @since   3.1.1
 */
final class PostSliderRest {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Before core registers its routes (priority 99), so the extra params are accepted.
		add_action( 'rest_api_init', array( $this, 'extend_post_types' ), 5 );
	}

	/**
	 * Public post types with a REST endpoint.
	 *
	 * @return string[]
	 */
	private function post_types() {
		$types = array();
		foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $type ) {
			if ( 'attachment' !== $type->name && is_post_type_viewable( $type ) ) {
				$types[] = $type->name;
			}
		}
		return $types;
	}

	/**
	 * Hook every public post type.
	 */
	public function extend_post_types() {
		foreach ( $this->post_types() as $type ) {
			add_filter( "rest_{$type}_collection_params", array( $this, 'collection_params' ) );
			add_filter( "rest_{$type}_query", array( $this, 'query_args' ), 10, 2 );
		}
		$this->register_fields();
	}

	/**
	 * Accept the extra order and filter parameters.
	 *
	 * @param array $params Collection parameters.
	 * @return array
	 */
	public function collection_params( $params ) {
		if ( isset( $params['orderby']['enum'] ) && is_array( $params['orderby']['enum'] ) ) {
			$params['orderby']['enum'] = array_values( array_unique( array_merge( $params['orderby']['enum'], array( 'rand', 'comment_count' ) ) ) );
		}

		$params['gutslider_has_image'] = array(
			'description' => __( 'Limit results to posts with a featured image.', 'slider-blocks' ),
			'type'        => 'boolean',
		);

		return $params;
	}

	/**
	 * Map the extra parameters onto WP_Query.
	 *
	 * @param array            $args    WP_Query arguments.
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	public function query_args( $args, $request ) {
		$orderby = $request->get_param( 'orderby' );
		if ( in_array( $orderby, array( 'rand', 'comment_count' ), true ) ) {
			$args['orderby'] = $orderby;
		}

		if ( $request->get_param( 'gutslider_has_image' ) ) {
			$meta_query        = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
			$meta_query[]      = array(
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS',
			);
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		return $args;
	}

	/**
	 * Comment count on post records (the editor shows it in the meta line).
	 */
	public function register_fields() {
		register_rest_field(
			$this->post_types(),
			'gutslider_comments',
			array(
				'get_callback' => static function ( $post ) {
					return (int) get_comments_number( $post['id'] );
				},
				'schema'       => array(
					'description' => __( 'Approved comment count.', 'slider-blocks' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}
}
