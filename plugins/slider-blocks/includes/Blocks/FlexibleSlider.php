<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flexible slider (gutsliders/any-content + gutsliders/slide): page output fixes.
 *
 * The blocks save static markup and many are published, so nothing here changes what
 * is saved: the saved HTML of each slide is adjusted as it is printed.
 *
 * - A slide link wraps the whole slide in `<a>`; buttons and links inside it made
 *   invalid nested links, which browsers re-arrange (content went missing). The slide
 *   becomes a `<div>` with a stretched link behind its content.
 * - Background images become real `<img>` elements: responsive sizes, lazy loading for
 *   slides after the first, the focal point kept.
 * - Slides without an image no longer print `background-image:url()`.
 * - A slide's fixed height becomes a minimum, so long content is never cut off.
 * - Background videos get the slide image as their poster; autoplay sliders get the
 *   labels for their pause button (frontend.js).
 * - The newer settings (design, height mode, animations, background effect, tab
 *   pagination, progress bar, deep links; per slide: position, text colour, width,
 *   visibility, schedule) become classes and data attributes here.
 *
 * @package GutSlider\Blocks
 * @since   3.1.1
 */
final class FlexibleSlider {

	/** Sliders rendered so far in this request. */
	private static $sliders = 0;

	/** Slides rendered so far in the current slider. */
	private static $slides = 0;

	/** Settings of the slider being rendered, for its slides. */
	private static $context = array();

	/** Slide content positions. */
	const POSITIONS = array( 'top-left', 'top-center', 'top-right', 'center-left', 'center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right' );

