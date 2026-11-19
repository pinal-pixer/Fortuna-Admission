<?php
/**
 * Render the Backdrop block.
 *
 * The work (backdrop, query, slider markup) lives in \GutSlider\Blocks\BackdropSlider.
 *
 * @param array    $attributes The block attributes.
 * @param string   $content    The rendered slides.
 * @param WP_Block $block      The block instance.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \GutSlider\Blocks\BackdropSlider::render( $attributes, $content, $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
