<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backdrop slider (gutsliders/backdrop-slider + gutsliders/backdrop-slide).
 *
 * One background (color, gradient, image or video, with an overlay) stays still while
 * the slides' content changes over it. The slides are either the block's own slides
 * (static) or its post template slide repeated for each post of a query (dynamic, GutSlider Pro).
 *
 * The block saves only its slides; the slider around them is built here, so its
 * markup can change without invalidating saved blocks.
 *
 * @package GutSlider\Blocks
 * @since   3.4.0
 */
final class BackdropSlider {

	/** Child block name. */
	const SLIDE = 'gutsliders/backdrop-slide';

	/** Values the settings may take (the first is the default). */
	const EFFECTS          = array( 'slide', 'fade', 'rise' );
	const PAGINATION_TYPES = array( 'bullets', 'fraction', 'progressbar' );
	const H_ALIGNS         = array( 'center', 'left', 'right' );
	const V_ALIGNS         = array( 'center', 'top', 'bottom' );
	const ORDER_BY         = array( 'date', 'title', 'modified', 'menu_order', 'rand' );

	/** Most posts a dynamic slider shows. */
	const MAX_POSTS = 20;

	/**
	 * Whether the slides come from a query: needs GutSlider Pro, static otherwise.
	 *
	 * @param array $attributes Block attributes.
	 * @return bool
	 */
	public static function is_dynamic( $attributes ) {
		return 'dynamic' === ( $attributes['contentSource'] ?? 'static' ) && defined( 'GUTSLIDER_PRO_VERSION' );
	}