	/** Values the new slider settings may take. */
	const DESIGNS    = array( 'hero', 'split', 'cards', 'fullscreen', 'stacked', 'ticker' );
	const ANIMATIONS = array( 'fade-up', 'fade', 'zoom', 'slide-left' );
	const BG_EFFECTS = array( 'kenburns', 'parallax' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'render_block_data', array( $this, 'prepare' ), 10, 1 );
		add_filter( 'render_block', array( $this, 'render' ), 20, 2 );
	}

	/**
	 * Before a slider renders: count it, and turn its slide height into a minimum.
	 *
	 * @param array $block Parsed block.
	 * @return array
	 */
	public function prepare( $block ) {
		if ( 'gutsliders/any-content' !== ( $block['blockName'] ?? '' ) ) {
			return $block;
		}

		++self::$sliders;
		self::$slides  = 0;
		self::$context = $block['attrs'] ?? array();

		$mode     = $block['attrs']['heightMode'] ?? 'min';
		$vertical = 'vertical' === ( $block['attrs']['direction'] ?? '' );
		// Blocks saved before height modes wrote a fixed `height`: a minimum now (see below).
		if ( 'min' === $mode && ! $vertical && ! empty( $block['attrs']['blockStyle'] ) && is_string( $block['attrs']['blockStyle'] ) ) {
			$block['attrs']['blockStyle'] = self::min_height( $block['attrs']['blockStyle'] );
		}

		return $block;
	}

	/**
	 * `height` in the slide rules of a saved block style becomes `min-height`
	 * (same as dynamic-style.js now writes it).
	 *
	 * @param string $css Block style.
	 * @return string
	 */
	public static function min_height( $css ) {
		return (string) preg_replace_callback(
			'/(\.gutslider-[\w-]+\s+\.swiper-slide\s*\{)([^}]*)\}/',
			static function ( $match ) {
				return $match[1] . preg_replace( '/(?<![\w-])height\s*:/', 'min-height:', $match[2] ) . '}';
			},
			$css
		);
	}

	/**
	 * Adjust a slide's or a slider's output.
	 *
	 * @param string $content Block HTML.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public function render( $content, $block ) {
		$name = $block['blockName'] ?? '';

		if ( 'gutsliders/slide' === $name ) {
			$attrs = $block['attrs'] ?? array();
			// Scheduled slides are left out outside their dates.
			if ( self::out_of_schedule( $attrs ) ) {
				return '';
			}
			++self::$slides;
			return self::slide( (string) $content, $attrs );
		}

		if ( 'gutsliders/any-content' === $name ) {
			return self::slider( (string) $content, $block['attrs'] ?? array() );
		}

		return $content;
	}

	/**
	 * The slider: classes for its design and settings, screen reader wording, pause
	 * button labels (autoplay) and its deep link id.
	 *
	 * @param string $content Slider HTML.
	 * @param array  $attrs   Attributes.
	 * @return string
	 */
	private static function slider( $content, $attrs ) {
		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( ! $tags->next_tag( array( 'class_name' => 'wp-block-gutsliders-any-content' ) ) ) {
			return $content;
		}

		// Same classes as flexClasses() in edit.js.
		$design    = in_array( $attrs['design'] ?? '', self::DESIGNS, true ) ? $attrs['design'] : '';
		$mode      = $attrs['heightMode'] ?? 'min';
		$animation = in_array( $attrs['contentAnimation'] ?? '', self::ANIMATIONS, true ) ? $attrs['contentAnimation'] : '';
		$bg_effect = in_array( $attrs['bgEffect'] ?? '', self::BG_EFFECTS, true ) ? $attrs['bgEffect'] : '';
		$autoplay  = ! empty( $attrs['sliderOptions']['autoplay'] );
		$classes   = array_filter(
			array(
				$design ? 'gs-flex-design-' . $design : '',
				in_array( $mode, array( 'fixed', 'auto' ), true ) ? 'gs-height-' . $mode : '',
				'vertical' === ( $attrs['direction'] ?? '' ) ? 'gs-vertical' : '',
				$animation ? 'gs-anim-' . $animation : '',
				$bg_effect ? 'gs-bg-' . $bg_effect : '',
				// showPagination is on by default (not saved when on).
				! empty( $attrs['paginationTabs'] ) && ( $attrs['showPagination'] ?? true ) ? 'has-tab-pagination' : '',
				! empty( $attrs['showProgress'] ) && $autoplay ? 'has-progress' : '',
			)
		);
		foreach ( $classes as $class ) {
			$tags->add_class( $class );
		}

		// Screen reader wording for Swiper's accessibility module.
		$tags->set_attribute( 'data-label-carousel', __( 'carousel', 'slider-blocks' ) );
		$tags->set_attribute( 'data-label-slide', __( 'slide', 'slider-blocks' ) );
		/* translators: 1: slide number, 2: number of slides. */
		$tags->set_attribute( 'data-label-position', __( '%1$s of %2$s', 'slider-blocks' ) );

		if ( $autoplay ) {
			$tags->set_attribute( 'data-label-pause', __( 'Pause slideshow', 'slider-blocks' ) );
			$tags->set_attribute( 'data-label-play', __( 'Play slideshow', 'slider-blocks' ) );
		}

		// Deep links: #{anchor or id}-{slide number}.
		if ( ! empty( $attrs['deepLink'] ) ) {
			$id = $tags->get_attribute( 'id' );
			$tags->set_attribute( 'data-deep-link', is_string( $id ) && '' !== $id ? $id : (string) ( $attrs['uniqueId'] ?? '' ) );
		}

		return $tags->get_updated_html();
	}

	/**
	 * Whether a slide is outside its schedule (site time zone).
	 *
	 * @param array $attrs Slide attributes.
	 * @return bool
	 */
	private static function out_of_schedule( $attrs ) {
		$start = isset( $attrs['scheduleStart'] ) ? (string) $attrs['scheduleStart'] : '';
		$end   = isset( $attrs['scheduleEnd'] ) ? (string) $attrs['scheduleEnd'] : '';
		if ( '' === $start && '' === $end ) {
			return false;
		}
		try {
			$zone = wp_timezone();
			$now  = new \DateTimeImmutable( 'now', $zone );
			if ( '' !== $start && $now < new \DateTimeImmutable( $start, $zone ) ) {
				return true;
			}
			if ( '' !== $end && $now > new \DateTimeImmutable( $end, $zone ) ) {
				return true;
			}
		} catch ( \Exception $e ) {
			return false;
		}
		return false;
	}

	/**
	 * One slide.
	 *
	 * @param string $content Slide HTML.
	 * @param array  $attrs   Attributes (saved, non-default ones).
	 * @return string
	 */
	private static function slide( $content, $attrs ) {
		// The slide's content box: <a> (linked slide) or <div>.
		if ( ! preg_match( '/<(a|div)\b[^>]*\bclass="swiper-container-outer[^"]*"[^>]*>/', $content, $match, PREG_OFFSET_CAPTURE ) ) {
			return $content;
		}
		$open_tag   = $match[0][0];
		$open_at    = $match[0][1];
		$is_link    = 'a' === $match[1][0];
		$bg_type    = $attrs['bgType'] ?? 'classic';
		$responsive = ! empty( $attrs['enableResponsiveBg'] );
		$image      = isset( $attrs['image'] ) && is_array( $attrs['image'] ) ? $attrs['image'] : array();
		$image_id   = isset( $image['id'] ) ? absint( $image['id'] ) : 0;
		$image_url  = isset( $image['url'] ) ? (string) $image['url'] : '';

		$tags = new \WP_HTML_Tag_Processor( $open_tag );
		$tags->next_tag();
		$style = (string) $tags->get_attribute( 'style' );

		$before = '';
		// Background image as a real image (not for responsive or gradient backgrounds).
		if ( 'classic' === $bg_type && ! $responsive && $image_id && wp_attachment_is_image( $image_id ) ) {
			$focus    = isset( $attrs['focusPoint']['x'], $attrs['focusPoint']['y'] ) ? $attrs['focusPoint'] : array( 'x' => 0.5, 'y' => 0.5 );
			$first    = 1 === self::$slides;
			$img_html = wp_get_attachment_image(
				$image_id,
				'full',
				false,
				array(
					'class'         => 'gutslider-slide-bg',
					// Decorative: the slide's own content says what it is about.
					'alt'           => '',
					'loading'       => $first ? 'eager' : 'lazy',
					'fetchpriority' => $first && 1 === self::$sliders ? 'high' : 'auto',
					'decoding'      => 'async',
					'style'         => sprintf( 'object-position:%s%% %s%%', round( (float) $focus['x'] * 100, 2 ), round( (float) $focus['y'] * 100, 2 ) ),
				)
			);
			// Parallax: the image moves slower than the slide.
			if ( $img_html && 'parallax' === ( self::$context['bgEffect'] ?? '' ) ) {
				$img_html = str_replace( '<img ', '<img data-swiper-parallax="35%" ', $img_html );
			}
			if ( $img_html ) {
				$before .= $img_html;
				$style   = self::strip_background_image( $style );
			}
		}

		// No image at all: drop the empty `url()` and what goes with it.
		if ( 'classic' === $bg_type && '' === $image_url ) {
			$style = self::strip_background_image( $style );
		}

		// A linked slide: a <div>, with the link stretched behind its content.
		$link = '';
		if ( $is_link ) {
			$link_attrs = '';
			foreach ( (array) $tags->get_attribute_names_with_prefix( '' ) as $name ) {
				if ( in_array( $name, array( 'class', 'style' ), true ) ) {
					continue;
				}
				$value = $tags->get_attribute( $name );
				$link_attrs .= true === $value ? ' ' . esc_attr( $name ) : sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
			}
			$label = self::slide_label( $content );
			$link  = sprintf( '<a class="gutslider-slide-link"%1$s><span class="gutslider-sr-only">%2$s</span></a>', $link_attrs, esc_html( $label ) );
		}

		// Per-slide settings (same as slideClasses() / slideStyle() in slide.js).
		$classes  = (string) $tags->get_attribute( 'class' );
		$position = $attrs['contentPosition'] ?? '';
		if ( in_array( $position, self::POSITIONS, true ) ) {
			$classes .= ' gs-pos-' . $position;
		}
		if ( in_array( $attrs['textColor'] ?? '', array( 'light', 'dark' ), true ) ) {
			$classes .= ' gs-text-' . $attrs['textColor'];
		}
		$width = isset( $attrs['contentMaxWidth'] ) ? (int) $attrs['contentMaxWidth'] : 0;
		if ( $width > 0 ) {
			$classes .= ' gs-has-width';
			$style    = trim( $style . ';--gs-content-width:' . $width . 'px', ';' );
		}
		if ( $is_link ) {
			$classes .= ' has-slide-link';
		}

		$new_open = sprintf(
			'<div class="%1$s"%2$s>',
			esc_attr( trim( $classes ) ),
			'' !== trim( $style ) ? ' style="' . esc_attr( $style ) . '"' : ''
		);

		$html = substr_replace( $content, $new_open . $link . $before, $open_at, strlen( $open_tag ) );

		if ( $is_link ) {
			// The box's closing </a> is the last one in the slide.
			$close_at = strrpos( $html, '</a>' );
			if ( false !== $close_at ) {
				$html = substr_replace( $html, '</div>', $close_at, 4 );
			}
		}

		// The slide itself: its name (tab pagination) and where it is hidden.
		$slide_tag = new \WP_HTML_Tag_Processor( $html );
		if ( $slide_tag->next_tag( array( 'class_name' => 'swiper-slide' ) ) ) {
			$title = isset( $attrs['slideTitle'] ) && '' !== $attrs['slideTitle'] ? (string) $attrs['slideTitle'] : self::slide_label( $content, '' );
			if ( '' !== $title ) {
				$slide_tag->set_attribute( 'data-title', $title );
			}
			$hide = array_intersect( (array) ( $attrs['hideOn'] ?? array() ), array( 'desktop', 'tablet', 'mobile' ) );
			if ( $hide ) {
				$slide_tag->set_attribute( 'data-hide', implode( ' ', $hide ) );
			}
			if ( 'parallax' === ( self::$context['bgEffect'] ?? '' ) ) {
				$slide_tag->add_class( 'has-parallax' );
			}
			$html = $slide_tag->get_updated_html();
		}

		// Background video: the slide image as its poster.
		if ( 'video' === $bg_type && '' !== $image_url && false === strpos( $html, 'poster=' ) ) {
			$html = preg_replace( '/<video\b(?=[^>]*class="gutslider-video")/', '<video poster="' . esc_url( $image_url ) . '"', $html, 1 );
		}

		return $html;
	}

	/**
	 * Remove the background image declarations from an inline style.
	 *
	 * @param string $style Inline style.
	 * @return string
	 */
	private static function strip_background_image( $style ) {
		$style = preg_replace( '/background-(image|size|position|repeat)\s*:\s*(url\([^)]*\)|[^;]*)\s*;?/', '', $style );
		return trim( (string) $style, "; \t\n" );
	}

	/**
	 * A name for a slide link: the slide's first heading, else a generic label.
	 *
	 * @param string $content Slide HTML.
	 * @return string
	 */
	private static function slide_label( $content, $fallback = null ) {
		if ( preg_match( '/<h[1-6]\b[^>]*>(.*?)<\/h[1-6]>/is', $content, $heading ) ) {
			$text = trim( wp_strip_all_tags( $heading[1] ) );
			if ( '' !== $text ) {
				return $text;
			}
		}
		return null === $fallback ? __( 'Open this slide', 'slider-blocks' ) : $fallback;
	}
}
