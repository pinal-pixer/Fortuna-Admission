<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles registration of all GutSlider Gutenberg blocks.
 *
 * Reads block definitions from the data file, checks whether each
 * block is enabled, and registers or unregisters them accordingly.
 *
 * @package GutSlider\Blocks
 * @since   3.0.0
 */
final class RegisterBlocks {

	/**
	 * Constructor.
	 *
	 * Registers WordPress hooks for block registration.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );

		if ( ! wp_is_block_theme() ) {
			add_filter( 'should_load_separate_core_block_assets', '__return_true' );
		}
	}

	/**
	 * Register all GutSlider blocks.
	 *
	 * Iterates through the block definitions, registers enabled blocks,
	 * and unregisters disabled ones from the block type registry.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$blocks = get_option( 'gutslider_blocks', $this->get_gutslider_blocks() );

		if ( empty( $blocks ) || ! is_array( $blocks ) ) {
			return;
		}

		foreach ( $blocks as $block ) {
			$path = GUTSLIDER_DIR_PATH;

			if ( isset( $block['is_pro'] ) && true === $block['is_pro'] && defined( 'GUTSLIDER_PRO_PATH' ) ) {
				$path = GUTSLIDER_PRO_PATH;
			}

			$block_name = trailingslashit( $path ) . 'build/blocks/' . $block['name'];
			$block_key  = $block['name'];
			$registry   = \WP_Block_Type_Registry::get_instance();

			if ( $this->is_block_enabled( $block_key ) ) {
				if ( file_exists( $block_name ) && ! $registry->is_registered( 'gutsliders/' . $block['name'] ) ) {
					register_block_type( $block_name );
				}
			} elseif ( $registry->is_registered( 'gutsliders/' . $block['name'] ) ) {
				unregister_block_type( 'gutsliders/' . $block['name'] );
			}
		}
	}

	/**
	 * Get the default GutSlider block definitions.
	 *
	 * @since 3.0.0
	 *
	 * @return array<int, array<string, mixed>> Array of block definition arrays.
	 */
	public function get_gutslider_blocks(): array {
		return require GUTSLIDER_DIR_PATH . 'includes/Api/blocks.php';
	}

	/**
	 * Check if a specific block is enabled.
	 *
	 * Reads the block's enabled status from a WordPress option.
	 * Blocks are enabled by default.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_name The block name identifier.
	 * @return bool Whether the block is enabled.
	 */
	private function is_block_enabled( string $block_name ): bool {
		$option_key = 'gut_' . str_replace( '-', '_', $block_name );
		return (bool) get_option( $option_key, true );
	}
}
