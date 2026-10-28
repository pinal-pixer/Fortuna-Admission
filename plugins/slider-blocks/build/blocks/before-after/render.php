<?php
/**
 * Before After: the page output (see \GutSlider\Blocks\BeforeAfter).
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Saved block markup.
 *
 * @package GutSlider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \GutSlider\Blocks\BeforeAfter::render( $attributes, $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
