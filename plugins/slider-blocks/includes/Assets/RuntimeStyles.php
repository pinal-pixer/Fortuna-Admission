<?php
declare( strict_types=1 );

namespace GutSlider\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The runtime's CSS (assets/css/runtime.css), for the Playback panel's controls, full-screen
 * height and easing, is added to the page only when a slider uses one of them. Every page with a
 * slider gets the (few bytes of) focus styles for its controls.
 *
 * Both are queued as inline styles while the slider renders. WordPress prints each once per page,
 * and in the head in block themes (their templates render before it), so nothing jumps once
 * the page has loaded. Queueing, rather than printing with the first slider, also holds when the
 * content is rendered more than once in a request (an SEO plugin building a description, a theme
 * building an excerpt): a flag set by that first render would leave the real page without them.
 *
 * @package GutSlider\Assets
 * @since   3.5.0
 */
final class RuntimeStyles {

	/**
	 * Classes, set by the Playback panel, that need the stylesheet.
	 *
	 * @var string
	 */
	private const PATTERN = '/(?:^|\s)gs-(?:play-pause|counter|progress|scrollbar|fullscreen|ease-[a-z]+)(?:\s|$)/';

	/**
	 * A focus ring for the controls of every slider: white with a dark edge, so it shows on light
	 * and dark slides alike (browsers draw a thin one in the arrow's own colour).
	 *
	 * @var string
	 */
	private const FOCUS_CSS = '[class*="wp-block-gutsliders-"] :is(.swiper-button-next,.swiper-button-prev,.gutslider-next,.gutslider-prev,.swiper-pagination-bullet,.gs-runtime-play,.gs-video-facade,.gutslider-thumbs .swiper-slide):focus-visible{outline:2px solid #fff;outline-offset:2px;box-shadow:0 0 0 4px rgba(0,0,0,.65)}';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'render_block', array( $this, 'render' ), 10, 2 );
	}

	/**
	 * Queues the styles a slider needs.
	 *
	 * @param string $content Block HTML.
	 * @param array  $block   Parsed block.
	 * @return string Block HTML, unchanged.
	 */
	public function render( $content, $block ) {
		$name = $block['blockName'] ?? '';
		if ( is_admin() || ! is_string( $name ) || 0 !== strpos( $name, 'gutsliders/' ) || in_array( $name, \GutSlider\Blocks\SliderMarkup::CHILD_BLOCKS, true ) ) {
			return $content;
		}

		self::queue( 'gutslider-focus', self::FOCUS_CSS );

		$class = $block['attrs']['className'] ?? '';
		if ( is_string( $class ) && preg_match( self::PATTERN, $class ) && ! wp_style_is( 'gutslider-runtime', 'enqueued' ) ) {
			$css = file_get_contents( GUTSLIDER_DIR_PATH . 'assets/css/runtime.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file
			if ( $css ) {
				// strip the comment and line breaks; the file is plain CSS we ship
				self::queue( 'gutslider-runtime', trim( preg_replace( array( '#/\*.*?\*/#s', '/\s*\n\s*/' ), array( '', '' ), $css ) ) );
			}
		}

		return $content;
	}

	/**
	 * Queues a block of CSS under its own handle, once.
	 *
	 * @param string $handle Style handle.
	 * @param string $css    CSS.
	 * @return void
	 */
	public static function queue( string $handle, string $css ): void {
		if ( wp_style_is( $handle, 'enqueued' ) ) {
			return;
		}
		if ( ! wp_style_is( $handle, 'registered' ) ) {
			wp_register_style( $handle, false, array(), GUTSLIDER_VERSION );
		}
		wp_add_inline_style( $handle, $css );
		wp_enqueue_style( $handle );
	}
}
