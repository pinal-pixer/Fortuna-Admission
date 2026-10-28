<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marquee block (gutsliders/marquee + gutsliders/marquee-item): page output.
 *
 * - The items are printed as they were rendered (they are already safe); running
 *   them through wp_kses_post() removed SVG icons, embeds and forms.
 * - The repeated copy is hidden from screen readers and keyboards (`inert`) and
 *   carries no duplicate ids; frontend.js adds copies until the strip fills the screen.
 * - Speed can be set in pixels per second (the same on every screen); blocks saved
 *   before keep their "one loop every N seconds".
 * - A linked item used to wrap its content in `<a>`, so buttons inside made invalid
 *   nested links; it becomes a `<div>` with a stretched link behind the content.
 * - Item background images become real `<img>` elements (sizes, lazy loading).
 * - Rows (every other one reversed), separators between items, designs, fade edges,
 *   item width / alignment, and the motion options read by engine.js.
 *
 * @package GutSlider\Blocks
 * @since   3.1.1
 */
final class Marquee {

	const DIRECTIONS = array( 'left', 'right', 'up', 'down' );
	const UNITS      = array( 'px', 'em', 'rem', 'vh', 'vw', '%' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'render_block_gutsliders/marquee-item', array( $this, 'item' ), 10, 2 );
	}

	/** Designs, separators and item alignments offered. */
	const DESIGNS    = array( 'logos', 'ticker', 'testimonials', 'gallery', 'tags', 'announcement' );
	const SEPARATORS = array(
		'dot'     => '•',
		'star'    => '✦',
		'slash'   => '/',
		'diamond' => '◆',
		'dash'    => '—',
	);
	const ALIGNS     = array( 'start', 'center', 'end', 'stretch' );

	/**
	 * The marquee.
	 *
	 * @param array          $attributes Block attributes.
	 * @param string         $content    Rendered items (all of them).
	 * @param \WP_Block|null $block      Block, to render the items one by one (rows, separators).
	 * @return string HTML.
	 */
	public static function render( $attributes, $content, $block = null ) {
		$unique_id   = isset( $attributes['uniqueId'] ) ? sanitize_html_class( (string) $attributes['uniqueId'] ) : '';
		$duration    = isset( $attributes['speed'] ) ? max( 1, (int) $attributes['speed'] ) : 30;
		$speed_px    = isset( $attributes['speedPx'] ) ? max( 0, (float) $attributes['speedPx'] ) : 0;
		$direction   = isset( $attributes['direction'] ) && in_array( $attributes['direction'], self::DIRECTIONS, true ) ? $attributes['direction'] : 'left';
		$vertical    = 'vertical' === ( $attributes['orientation'] ?? 'horizontal' );
		$orientation = $vertical ? 'vertical' : 'horizontal';
		$pause_btn   = ! isset( $attributes['pauseButton'] ) || ! empty( $attributes['pauseButton'] );
		$rows        = isset( $attributes['rows'] ) ? max( 1, min( 3, (int) $attributes['rows'] ) ) : 1;
		$design      = isset( $attributes['design'] ) && in_array( $attributes['design'], self::DESIGNS, true ) ? $attributes['design'] : '';
		$separator   = isset( $attributes['separator'] ) && isset( self::SEPARATORS[ $attributes['separator'] ] ) ? $attributes['separator'] : '';
		$item_align  = isset( $attributes['itemAlign'] ) && in_array( $attributes['itemAlign'], self::ALIGNS, true ) ? $attributes['itemAlign'] : '';
		$item_width  = isset( $attributes['itemWidth'] ) ? max( 0, (int) $attributes['itemWidth'] ) : 0;
		$fade        = ! empty( $attributes['fadeEdges'] );
		$fade_size   = isset( $attributes['fadeSize'] ) ? max( 0, min( 400, (int) $attributes['fadeSize'] ) ) : 80;

		// Hover: pause (also the older "Pause on Hover") or slow down.
		$hover = isset( $attributes['hoverEffect'] ) && in_array( $attributes['hoverEffect'], array( 'pause', 'slow' ), true ) ? $attributes['hoverEffect'] : '';
		if ( ! isset( $attributes['hoverEffect'] ) && ! empty( $attributes['pauseOnHover'] ) ) {
			$hover = 'pause';
		}

		// Responsive gap and height as custom properties (style.scss reads them).
		$vars = array();
		foreach ( array( 'desk', 'tab', 'mob' ) as $bp ) {
			$gap = self::range( $attributes, 'columnsGap', $bp );
			if ( $gap ) {
				$vars[] = '--marquee-gap-' . $bp . ':' . $gap;
			}
			if ( $vertical ) {
				$height = self::range( $attributes, 'slideHeight', $bp );
				if ( $height ) {
					$vars[] = '--marquee-height-' . $bp . ':' . $height;
				}
			}
		}
		if ( $item_width ) {
			$vars[] = '--marquee-item-width:' . $item_width . 'px';
		}
		if ( $fade ) {
			$vars[] = '--marquee-fade:' . $fade_size . 'px';
		}

		$classes = array( 'marquee-wrapper', $unique_id, 'marquee-' . $orientation );
		if ( $design ) {
			$classes[] = 'gs-mq-design-' . $design;
		}
		if ( $rows > 1 ) {
			$classes[] = 'has-rows';
		}
		if ( $fade ) {
			$classes[] = 'has-fade';
		}
		if ( $item_width ) {
			$classes[] = 'has-item-width';
		}
		if ( $item_align ) {
			$classes[] = 'gs-mq-align-' . $item_align;
		}

		$wrapper = array( 'class' => implode( ' ', array_filter( $classes ) ) );
		if ( $vars ) {
			$wrapper['style'] = implode( ';', $vars );
		}

		$data = sprintf( ' data-marquee="%s"', esc_attr( $orientation ) );
		if ( $speed_px > 0 ) {
			$data .= sprintf( ' data-speed-px="%s"', esc_attr( (string) $speed_px ) );
		}
		if ( $hover ) {
			$data .= sprintf( ' data-hover="%s"', esc_attr( $hover ) );
		}
		if ( ! empty( $attributes['dragToScrub'] ) ) {
			$data .= ' data-drag="1"';
		}
		if ( 'linked' === ( $attributes['scrollMode'] ?? '' ) ) {
			$data .= ' data-scroll="linked"';
		}
		if ( ! empty( $attributes['reverseOnScroll'] ) ) {
			$data .= ' data-reverse-scroll="1"';
		}
		if ( ! empty( $attributes['scrollBoost'] ) ) {
			$data .= ' data-scroll-boost="1"';
		}
		if ( $pause_btn ) {
			$data .= sprintf(
				' data-label-pause="%1$s" data-label-play="%2$s"',
				esc_attr__( 'Pause the moving content', 'slider-blocks' ),
				esc_attr__( 'Play the moving content', 'slider-blocks' )
			);
		}

		// The items, one by one when possible (rows and separators need them apart).
		$items = array();
		if ( $block instanceof \WP_Block && ! empty( $block->inner_blocks ) ) {
			foreach ( $block->inner_blocks as $inner ) {
				$items[] = (string) $inner->render();
			}
		} else {
			$items[] = (string) $content;
		}
		$items = array_values( array_filter( $items, static fn( $html ) => '' !== trim( $html ) ) );

		// Rows: the items dealt out in turn when there are enough, else every row shows them all.
		$row_items = array();
		for ( $r = 0; $r < $rows; $r++ ) {
			$row_items[ $r ] = array();
		}
		foreach ( $items as $index => $html ) {
			if ( count( $items ) >= $rows * 2 ) {
				$row_items[ $index % $rows ][] = $html;
			} else {
				for ( $r = 0; $r < $rows; $r++ ) {
					$row_items[ $r ][] = $html;
				}
			}
		}

		$sep = $separator ? sprintf( '<span class="marquee-sep is-%1$s" aria-hidden="true">%2$s</span>', esc_attr( $separator ), esc_html( self::SEPARATORS[ $separator ] ) ) : '';

		$html = '';
		foreach ( $row_items as $r => $list ) {
			// Every other row goes the other way.
			$row_direction = $direction;
			if ( 1 === $r % 2 && empty( $attributes['sameDirection'] ) ) {
				$row_direction = array( 'left' => 'right', 'right' => 'left', 'up' => 'down', 'down' => 'up' )[ $direction ];
			}
			if ( $vertical ) {
				$animation = in_array( $row_direction, array( 'up', 'left' ), true ) ? 'marquee-scroll-up' : 'marquee-scroll-down';
			} else {
				$animation = 'marquee-scroll-' . ( 'right' === $row_direction ? 'right' : 'left' );
			}

			$container_classes = array( 'marquee-container', 'marquee-' . $orientation );
			if ( 'pause' === $hover ) {
				$container_classes[] = 'pause-on-hover';
			}

			// The separator also follows the last item, so it sits between copies too.
			$copy_html = $sep ? implode( $sep, $list ) . $sep : implode( '', $list );

			$html .= sprintf(
				'<div class="%1$s" data-direction="%2$s" style="--animation-duration:%3$ds;--animation-name:%4$s"><div class="marquee-content">%5$s</div><div class="marquee-content is-copy" aria-hidden="true" inert>%6$s</div></div>',
				esc_attr( implode( ' ', $container_classes ) ),
				esc_attr( $row_direction ),
				$duration,
				esc_attr( $animation ),
				$copy_html,
				self::copy( $copy_html )
			);
		}

		return sprintf( '<div %1$s%2$s>%3$s</div>', get_block_wrapper_attributes( $wrapper ), $data, $html );
	}

	/**
	 * A repeat of the items: no ids (they must stay unique on the page).
	 *
	 * @param string $html Items HTML.
	 * @return string
	 */
	public static function copy( $html ) {
		return (string) preg_replace( '/(<[a-z][a-z0-9-]*\b[^>]*?)\s+id=("[^"]*"|\'[^\']*\')/i', '$1', $html );
	}

	/**
	 * A responsive range value with its unit, or ''.
	 *
	 * @param array  $attributes Attributes.
	 * @param string $name       Control name.
	 * @param string $bp         desk|tab|mob.
	 * @return string
	 */
	private static function range( $attributes, $name, $bp ) {
		$ranges = isset( $attributes[ $name . 'Ranges' ] ) && is_array( $attributes[ $name . 'Ranges' ] ) ? $attributes[ $name . 'Ranges' ] : array();
		$units  = isset( $attributes[ $name . 'Units' ] ) && is_array( $attributes[ $name . 'Units' ] ) ? $attributes[ $name . 'Units' ] : array();
		if ( ! isset( $ranges[ $bp ] ) || ! is_numeric( $ranges[ $bp ] ) ) {
			return '';
		}
		$unit = isset( $units[ $bp ] ) && in_array( $units[ $bp ], self::UNITS, true ) ? $units[ $bp ] : 'px';
		return (float) $ranges[ $bp ] . $unit;
	}

	/**
	 * An item: its link behind the content (no nested links), and no empty
	 * `background-image:url()` for items without an image.
	 *
	 * @param string $content Item HTML.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public function item( $content, $block ) {
		$content = (string) $content;
		$attrs   = $block['attrs'] ?? array();

		if ( ! preg_match( '/<(a|div)\b[^>]*\bclass="marquee-container-outer[^"]*"[^>]*>/', $content, $match, PREG_OFFSET_CAPTURE ) ) {
			return $content;
		}
		$open_tag = $match[0][0];
		$open_at  = $match[0][1];
		$is_link  = 'a' === $match[1][0];
		$has_img  = ! empty( $attrs['image']['url'] );

		$tags = new \WP_HTML_Tag_Processor( $open_tag );
		$tags->next_tag();
		$style   = (string) $tags->get_attribute( 'style' );
		$classic = 'classic' === ( $attrs['bgType'] ?? 'classic' );
		$strip   = static fn( $css ) => trim( (string) preg_replace( '/background-(image|size|position|repeat)\s*:\s*(url\([^)]*\)|[^;]*)\s*;?/', '', $css ), "; \t\n" );

		// Background image as a real image: responsive sizes, lazy loading, focal point.
		$img      = '';
		$image_id = isset( $attrs['image']['id'] ) ? absint( $attrs['image']['id'] ) : 0;
		if ( $classic && empty( $attrs['enableResponsiveBg'] ) && $image_id && wp_attachment_is_image( $image_id ) ) {
			$focus = isset( $attrs['focusPoint']['x'], $attrs['focusPoint']['y'] ) ? $attrs['focusPoint'] : array( 'x' => 0.5, 'y' => 0.5 );
			$img   = (string) wp_get_attachment_image(
				$image_id,
				'large',
				false,
				array(
					'class'    => 'gutslider-slide-bg',
					'alt'      => '',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'style'    => sprintf( 'object-position:%s%% %s%%', round( (float) $focus['x'] * 100, 2 ), round( (float) $focus['y'] * 100, 2 ) ),
				)
			);
			if ( $img ) {
				$style = $strip( $style );
			}
		}
		if ( $classic && ! $has_img ) {
			$style = $strip( $style );
		}

		if ( ! $is_link && ! $img && $style === (string) $tags->get_attribute( 'style' ) ) {
			return $content;
		}

		$link = '';
		if ( $is_link ) {
			$link_attrs = '';
			foreach ( (array) $tags->get_attribute_names_with_prefix( '' ) as $name ) {
				if ( in_array( $name, array( 'class', 'style' ), true ) ) {
					continue;
				}
				$value       = $tags->get_attribute( $name );
				$link_attrs .= true === $value ? ' ' . esc_attr( $name ) : sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
			}
			$label = '';
			if ( preg_match( '/<(h[1-6]|p)\b[^>]*>(.*?)<\/\1>/is', $content, $text ) ) {
				$label = trim( wp_strip_all_tags( $text[2] ) );
			}
			$link = sprintf(
				'<a class="gutslider-slide-link"%1$s><span class="gutslider-sr-only">%2$s</span></a>',
				$link_attrs,
				esc_html( '' !== $label ? $label : __( 'Open link', 'slider-blocks' ) )
			);
		}

		$new_open = sprintf(
			'<div class="%1$s"%2$s>',
			esc_attr( trim( (string) $tags->get_attribute( 'class' ) . ( $is_link ? ' has-slide-link' : '' ) ) ),
			'' !== $style ? ' style="' . esc_attr( $style ) . '"' : ''
		);

		$html = substr_replace( $content, $new_open . $link . $img, $open_at, strlen( $open_tag ) );
		if ( $is_link ) {
			$close_at = strrpos( $html, '</a>' );
			if ( false !== $close_at ) {
				$html = substr_replace( $html, '</div>', $close_at, 4 );
			}
		}
		return $html;
	}
}
