<?php
declare( strict_types=1 );

namespace GutSlider\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines allowed SVG tags for wp_kses sanitization.
 *
 * Provides the allowed tags and attributes configuration used when
 * rendering SVG icons through WordPress's wp_kses() function.
 *
 * @package GutSlider\Icons
 * @since   3.0.0
 */
final class AllowedSvg {

	/**
	 * Get the allowed SVG tag configuration for wp_kses.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<string, bool>> Allowed tags and their attributes.
	 */
	public static function tags(): array {
		return array(
			'svg'   => array(
				'class'           => true,
				'aria-hidden'     => true,
				'aria-labelledby' => true,
				'role'            => true,
				'xmlns'           => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true,
				'fill'            => true,
			),
			'g'     => array( 'fill' => true ),
			'title' => array( 'title' => true ),
			'path'  => array(
				'd'         => true,
				'fill'      => true,
				'fill-rule' => true,
			),
		);
	}
}