	/**
	 * Whole block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Rendered slides.
	 * @param \WP_Block $block      Block instance.
	 * @return string HTML.
	 */
	public static function render( $attributes, $content, $block ) {
		if ( self::is_dynamic( $attributes ) ) {
			list( $slides, $count ) = self::render_posts( $block, $attributes );
		} else {
			list( $slides, $count ) = self::render_static( $block, (string) $content );
		}

		// No slides (or no posts): nothing to show.
		if ( ! $count || '' === trim( $slides ) ) {
			return '';
		}

		$show_nav = ! empty( $attributes['showNavigation'] ) || ! isset( $attributes['showNavigation'] );
		$show_pag = ! empty( $attributes['showPagination'] ) || ! isset( $attributes['showPagination'] );
		$pag_type = self::pick( $attributes['paginationType'] ?? '', self::PAGINATION_TYPES );
		$h_align  = self::pick( $attributes['contentHAlign'] ?? '', self::H_ALIGNS );
		$v_align  = self::pick( $attributes['contentVAlign'] ?? '', self::V_ALIGNS );
		$multiple = $count > 1;

		$classes = array(
			sanitize_html_class( (string) ( $attributes['uniqueId'] ?? '' ) ),
			'gs-h-' . $h_align,
			'gs-v-' . $v_align,
		);
		if ( $show_nav && $multiple ) {
			$classes[] = 'has-nav';
		}
		if ( $show_pag && $multiple ) {
			$classes[] = 'has-pagination';
			$classes[] = 'has-pagination-' . $pag_type;
		}

		$wrapper = get_block_wrapper_attributes(
			array(
				'class'                => implode( ' ', array_filter( $classes ) ),
				'data-swiper-options'  => (string) wp_json_encode( self::swiper_options( $attributes, $count ) ),
				'data-label-pause'     => __( 'Pause slider', 'slider-blocks' ),
				'data-label-play'      => __( 'Play slider', 'slider-blocks' ),
				'data-pagination-type' => $pag_type,
			)
		);

		$html  = '<div ' . $wrapper . '>';
		$html .= self::render_backdrop( (array) ( $attributes['backdrop'] ?? array() ) );
		$html .= '<div class="swiper"><div class="swiper-wrapper">' . $slides . '</div>';
		if ( $show_pag && $multiple ) {
			$html .= '<div class="swiper-pagination"></div>';
		}
		$html .= '</div>';
		if ( $show_nav && $multiple ) {
			$html .= self::render_nav();
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * The shared background: image or video element and the overlay. Colors,
	 * gradients, the focal point and the overlay's look come from the block style.
	 *
	 * @param array $backdrop Backdrop attribute.
	 * @return string HTML.
	 */
	private static function render_backdrop( $backdrop ) {
		$type  = $backdrop['bgType'] ?? '';
		$media = '';

		if ( 'classic' === $type && ! empty( $backdrop['image']['url'] ) ) {
			$id = absint( $backdrop['image']['id'] ?? 0 );
			if ( $id && wp_attachment_is_image( $id ) ) {
				$media = wp_get_attachment_image(
					$id,
					'full',
					false,
					array(
						'class'    => 'gs-backdrop__media',
						'alt'      => '',
						'sizes'    => '100vw',
						'decoding' => 'async',
					)
				);
			}
			if ( ! $media ) {
				$media = sprintf( '<img class="gs-backdrop__media" src="%s" alt="" decoding="async" />', esc_url( $backdrop['image']['url'] ) );
			}
		} elseif ( 'video' === $type && ! empty( $backdrop['bgVideo']['url'] ) ) {
			$media = sprintf(
				'<video class="gs-backdrop__media" src="%s" autoplay muted loop playsinline preload="metadata"></video>',
				esc_url( $backdrop['bgVideo']['url'] )
			);
		}

		return '<div class="gs-backdrop" aria-hidden="true">' . $media . '<span class="gs-backdrop__overlay"></span></div>';
	}

	/**
	 * Previous / next buttons.
	 *
	 * @return string HTML.
	 */
	private static function render_nav() {
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="%s"/></svg>';

		return sprintf(
			'<button type="button" class="gs-backdrop-nav gs-backdrop-prev" aria-label="%1$s">%2$s</button><button type="button" class="gs-backdrop-nav gs-backdrop-next" aria-label="%3$s">%4$s</button>',
			esc_attr__( 'Previous slide', 'slider-blocks' ),
			sprintf( $icon, 'm10.83 12 4.95 4.95-1.41 1.41L8 12l6.36-6.36 1.41 1.41z' ),
			esc_attr__( 'Next slide', 'slider-blocks' ),
			sprintf( $icon, 'M13.17 12 8.22 7.05l1.41-1.41L16 12l-6.36 6.36-1.41-1.41z' )
		);
	}

	/**
	 * Swiper settings (navigation and pagination elements are found by frontend.js).
	 *
	 * @param array $attributes Block attributes.
	 * @param int   $count      Number of slides.
	 * @return array
	 */
	public static function swiper_options( $attributes, $count ) {
		$multiple = $count > 1;
		$effect   = self::pick( $attributes['effect'] ?? '', self::EFFECTS );
		$speed    = self::clamp( $attributes['speed'] ?? 700, 100, 3000 );
		$delay    = self::clamp( $attributes['autoplayDelay'] ?? 5000, 1000, 15000 );

		$options = array(
			'slidesPerView' => 1,
			'spaceBetween'  => 0,
			'speed'         => $speed,
			'loop'          => $multiple && ( $attributes['infiniteLoop'] ?? true ),
			'grabCursor'    => $multiple,
			'allowTouchMove' => $multiple,
			'autoplay'      => $multiple && ! empty( $attributes['autoplay'] )
				? array(
					'delay'                => $delay,
					'pauseOnMouseEnter'    => (bool) ( $attributes['pauseOnHover'] ?? true ),
					'disableOnInteraction' => false,
				)
				: false,
			'keyboard'      => array(
				'enabled'        => (bool) ( $attributes['keyboardControl'] ?? true ),
				'onlyInViewport' => true,
			),
			'a11y'          => array(
				'enabled'                         => true,
				'containerRoleDescriptionMessage' => __( 'carousel', 'slider-blocks' ),
				'itemRoleDescriptionMessage'      => __( 'slide', 'slider-blocks' ),
				'prevSlideMessage'                => __( 'Previous slide', 'slider-blocks' ),
				'nextSlideMessage'                => __( 'Next slide', 'slider-blocks' ),
				/* translators: Screen reader label of a slide. Keep {{index}} (slide number) and {{slidesLength}} (number of slides). */
				'slideLabelMessage'               => __( '{{index}} / {{slidesLength}}', 'slider-blocks' ),
			),
		);

		if ( 'fade' === $effect ) {
			$options['effect']     = 'fade';
			$options['fadeEffect'] = array( 'crossFade' => true );
		} elseif ( 'rise' === $effect ) {
			$options['effect']         = 'creative';
			$options['creativeEffect'] = array(
				'prev' => array(
					'opacity'   => 0,
					'translate' => array( 0, '-12%', 0 ),
				),
				'next' => array(
					'opacity'   => 0,
					'translate' => array( 0, '12%', 0 ),
				),
			);
		}

		/**
		 * Filters the Swiper settings of a Backdrop slider.
		 *
		 * @param array $options    Swiper options.
		 * @param array $attributes Block attributes.
		 */
		return apply_filters( 'gutslider_backdrop_swiper_options', $options, $attributes );
	}

	/**
	 * Whether a parsed block is a slide.
	 *
	 * @param array $inner Parsed block.
	 * @return bool
	 */
	private static function is_slide( $inner ) {
		return self::SLIDE === ( $inner['blockName'] ?? '' );
	}

	/**
	 * Whether a parsed slide is the post template (only used in dynamic mode).
	 *
	 * @param array $inner Parsed block.
	 * @return bool
	 */
	private static function is_template( $inner ) {
		return ! empty( $inner['attrs']['isTemplate'] );
	}

	/**
	 * Static slides: the rendered slides, without the post template.
	 *
	 * @param \WP_Block $block   Block instance.
	 * @param string    $content Rendered inner blocks.
	 * @return array [ HTML, number of slides ]
	 */
	private static function render_static( $block, $content ) {
		$parsed = $block->parsed_block['innerBlocks'] ?? array();
		$slides = array_filter( $parsed, array( __CLASS__, 'is_slide' ) );
		$static = array_filter(
			$slides,
			static function ( $inner ) {
				return ! self::is_template( $inner );
			}
		);

		// No template: the rendered content is exactly the slides.
		if ( count( $static ) === count( $slides ) ) {
			return array( $content, count( $slides ) );
		}

		$html = '';
		foreach ( $block->inner_blocks as $inner ) {
			if ( self::is_slide( $inner->parsed_block ) && ! self::is_template( $inner->parsed_block ) ) {
				$html .= $inner->render();
			}
		}
		return array( $html, count( $static ) );
	}

	/**
	 * Dynamic slides: the post template slide rendered once per post, with the post as its context
	 * (the same way core's Post Template block renders its template).
	 *
	 * @param \WP_Block $block      Block instance.
	 * @param array     $attributes Block attributes.
	 * @return array [ HTML, number of slides ]
	 */
	private static function render_posts( $block, $attributes ) {
		$template = null;
		foreach ( $block->parsed_block['innerBlocks'] ?? array() as $inner ) {
			if ( self::is_slide( $inner ) && self::is_template( $inner ) ) {
				$template = $inner;
				break;
			}
		}
		if ( ! $template ) {
			return array( '', 0 );
		}

		$query = new \WP_Query( self::query_args( $attributes ) );
		$html  = '';

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id   = (int) get_the_ID();
			$post_type = (string) get_post_type();

			$set_context = static function ( $context ) use ( $post_id, $post_type ) {
				$context['postId']   = $post_id;
				$context['postType'] = $post_type;
				return $context;
			};

			add_filter( 'render_block_context', $set_context, 1 );
			$html .= ( new \WP_Block(
				$template,
				array(
					'postId'   => $post_id,
					'postType' => $post_type,
				)
			) )->render( array( 'dynamic' => false ) );
			remove_filter( 'render_block_context', $set_context, 1 );
		}

		$count = (int) $query->post_count;
		wp_reset_postdata();

		return array( $html, $count );
	}

	/**
	 * WP_Query arguments from the block's query settings.
	 *
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	public static function query_args( $attributes ) {
		$query     = isset( $attributes['query'] ) && is_array( $attributes['query'] ) ? $attributes['query'] : array();
		$post_type = sanitize_key( (string) ( $query['postType'] ?? 'post' ) );
		if ( ! post_type_exists( $post_type ) || ! is_post_type_viewable( $post_type ) ) {
			$post_type = 'post';
		}
		$orderby = self::pick( $query['orderBy'] ?? '', self::ORDER_BY );

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => self::clamp( $query['perPage'] ?? 5, 1, self::MAX_POSTS ),
			'offset'              => self::clamp( $query['offset'] ?? 0, 0, 50 ),
			'orderby'             => $orderby,
			'order'               => 'asc' === strtolower( (string) ( $query['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		$current = PostSlider::current_post_id();
		if ( ( $query['excludeCurrent'] ?? true ) && $current ) {
			$args['post__not_in'] = array( $current ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		}

		// `taxQuery`: { taxonomy: [{ label, value }] }, as TaxonomyFilters saves it.
		$tax_query = array();
		if ( ! empty( $query['taxQuery'] ) && is_array( $query['taxQuery'] ) ) {
			foreach ( $query['taxQuery'] as $taxonomy => $terms ) {
				$taxonomy = sanitize_key( (string) $taxonomy );
				$term_ids = is_array( $terms )
					? array_values(
						array_filter(
							array_map(
								static function ( $term ) {
									return absint( is_array( $term ) ? ( $term['value'] ?? 0 ) : $term );
								},
								$terms
							)
						)
					)
					: array();
				if ( $term_ids && taxonomy_exists( $taxonomy ) && is_object_in_taxonomy( $post_type, $taxonomy ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term_ids,
					);
				}
			}
		}
		if ( $tax_query ) {
			// Every group must match (same as the REST query the editor previews with).
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		/**
		 * Filters the query behind a dynamic Backdrop slider.
		 *
		 * @param array $args       WP_Query arguments.
		 * @param array $attributes Block attributes.
		 */
		return apply_filters( 'gutslider_backdrop_query_args', $args, $attributes );
	}

	/**
	 * A known value, or the first (default) one.
	 *
	 * @param mixed    $value   Saved value.
	 * @param string[] $allowed Allowed values, default first.
	 * @return string
	 */
	private static function pick( $value, $allowed ) {
		return in_array( $value, $allowed, true ) ? $value : $allowed[0];
	}

	/**
	 * A whole number within bounds.
	 *
	 * @param mixed $value Saved value.
	 * @param int   $min   Minimum.
	 * @param int   $max   Maximum.
	 * @return int
	 */
	private static function clamp( $value, $min, $max ) {
		return max( $min, min( $max, (int) $value ) );
	}
}
