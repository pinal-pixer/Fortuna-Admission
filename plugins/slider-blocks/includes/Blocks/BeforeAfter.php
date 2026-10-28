<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Before After block: server output.
 *
 * The block still saves its original markup (so every saved block stays valid), but
 * the page is built here: real, responsive images that show without JavaScript, and
 * only the settings the slider needs. The saved image data is never printed.
 *
 * Class names match the ones the block's style settings target (labels, caption,
 * `__rcs-handle-*`), so saved styles keep working. compare.js makes it interactive.
 *
 * @package GutSlider\Blocks
 * @since   3.1.1
 */
final class BeforeAfter {

	/** Labels positions and animations the block offers. */
	const V_POSITIONS  = array( 'icgbv__top', 'icgbv__center', 'icgbv__bottom' );
	const H_POSITIONS  = array( 'icgbh__left', 'icgbh__center', 'icgbh__right' );
	const ANIMATIONS   = array( 'icgb__fade', 'icgb__zoom_in', 'icgb__zoom_out', 'icgb__slide_down', 'icgb__slide_up', 'icgb__slide_left', 'icgb__slide_right' );
	const CAPTION_TAGS = array( 'span', 'p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

	const DESIGNS       = array( 'minimal', 'glass', 'card', 'split', 'dark' );
	const RATIOS        = array( '16/9', '3/2', '4/3', '1/1', '3/4', '9/16', '21/9' );
	const HANDLE_STYLES = array( 'circle', 'arrows', 'line', 'pill', 'icon' );
	const MODES         = array( 'slide', 'fade', 'toggle' );
	const CTA_POSITIONS = array( 'bottom-left', 'bottom-center', 'bottom-right', 'top-left' );

	/** Comparisons printed so far in this request (the first one may be above the fold). */
	private static $count = 0;

	/**
	 * Settings with the block's defaults filled in (unchanged settings aren't saved).
	 *
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	public static function normalize( $attributes ) {
		$slider = wp_parse_args(
			isset( $attributes['sliderOptions'] ) && is_array( $attributes['sliderOptions'] ) ? $attributes['sliderOptions'] : array(),
			array(
				'showLabels'       => true,
				'labelsVisibility' => 'always',
				'hoverAnimation'   => 'icgb__fade',
				'labelVPosition'   => 'icgbv__center',
				'labelHPosition'   => 'icgbh__center',
				'beforeLabel'      => 'Before',
				'afterLabel'       => 'After',
			)
		);
		$compare = wp_parse_args(
			isset( $attributes['comparisonOptions'] ) && is_array( $attributes['comparisonOptions'] ) ? $attributes['comparisonOptions'] : array(),
			array(
				'disableSliding'      => false,
				'onlyHandleDraggable' => false,
				'slideMode'           => 'horizontal',
				'swipeMode'           => 'drag',
				'initialPosition'     => 50,
			)
		);

		$pick = static function ( $key, $allowed, $fallback ) use ( $attributes ) {
			return isset( $attributes[ $key ] ) && in_array( $attributes[ $key ], $allowed, true ) ? $attributes[ $key ] : $fallback;
		};

		// More comparisons (carousel).
		$pairs = array();
		if ( ! empty( $attributes['pairs'] ) && is_array( $attributes['pairs'] ) ) {
			foreach ( $attributes['pairs'] as $pair ) {
				$pair = array(
					'before'  => self::image( $pair['beforeImage'] ?? array() ),
					'after'   => self::image( $pair['afterImage'] ?? array() ),
					'caption' => isset( $pair['caption'] ) ? (string) $pair['caption'] : '',
				);
				if ( '' !== $pair['before']['url'] && '' !== $pair['after']['url'] ) {
					$pairs[] = $pair;
				}
			}
		}

		return array(
			'uniqueId'    => isset( $attributes['uniqueId'] ) ? (string) $attributes['uniqueId'] : '',
			'before'      => self::image( $attributes['beforeImage'] ?? array() ),
			'after'       => self::image( $attributes['afterImage'] ?? array() ),
			'slider'      => $slider,
			'compare'     => $compare,
			'showCaption' => ! empty( $attributes['showCaption'] ),
			'caption'     => isset( $attributes['sliderCaption'] ) ? (string) $attributes['sliderCaption'] : '',
			'captionTag'  => $pick( 'captionTag', self::CAPTION_TAGS, 'span' ),
			'design'      => $pick( 'design', self::DESIGNS, '' ),
			'ratio'       => $pick( 'aspectRatio', self::RATIOS, '' ),
			'imageSize'   => $pick( 'imageSize', array( 'large', 'full' ), 'full' ),
			'handleStyle' => $pick( 'handleStyle', self::HANDLE_STYLES, 'circle' ),
			'handleText'  => isset( $attributes['handleText'] ) ? (string) $attributes['handleText'] : '',
			'handleIcon'  => isset( $attributes['handleIcon'] ) ? (string) $attributes['handleIcon'] : '',
			'mode'        => $pick( 'compareMode', self::MODES, 'slide' ),
			'introHint'   => ! empty( $attributes['introHint'] ),
			'autoPlay'    => ! empty( $attributes['autoPlay'] ),
			'fullscreen'  => ! empty( $attributes['fullscreen'] ),
			'ctaText'     => isset( $attributes['ctaText'] ) ? (string) $attributes['ctaText'] : '',
			'ctaUrl'      => isset( $attributes['ctaUrl'] ) ? (string) $attributes['ctaUrl'] : '',
			'ctaNewTab'   => ! empty( $attributes['ctaNewTab'] ),
			'ctaPosition' => $pick( 'ctaPosition', self::CTA_POSITIONS, 'bottom-center' ),
			'video'       => 'video' === ( $attributes['mediaType'] ?? 'image' ),
			'beforeVideo' => self::image( $attributes['beforeVideo'] ?? array() ),
			'afterVideo'  => self::image( $attributes['afterVideo'] ?? array() ),
			'pairs'       => $pairs,
			'pairThumbs'  => ! isset( $attributes['pairThumbs'] ) || ! empty( $attributes['pairThumbs'] ),
		);
	}

	/**
	 * The parts of a saved image (or video) the page needs: id, url, alt.
	 *
	 * @param mixed $image Saved image.
	 * @return array{id:int,url:string,alt:string}
	 */
	private static function image( $image ) {
		$image = is_array( $image ) ? $image : array();
		return array(
			'id'  => isset( $image['id'] ) ? absint( $image['id'] ) : 0,
			'url' => isset( $image['url'] ) ? (string) $image['url'] : '',
			'alt' => isset( $image['alt'] ) ? (string) $image['alt'] : '',
		);
	}

	/**
	 * A label as shown: the untouched English default is translated.
	 *
	 * @param string $label Saved label.
	 * @param string $which before|after.
	 * @return string
	 */
	public static function label( $label, $which ) {
		if ( 'Before' === $label && 'before' === $which ) {
			return __( 'Before', 'slider-blocks' );
		}
		if ( 'After' === $label && 'after' === $which ) {
			return __( 'After', 'slider-blocks' );
		}
		return (string) $label;
	}

	/**
	 * Block output.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Saved markup (only its HTML anchor is used).
	 * @return string
	 */
	public static function render( $attributes, $content = '' ) {
		$atts = self::normalize( $attributes );

		// The first comparison (images or videos) must be complete.
		$first = $atts['video']
			? array( 'before' => $atts['beforeVideo'], 'after' => $atts['afterVideo'], 'caption' => '' )
			: array( 'before' => $atts['before'], 'after' => $atts['after'], 'caption' => '' );
		if ( '' === $first['before']['url'] || '' === $first['after']['url'] ) {
			return '';
		}

		// Several comparisons make a carousel (images only).
		$pairs = $atts['video'] ? array() : $atts['pairs'];

		$classes = array( $atts['uniqueId'] );
		if ( $atts['design'] ) {
			$classes[] = 'gs-ba-design-' . $atts['design'];
		}
		if ( $pairs ) {
			$classes[] = 'has-pairs';
		}
		$wrapper = array( 'class' => implode( ' ', array_filter( $classes ) ) );
		// The HTML anchor is saved in the markup, not in the attributes.
		if ( preg_match( '/^\s*<div\b[^>]*\bid="([^"]+)"/', (string) $content, $match ) ) {
			$wrapper['id'] = $match[1];
		}

		$caption = $atts['showCaption'] ? $atts['caption'] : '';

		if ( ! $pairs ) {
			return sprintf(
				'<div %1$s>%2$s</div>',
				get_block_wrapper_attributes( $wrapper ),
				self::figure( $first, $atts, $caption, true )
			);
		}

		// Carousel: Swiper only loads for blocks that need it.
		wp_enqueue_script( 'gutslider-swiper-script' );
		wp_enqueue_style( 'gutslider-swiper-style' );

		$all    = array_merge( array( array_merge( $first, array( 'caption' => $caption ) ) ), $pairs );
		$slides = '';
		foreach ( $all as $index => $pair ) {
			$slides .= sprintf(
				'<div class="swiper-slide" role="group" aria-label="%1$s">%2$s</div>',
				/* translators: 1: comparison number, 2: number of comparisons. */
				esc_attr( sprintf( __( '%1$d of %2$d', 'slider-blocks' ), $index + 1, count( $all ) ) ),
				self::figure( $pair, $atts, $pair['caption'], 0 === $index )
			);
		}

		$thumbs = '';
		if ( $atts['pairThumbs'] ) {
			foreach ( $all as $index => $pair ) {
				$thumb   = $pair['after']['id'] ? wp_get_attachment_image_url( $pair['after']['id'], 'thumbnail' ) : '';
				$thumbs .= sprintf(
					'<button type="button" class="gs-ba-thumbs__item%1$s" aria-label="%2$s"%3$s><img src="%4$s" alt="" loading="lazy" decoding="async" /></button>',
					0 === $index ? ' is-active' : '',
					/* translators: %d: comparison number. */
					esc_attr( sprintf( __( 'Show comparison %d', 'slider-blocks' ), $index + 1 ) ),
					0 === $index ? ' aria-current="true"' : '',
					esc_url( $thumb ? $thumb : $pair['after']['url'] )
				);
			}
			$thumbs = '<div class="gs-ba-thumbs">' . $thumbs . '</div>';
		}

		return sprintf(
			'<div %1$s><div class="gs-ba-carousel swiper" aria-roledescription="%2$s"><div class="swiper-wrapper">%3$s</div><button type="button" class="gs-ba-carousel__nav is-prev" aria-label="%4$s"></button><button type="button" class="gs-ba-carousel__nav is-next" aria-label="%5$s"></button></div>%6$s</div>',
			get_block_wrapper_attributes( $wrapper ),
			esc_attr__( 'carousel', 'slider-blocks' ),
			$slides,
			esc_attr__( 'Previous comparison', 'slider-blocks' ),
			esc_attr__( 'Next comparison', 'slider-blocks' ),
			$thumbs
		);
	}

	/**
	 * One comparison with its caption.
	 *
	 * @param array  $pair    before, after (id, url, alt).
	 * @param array  $atts    Normalised settings.
	 * @param string $caption Caption (HTML).
	 * @param bool   $lead    First comparison of the block.
	 * @return string HTML.
	 */
	private static function figure( $pair, $atts, $caption, $lead ) {
		$slider   = $atts['slider'];
		$compare  = $atts['compare'];
		$mode     = $atts['mode'];
		$portrait = 'slide' === $mode && 'vertical' === $compare['slideMode'];
		$position = max( 0, min( 100, (float) $compare['initialPosition'] ) );
		if ( 'toggle' === $mode ) {
			$position = 100;
		}

		// Only the page's first comparison may hold its main image.
		if ( $lead ) {
			++self::$count;
		}
		$eager = $lead && 1 === self::$count;

		$root_classes = array(
			'gutslider-compare-block',
			'gs-ba',
			$portrait ? 'is-vertical' : 'is-horizontal',
			'is-' . $mode,
			'has-handle-' . $atts['handleStyle'],
		);
		if ( $compare['disableSliding'] && 'toggle' !== $mode ) {
			$root_classes[] = 'is-disabled';
		}
		if ( $compare['onlyHandleDraggable'] ) {
			$root_classes[] = 'is-handle-only';
		}
		if ( $atts['ratio'] ) {
			$root_classes[] = 'has-ratio';
		}
		if ( $atts['video'] ) {
			$root_classes[] = 'is-video';
		}

		// Opacity of "after": fade follows the knob (right = after), toggle starts on "before".
		$style = sprintf( '--gs-ba-pos:%1$s%%;--gs-ba-o:%2$s;', $position, 'fade' === $mode ? $position / 100 : 1 - $position / 100 );
		if ( $atts['ratio'] ) {
			$style .= 'aspect-ratio:' . $atts['ratio'] . ';';
		}

		$before_label = self::label( $slider['beforeLabel'], 'before' );
		$after_label  = self::label( $slider['afterLabel'], 'after' );

		ob_start();
		?>
		<figure class="gs-ba-figure">
			<div
				class="<?php echo esc_attr( implode( ' ', $root_classes ) ); ?>"
				style="<?php echo esc_attr( $style ); ?>"
				data-orientation="<?php echo $portrait ? 'vertical' : 'horizontal'; ?>"
				data-position="<?php echo esc_attr( (string) $position ); ?>"
				data-compare="<?php echo esc_attr( $mode ); ?>"
				data-mode="<?php echo 'hover' === $compare['swipeMode'] ? 'hover' : 'drag'; ?>"
				data-handle-only="<?php echo $compare['onlyHandleDraggable'] ? '1' : '0'; ?>"
				data-disabled="<?php echo $compare['disableSliding'] ? '1' : '0'; ?>"
				data-hint="<?php echo $atts['introHint'] ? '1' : '0'; ?>"
				data-autoplay="<?php echo $atts['autoPlay'] ? '1' : '0'; ?>"
			>
				<?php
				echo self::item( $pair['before'], 'before', $before_label, $atts, $eager, $eager ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<div class="gs-ba__clip">
					<?php echo self::item( $pair['after'], 'after', $after_label, $atts, $eager, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php echo self::handle( $atts, $portrait, $position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo self::ui( $atts, $before_label, $after_label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php if ( '' !== trim( wp_strip_all_tags( $caption ) ) ) : ?>
				<figcaption class="gutslider-caption">
					<?php
					printf(
						'<%1$s class="gutslider-caption-text">%2$s</%1$s>',
						tag_escape( $atts['captionTag'] ),
						wp_kses_post( $caption )
					);
					?>
				</figcaption>
			<?php endif; ?>
		</figure>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The divider handle (slide) or knob (fade).
	 *
	 * @param array $atts     Normalised settings.
	 * @param bool  $portrait Vertical slider.
	 * @param float $position Start position.
	 * @return string HTML.
	 */
	private static function handle( $atts, $portrait, $position ) {
		$disabled = $atts['compare']['disableSliding'] || 'toggle' === $atts['mode'];

		if ( 'pill' === $atts['handleStyle'] ) {
			$inner = '<div class="__rcs-handle-arrow"></div><span class="gs-ba__handle-text">' . esc_html( '' !== $atts['handleText'] ? $atts['handleText'] : __( 'Drag', 'slider-blocks' ) ) . '</span><div class="__rcs-handle-arrow"></div>';
		} elseif ( 'icon' === $atts['handleStyle'] && '' !== $atts['handleIcon'] && function_exists( 'gutslider_display_icon' ) ) {
			$inner = '<span class="gs-ba__handle-icon">' . wp_kses( gutslider_display_icon( $atts['handleIcon'] ), gutslider_allowed_svg_tags() ) . '</span>';
		} else {
			$inner = '<div class="__rcs-handle-arrow"></div><div class="__rcs-handle-arrow"></div>';
		}

		return sprintf(
			'<div class="gs-ba__handle" role="slider" tabindex="%1$s" aria-label="%2$s" aria-orientation="%3$s" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%4$s"%5$s><div class="__rcs-handle-root"><div class="__rcs-handle-line"></div><div class="__rcs-handle-button">%6$s</div><div class="__rcs-handle-line"></div></div></div>',
			$disabled ? '-1' : '0',
			'fade' === $atts['mode'] ? esc_attr__( 'Fade between the before and after images', 'slider-blocks' ) : esc_attr__( 'Drag to compare the before and after images', 'slider-blocks' ),
			$portrait ? 'vertical' : 'horizontal',
			esc_attr( (string) round( $position ) ),
			$disabled ? ' aria-disabled="true"' : '',
			$inner
		);
	}

	/**
	 * Buttons over the comparison: toggle, play/pause, fullscreen, call to action.
	 *
	 * @param array  $atts         Normalised settings.
	 * @param string $before_label Before label.
	 * @param string $after_label  After label.
	 * @return string HTML.
	 */
	private static function ui( $atts, $before_label, $after_label ) {
		$html = '';

		if ( 'toggle' === $atts['mode'] ) {
			$html .= sprintf(
				'<div class="gs-ba__ui gs-ba__toggle" role="group" aria-label="%1$s"><button type="button" data-show="before" aria-pressed="true">%2$s</button><button type="button" data-show="after" aria-pressed="false">%3$s</button></div>',
				esc_attr__( 'Show', 'slider-blocks' ),
				esc_html( '' !== $before_label ? $before_label : __( 'Before', 'slider-blocks' ) ),
				esc_html( '' !== $after_label ? $after_label : __( 'After', 'slider-blocks' ) )
			);
		}

		if ( $atts['video'] ) {
			$html .= sprintf(
				'<button type="button" class="gs-ba__ui gs-ba__play" aria-pressed="true" aria-label="%1$s" data-label-play="%2$s" data-label-pause="%1$s"><span aria-hidden="true"></span></button>',
				esc_attr__( 'Pause videos', 'slider-blocks' ),
				esc_attr__( 'Play videos', 'slider-blocks' )
			);
		}

		if ( $atts['fullscreen'] ) {
			$html .= sprintf(
				'<button type="button" class="gs-ba__ui gs-ba__fs" aria-pressed="false" aria-label="%1$s" data-label-open="%1$s" data-label-close="%2$s"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>',
				esc_attr__( 'View fullscreen', 'slider-blocks' ),
				esc_attr__( 'Exit fullscreen', 'slider-blocks' )
			);
		}

		if ( '' !== $atts['ctaText'] && '' !== $atts['ctaUrl'] ) {
			$html .= sprintf(
				'<a class="gs-ba__ui gs-ba__cta is-%1$s" href="%2$s"%3$s>%4$s</a>',
				esc_attr( $atts['ctaPosition'] ),
				esc_url( $atts['ctaUrl'] ),
				$atts['ctaNewTab'] ? ' target="_blank" rel="noopener noreferrer"' : '',
				esc_html( $atts['ctaText'] )
			);
		}

		return $html;
	}

	/**
	 * One side: its label and image (or video).
	 *
	 * @param array  $media    id, url, alt.
	 * @param string $which    before|after.
	 * @param string $label    Label text.
	 * @param array  $atts     Normalised settings.
	 * @param bool   $eager    Load right away.
	 * @param bool   $priority May be fetched with high priority.
	 * @return string HTML.
	 */
	private static function item( $media, $which, $label, $atts, $eager, $priority ) {
		$slider     = $atts['slider'];
		$visibility = 'hover' === $slider['labelsVisibility'] ? 'hover' : 'always';
		$portrait   = 'slide' === $atts['mode'] && 'vertical' === $atts['compare']['slideMode'];

		$label_classes = array( 'gutslider-label', $which . '-label', $portrait ? 'vertical' : 'horizontal' );
		if ( 'hover' === $visibility && in_array( $slider['hoverAnimation'], self::ANIMATIONS, true ) ) {
			$label_classes[] = $slider['hoverAnimation'];
		}
		$place = $portrait ? $slider['labelHPosition'] : $slider['labelVPosition'];
		if ( in_array( $place, $portrait ? self::H_POSITIONS : self::V_POSITIONS, true ) ) {
			$label_classes[] = $place;
		}

		if ( $atts['video'] ) {
			$body = sprintf(
				'<video class="gs-ba__img gs-ba__video" src="%1$s" muted loop playsinline autoplay preload="metadata" aria-label="%2$s"></video>',
				esc_url( $media['url'] ),
				esc_attr( wp_strip_all_tags( $label ) )
			);
		} else {
			// The attachment's current alt text, else the saved one, else the label.
			$alt = $media['id'] ? (string) get_post_meta( $media['id'], '_wp_attachment_image_alt', true ) : '';
			if ( '' === $alt ) {
				$alt = '' !== $media['alt'] ? $media['alt'] : wp_strip_all_tags( $label );
			}
			$loading = $eager ? 'eager' : 'lazy';

			$body = $media['id'] && wp_attachment_is_image( $media['id'] )
				? wp_get_attachment_image(
					$media['id'],
					$atts['imageSize'],
					false,
					array(
						'class'         => 'gs-ba__img wp-image-' . $media['id'],
						'alt'           => $alt,
						'draggable'     => 'false',
						'decoding'      => 'async',
						'loading'       => $loading,
						'fetchpriority' => $priority ? 'high' : 'auto',
					)
				)
				: '';
			if ( '' === $body ) {
				$body = sprintf(
					'<img class="gs-ba__img" src="%1$s" alt="%2$s" draggable="false" decoding="async" loading="%3$s" />',
					esc_url( $media['url'] ),
					esc_attr( $alt ),
					esc_attr( $loading )
				);
			}
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( 'gutslider-' . $which . ' gutslider-ba-item ' . $visibility ); ?>">
			<?php if ( $slider['showLabels'] && '' !== $label && 'toggle' !== $atts['mode'] ) : ?>
				<div class="<?php echo esc_attr( implode( ' ', $label_classes ) ); ?>" aria-hidden="true"><?php echo esc_html( $label ); ?></div>
			<?php endif; ?>
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
