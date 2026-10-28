<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static slider (gutsliders/content-slider): motion effects on the page.
 *
 * The block saves static markup and many are published, so the newer motion settings
 * (content reveal, pointer effect, magnetic buttons) are added to the saved HTML as it is
 * printed: classes on the block wrapper, which frontend.js and style.scss act on.
 *
 * @package GutSlider\Blocks
 * @since   3.2.0
 */
final class StaticSlider {

	/** Values the motion settings may take. */
	const REVEALS  = array( 'words', 'letters', 'blur', 'mask' );
	const POINTERS = array( 'depth', 'tilt', 'spotlight' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'render_block_gutsliders/content-slider', array( $this, 'render' ), 20, 2 );
	}

	/**
	 * Add the motion classes to the slider wrapper.
	 *
	 * @param string $content Block HTML.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public function render( $content, $block ) {
		$attrs   = $block['attrs'] ?? array();
		$reveal  = in_array( $attrs['textReveal'] ?? '', self::REVEALS, true ) ? $attrs['textReveal'] : '';
		$pointer = in_array( $attrs['pointerEffect'] ?? '', self::POINTERS, true ) ? $attrs['pointerEffect'] : '';
		$magnet  = ! empty( $attrs['magneticButtons'] );

		if ( ! $reveal && ! $pointer && ! $magnet ) {
			return $content;
		}

		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( ! $tags->next_tag( array( 'class_name' => 'wp-block-gutsliders-content-slider' ) ) ) {
			return $content;
		}

		// Read by motion.js and style.scss.
		if ( $reveal ) {
			$tags->add_class( 'gs-reveal' );
			$tags->add_class( 'gs-reveal-' . $reveal );
			if ( in_array( $attrs['revealSpeed'] ?? '', array( 'slow', 'fast' ), true ) ) {
				$tags->add_class( 'gs-reveal-' . $attrs['revealSpeed'] );
			}
		}
		if ( $pointer ) {
			$tags->add_class( 'gs-pointer-' . $pointer );
		}
		if ( $magnet ) {
			$tags->add_class( 'gs-magnetic' );
		}

		return $tags->get_updated_html();
	}
}
