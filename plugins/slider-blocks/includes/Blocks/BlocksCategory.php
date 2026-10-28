<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the custom block category for GutSlider blocks.
 *
 * Adds a "GutSlider Blocks" category to the block editor so that
 * all slider blocks are grouped together in the inserter.
 *
 * @package GutSlider\Blocks
 * @since   3.0.0
 */
final class BlocksCategory {

	/**
	 * Constructor.
	 *
	 * Registers the block_categories_all filter hook.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
	}

	/**
	 * Register the GutSlider block category.
	 *
	 * Prepends the slider-blocks category to the list of available
	 * block categories in the editor.
	 *
	 * @since 3.0.0
	 *
	 * @param array<int, array<string, mixed>> $categories Existing block categories.
	 * @param \WP_Block_Editor_Context         $context    The block editor context.
	 * @return array<int, array<string, mixed>> Modified block categories.
	 */
	// public function register_block_category( array $categories, \WP_Block_Editor_Context $context ): array {
	// 	$new_category = array(
	// 		'slug'  => 'slider-blocks',
	// 		'title' => __( 'GutSlider Blocks', 'slider-blocks' ),
	// 	);

	// 	return array_merge( array( $new_category ), $categories );
	// }

	public function register_block_category( $categories, $post ) {
	
		array_unshift( $categories, array(
			'slug'	=> 'slider-blocks',
			'title' => __( 'GutSlider Blocks', 'slider-blocks' ),
		) );

		return $categories;
	}
}
