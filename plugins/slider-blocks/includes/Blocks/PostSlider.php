<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blog Post block: query, cache, markup, "Load more" endpoint and schema.
 *
 * The block is rendered on the server (render.php calls self::render()), so
 * posts are always current. Every option added here is off by default: a
 * slider saved before renders exactly as it did.
 *
 * @package GutSlider\Blocks
 * @since   3.1.1
 */
final class PostSlider {

	/** Overlay Grid: posts per page (one large tile, four small). Same as GRID_PAGE_SIZE in presets.js. */
	const GRID_PAGE_SIZE = 5;

	/** Designs with their own navigation instead of thumbnails. */
	const NO_THUMBS_DESIGNS = array( 'grid', 'featured' );

	/** Featured + List navigates with its list; "Load more" would only make it longer. */
	const NO_LOAD_MORE_DESIGNS = array( 'featured' );

	/**
	 * Swiper options each design needs, over the saved ones (so a block saved with an
	 * older version of a design still slides right). Same as PRESET_SWIPER_OPTIONS in
	 * presets.js; `null` removes the option (the carousel columns).
	 */
	const PRESET_SWIPER_OPTIONS = array(
		'magazine' => array(
			'effect'        => 'fade',
			'fadeEffect'    => array( 'crossFade' => true ),
			'slidesPerView' => 1,
			'spaceBetween'  => 0,
			'breakpoints'   => null,
		),
		'cards'    => array(
			'effect'     => 'slide',
			'grabCursor' => true,
		),
		'grid'     => array(
			'effect'        => 'slide',
			'slidesPerView' => 1,
			'spaceBetween'  => 24,
			'grabCursor'    => true,
			'breakpoints'   => null,
		),
		'featured' => array(
			'effect'        => 'fade',
			'fadeEffect'    => array( 'crossFade' => true ),
			'slidesPerView' => 1,
			'spaceBetween'  => 0,
			'breakpoints'   => null,
		),
		'timeline' => array(
			'effect'     => 'slide',
			'grabCursor' => true,
		),
	);

	/** All designs ('' = the original slider). */
	const DESIGNS = array( 'magazine', 'cards', 'grid', 'featured', 'timeline' );

	/** Option holding the cache version; bumped whenever posts change. */
	const CACHE_VERSION_OPTION = 'gutslider_post_slider_cache_v';

	/** Fallback badge colours, picked by term id (same list as the editor). */
	const BADGE_PALETTE = array( '#2563eb', '#db2777', '#059669', '#d97706', '#7c3aed', '#dc2626', '#0891b2', '#4f46e5' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		// Anything that can change what a slider shows invalidates cached results.
		foreach ( array( 'save_post', 'deleted_post', 'trashed_post', 'untrashed_post', 'set_object_terms', 'edited_term', 'delete_term', 'wp_update_comment_count', 'update_option_sticky_posts' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'flush_cache' ) );
		}
		add_action( 'transition_post_status', array( $this, 'on_status_change' ), 10, 3 );
		add_action( 'updated_post_meta', array( $this, 'on_meta_change' ), 10, 3 );
		add_action( 'added_post_meta', array( $this, 'on_meta_change' ), 10, 3 );
		add_action( 'deleted_post_meta', array( $this, 'on_meta_change' ), 10, 3 );
	}

	/* ---------------------------------------------------------------------
	 * Attributes
	 * ------------------------------------------------------------------ */

	/**
	 * Attributes with defaults; scalars sanitised as text (booleans become '1' / '').
	 *
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	public static function normalize( $attributes ) {
		$defaults = array(
			'showTitle'            => true,
			'showCat'              => true,
			'showExcerpt'          => true,
			'showBtn'              => true,
			'linkTitle'            => true,
			'titleTag'             => 'h2',
			'btnLabel'             => __( 'Read More', 'slider-blocks' ),
			'enableOverlay'        => true,
			'sliderType'           => 'slider',
			'uniqueId'             => '',
			'navContainerPosition' => 'nav_inside',
			'navPosition'          => 'nav_cc',
			'catAnimation'         => '',
			'titleAnimation'       => '',
			'exptAnimation'        => '',
			'btnAnimation'         => '',
			'showNavigation'       => true,
			'showPagination'       => true,
			'customNavIcon'        => false,
			'navIconSource'        => 'library',
			'navPrevIcon'          => 'arrowLeft',
			'navNextIcon'          => 'arrowRight',
			'customPrevSVG'        => '',
			'customNextSVG'        => '',
			'excerptLength'        => 20,
			'enableRemoteNav'      => false,
			'remotePrevSelector'   => '',
			'remoteNextSelector'   => '',
			'detachContent'        => false,
			'showThumbs'           => false,
			'thumbContent'         => 'image',
			'thumbTitlePosition'   => 'below',
			'hideWhenEmpty'        => true,
			'emptyMessage'         => '',
			'showDate'             => false,
			'showAuthor'           => false,
			'showAvatar'           => false,
			'showReadingTime'      => false,
			'showComments'         => false,
			'cardLink'             => false,
			'imageSize'            => 'full',
			'preset'               => '',
			'catStyle'             => '',
			'catPosition'          => 'above-title',
			'highlightCurrent'     => false,
			'schemaMarkup'         => false,
			'loadMore'             => false,
			'loadMoreMax'          => 30,
			'infiniteLoop'         => true,
		);

		$atts = wp_parse_args( $attributes, $defaults );
		foreach ( $atts as $key => $value ) {
			if ( is_scalar( $value ) || null === $value ) {
				$atts[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		$atts['sliderOptions'] = isset( $attributes['sliderOptions'] ) ? $attributes['sliderOptions'] : array();
		$atts['thumbsOptions'] = isset( $attributes['thumbsOptions'] ) ? $attributes['thumbsOptions'] : array();
		$atts['focusPoints']   = isset( $attributes['focusPoints'] ) && is_array( $attributes['focusPoints'] ) ? $attributes['focusPoints'] : array();
		$atts['preset']        = in_array( $atts['preset'], self::DESIGNS, true ) ? $atts['preset'] : '';

		return $atts;
	}

	/**
	 * Post type to query: any public post type, 'post' otherwise.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function post_type( $attributes ) {
		$type = isset( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : 'post';
		return ( $type && post_type_exists( $type ) && is_post_type_viewable( $type ) && 'attachment' !== $type ) ? $type : 'post';
	}

	/**
	 * Taxonomies of a post type that can filter or label slides.
	 *
	 * @param string $post_type Post type.
	 * @return string[]
	 */
	public static function taxonomies_for( $post_type ) {
		$names = array();
		foreach ( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy ) {
			if ( $taxonomy->public && $taxonomy->show_ui && 'post_format' !== $taxonomy->name ) {
				$names[] = $taxonomy->name;
			}
		}
		return $names;
	}

	/**
	 * Taxonomies whose terms label a slide: the ones the slider is filtered by, else the
	 * post type's categories (core `category`, or its first hierarchical taxonomy).
	 *
	 * @param array $attributes Block attributes.
	 * @return string[]
	 */
	public static function label_taxonomies( $attributes ) {
		$query_type = isset( $attributes['queryType'] ) ? sanitize_key( $attributes['queryType'] ) : 'latest_posts';
		if ( 'specific_categories' === $query_type && ! empty( $attributes['taxQuery'] ) && is_array( $attributes['taxQuery'] ) ) {
			return array_map( 'sanitize_key', array_keys( $attributes['taxQuery'] ) );
		}

		$post_type  = self::post_type( $attributes );
		$taxonomies = self::taxonomies_for( $post_type );
		// The post type's own categories first (e.g. product_cat), then any hierarchical one.
		foreach ( array( 'category', $post_type . '_cat', $post_type . '_category' ) as $preferred ) {
			if ( in_array( $preferred, $taxonomies, true ) ) {
				return array( $preferred );
			}
		}
		foreach ( $taxonomies as $taxonomy ) {
			if ( is_taxonomy_hierarchical( $taxonomy ) ) {
				return array( $taxonomy );
			}
		}
		return $taxonomies ? array( $taxonomies[0] ) : array();
	}

	/* ---------------------------------------------------------------------
	 * Query
	 * ------------------------------------------------------------------ */

	/**
	 * Term ids from a saved term list: `[{ value, label }]` as the editor saves it, or plain ids.
	 *
	 * @param mixed $terms Saved terms.
	 * @return int[]
	 */
	private static function term_ids( $terms ) {
		if ( ! is_array( $terms ) ) {
			return array();
		}
		$ids = array_map(
			static function ( $term ) {
				return absint( is_array( $term ) ? ( $term['value'] ?? 0 ) : $term );
			},
			$terms
		);
		return array_values( array_filter( $ids ) );
	}

	/**
	 * WP_Query arguments from the block's saved query settings.
	 *
	 * @param array $attributes Block attributes.
	 * @param int   $current    Post the slider is shown on (0 if none).
	 * @return array
	 */
	public static function query_args( $attributes, $current = 0 ) {
		$query_type = isset( $attributes['queryType'] ) ? sanitize_key( $attributes['queryType'] ) : 'latest_posts';
		$post_type  = self::post_type( $attributes );
		$total      = isset( $attributes['totalPosts'] ) ? (int) $attributes['totalPosts'] : 3;
		$orderby    = isset( $attributes['postOrderBy'] ) ? sanitize_key( $attributes['postOrderBy'] ) : 'date';
		$order      = isset( $attributes['postOrder'] ) && 'asc' === strtolower( (string) $attributes['postOrder'] ) ? 'ASC' : 'DESC';

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			// An empty "Total Posts" meant every post; cap it like the REST API does.
			'posts_per_page'      => $total > 0 ? min( $total, 100 ) : 100,
			'orderby'             => in_array( $orderby, array( 'date', 'title', 'modified', 'comment_count', 'rand' ), true ) ? $orderby : 'date',
			'order'               => $order,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		$is_specific = 'specific_posts' === $query_type && ! empty( $attributes['specificPosts'] ) && is_array( $attributes['specificPosts'] );
		$not_in      = array();

		// Skip the first N posts (not for hand-picked posts).
		$offset = isset( $attributes['postOffset'] ) ? absint( $attributes['postOffset'] ) : 0;
		if ( $offset && ! $is_specific ) {
			$args['offset'] = $offset;
		}

		// Leave out the post this slider is shown on (always, in Related mode).
		if ( $current && ( ! empty( $attributes['excludeCurrent'] ) || 'related' === $query_type ) ) {
			$not_in[] = (int) $current;
		}

		// Sticky posts: normal place (default), first (see self::query_ids()), or hidden.
		if ( ! $is_specific && isset( $attributes['stickyPosts'] ) && 'exclude' === $attributes['stickyPosts'] ) {
			$not_in = array_merge( $not_in, array_map( 'absint', (array) get_option( 'sticky_posts', array() ) ) );
		}

		if ( $not_in ) {
			$args['post__not_in'] = array_values( array_unique( $not_in ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		}

		if ( ! empty( $attributes['onlyWithImage'] ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			);
		}

		$tax_query = array();

		if ( $is_specific ) {
			$ids = array_filter( array_map( 'absint', wp_list_pluck( $attributes['specificPosts'], 'value' ) ) );
			if ( $ids ) {
				$args['post__in']       = $ids;
				$args['posts_per_page'] = count( $ids );
				// "Picked order": the order the posts were picked in.
				if ( 'post__in' === $orderby ) {
					$args['orderby'] = 'post__in';
				}
			}
		}

		if ( 'specific_categories' === $query_type ) {
			// `taxQuery` ({ taxonomy: [{ label, value }] }) covers every taxonomy;
			// `specificCategories` is the legacy core-category selection.
			$selected = isset( $attributes['taxQuery'] ) && is_array( $attributes['taxQuery'] ) && ! empty( $attributes['taxQuery'] )
				? $attributes['taxQuery']
				: ( ! empty( $attributes['specificCategories'] ) && is_array( $attributes['specificCategories'] ) ? array( 'category' => $attributes['specificCategories'] ) : array() );

			foreach ( $selected as $taxonomy => $terms ) {
				$taxonomy = sanitize_key( $taxonomy );
				$term_ids = self::term_ids( $terms );
				if ( $term_ids && taxonomy_exists( $taxonomy ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term_ids,
					);
				}
			}
		}

		// Related: posts sharing any of the current post's terms.
		if ( 'related' === $query_type && $current ) {
			$related = array();
			foreach ( self::related_taxonomies( $attributes, $post_type ) as $taxonomy ) {
				$term_ids = wp_get_post_terms( $current, $taxonomy, array( 'fields' => 'ids' ) );
				if ( $term_ids && ! is_wp_error( $term_ids ) ) {
					$related[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term_ids,
					);
				}
			}
			if ( $related ) {
				$tax_query[] = array_merge( array( 'relation' => 'OR' ), $related );
			}
		}

		// Leave out posts in these terms, for any query but hand-picked posts.
		if ( ! $is_specific && ! empty( $attributes['excludeTerms'] ) && is_array( $attributes['excludeTerms'] ) ) {
			foreach ( $attributes['excludeTerms'] as $taxonomy => $terms ) {
				$taxonomy = sanitize_key( $taxonomy );
				$term_ids = self::term_ids( $terms );
				if ( $term_ids && taxonomy_exists( $taxonomy ) ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term_ids,
						'operator' => 'NOT IN',
					);
				}
			}
		}

		if ( $tax_query ) {
			// Every group must match (same as the REST query the editor previews with).
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		/**
		 * Filters the query behind a Blog Post slider.
		 *
		 * @param array $args       WP_Query arguments.
		 * @param array $attributes Block attributes.
		 */
		return apply_filters( 'gutslider_post_slider_query_args', $args, $attributes );
	}

	/**
	 * Taxonomies Related mode matches on.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $post_type  Post type.
	 * @return string[]
	 */
	public static function related_taxonomies( $attributes, $post_type ) {
		$available = self::taxonomies_for( $post_type );
		if ( 'post' === $post_type ) {
			$by = isset( $attributes['relatedBy'] ) ? sanitize_key( $attributes['relatedBy'] ) : 'both';
			$wanted = 'category' === $by ? array( 'category' ) : ( 'post_tag' === $by ? array( 'post_tag' ) : array( 'category', 'post_tag' ) );
			return array_values( array_intersect( $wanted, $available ) );
		}
		return $available;
	}

	/**
	 * Post ids for a page of the slider (page 0 = what the page shows first),
	 * cached until any post, term, comment or sticky post changes.
	 *
	 * @param array $attributes Block attributes.
	 * @param int   $current    Post the slider is shown on.
	 * @param int   $page       Page (for "Load more").
	 * @return array { ids: int[], more: bool }
	 */
	public static function query_ids( $attributes, $current = 0, $page = 0 ) {
		$args     = self::query_args( $attributes, $current );
		$per_page = (int) $args['posts_per_page'];
		$offset   = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
		$sticky   = isset( $attributes['stickyPosts'] ) && 'first' === $attributes['stickyPosts'] && empty( $args['post__in'] );
		$max      = ! empty( $attributes['loadMore'] ) ? max( $per_page, min( 100, absint( $attributes['loadMoreMax'] ?? 30 ) ) ) : $per_page;

		// Hand-picked posts have no further pages.
		if ( $page > 0 && ! empty( $args['post__in'] ) && ! $sticky ) {
			return array( 'ids' => array(), 'more' => false );
		}

		$use_cache = 'rand' !== $args['orderby'] && apply_filters( 'gutslider_post_slider_use_cache', true, $attributes );
		$cache_key = 'gs_ps_' . md5( wp_json_encode( array( $args, $sticky, $page, $max, self::cache_version() ) ) );
		if ( $use_cache ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) && isset( $cached['ids'] ) ) {
				return $cached;
			}
		}

		$start = $offset + $page * $per_page;
		$want  = $per_page + 1; // One extra tells whether there is more.

		if ( $sticky ) {
			$sticky_ids = array_filter( array_map( 'absint', (array) get_option( 'sticky_posts', array() ) ) );
			$lookup     = array_merge( $args, array( 'fields' => 'ids', 'offset' => 0, 'posts_per_page' => $start + $want, 'suppress_filters' => false ) );
			$stickies   = $sticky_ids ? get_posts( array_merge( $lookup, array( 'post__in' => $sticky_ids ) ) ) : array();
			$others     = get_posts( array_merge( $lookup, array( 'post__not_in' => array_merge( $args['post__not_in'] ?? array(), $sticky_ids ) ) ) );
			$ids        = array_slice( array_merge( $stickies, $others ), $start, $want );
		} else {
			$query = new \WP_Query( array_merge( $args, array( 'fields' => 'ids', 'offset' => $start, 'posts_per_page' => $want ) ) );
			$ids   = array_map( 'intval', $query->posts );
		}

		$more = count( $ids ) > $per_page;
		$ids  = array_slice( $ids, 0, $per_page );

		// "Load more" stops at the chosen maximum.
		$shown_after = ( $page + 1 ) * $per_page;
		if ( $shown_after >= $max ) {
			$ids  = array_slice( $ids, 0, max( 0, $max - $page * $per_page ) );
			$more = false;
		}

		$result = array(
			'ids'  => array_map( 'intval', $ids ),
			'more' => $more && ! empty( $attributes['loadMore'] ),
		);

		if ( $use_cache ) {
			set_transient( $cache_key, $result, (int) apply_filters( 'gutslider_post_slider_cache_ttl', 12 * HOUR_IN_SECONDS ) );
		}

		return $result;
	}

	/**
	 * Everything a slide needs, for the given post ids (in that order).
	 *
	 * @param int[] $ids        Post ids.
	 * @param array $attributes Block attributes.
	 * @param int   $current    Post the slider is shown on.
	 * @return array[]
	 */
	public static function items( $ids, $attributes, $current = 0 ) {
		if ( ! $ids ) {
			return array();
		}

		$taxonomies     = self::label_taxonomies( $attributes );
		$excerpt_length = isset( $attributes['excerptLength'] ) ? (int) $attributes['excerptLength'] : 20;
		// Plain text only, but keep a leading space (" Read more").
		$excerpt_ending = isset( $attributes['excerptEnding'] ) ? wp_kses( (string) $attributes['excerptEnding'], array() ) : '...';

		$query = new \WP_Query(
			array(
				'post_type'           => 'any',
				'post_status'         => 'publish',
				'post__in'            => $ids,
				'orderby'             => 'post__in',
				'posts_per_page'      => count( $ids ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$items = array();
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();

			$terms = array();
			foreach ( $taxonomies as $taxonomy ) {
				$post_terms = get_the_terms( $post_id, $taxonomy );
				if ( $post_terms && ! is_wp_error( $post_terms ) ) {
					$terms = array_merge( $terms, $post_terms );
				}
			}

			// Excerpt: the post's own excerpt, or its opening words when it has none,
			// cut to the chosen length with the chosen ending. Protected posts keep it private.
			if ( post_password_required() ) {
				$excerpt = '';
			} elseif ( $excerpt_length > 0 ) {
				$source  = has_excerpt() ? get_the_excerpt() : wp_strip_all_tags( excerpt_remove_blocks( strip_shortcodes( get_the_content() ) ) );
				$excerpt = wp_trim_words( $source, $excerpt_length, $excerpt_ending );
			} else {
				$excerpt = apply_filters( 'the_excerpt', get_the_excerpt() );
			}

			$author_id = (int) get_post_field( 'post_author', $post_id );
			$words     = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) ), -1, PREG_SPLIT_NO_EMPTY ) );

			$items[] = array(
				'id'         => $post_id,
				'type'       => get_post_type(),
				'title'      => get_the_title(),
				'excerpt'    => $excerpt,
				'link'       => get_permalink(),
				'image_id'   => (int) get_post_thumbnail_id(),
				'terms'      => $terms,
				'date'       => get_the_date(),
				'date_iso'   => get_the_date( 'c' ),
				'modified'   => get_the_modified_date( 'c' ),
				'author'     => $author_id ? get_the_author_meta( 'display_name', $author_id ) : '',
				'author_id'  => $author_id,
				// About 200 words a minute; at least one minute.
				'reading'    => max( 1, (int) ceil( $words / 200 ) ),
				'comments'   => (int) get_comments_number(),
				'is_current' => $current && $post_id === (int) $current,
			);
		}
		wp_reset_postdata();

		return $items;
	}

	/**
	 * Whether the slide background was chosen by the user (not the untouched #efefef default).
	 * Featured + List and Timeline only show a chosen one, as a card. Keep in sync with
	 * hasCustomSlideBg() in presets.js.
	 *
	 * @param array $attributes Block attributes.
	 * @return bool
	 */
	public static function has_custom_slide_bg( $attributes ) {
		if ( isset( $attributes['bgType'] ) && 'gradient' === $attributes['bgType'] ) {
			return ! empty( $attributes['bgGradient'] );
		}
		if ( ! empty( $attributes['bgImage']['url'] ) ) {
			return true;
		}
		$color = isset( $attributes['bgColor'] ) ? strtolower( (string) $attributes['bgColor'] ) : '';
		return '' !== $color && '#efefef' !== $color;
	}

	/**
	 * Badge colour of a term: its own (set on the term screen), else one from the palette.
	 *
	 * @param \WP_Term $term Term.
	 * @return string Hex colour.
	 */
	public static function term_color( $term ) {
		$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'gutslider_color', true ) );
		return $color ? $color : self::BADGE_PALETTE[ $term->term_id % count( self::BADGE_PALETTE ) ];
	}

	/* ---------------------------------------------------------------------
	 * Markup
	 * ------------------------------------------------------------------ */

	/**
	 * Post the slider is shown on (the post whose content or template holds it).
	 *
	 * @return int
	 */
	public static function current_post_id() {
		$id = get_the_ID();
		if ( ! $id && is_singular() ) {
			$id = get_queried_object_id();
		}
		return (int) $id;
	}

	/**
	 * Whole block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string HTML.
	 */
	public static function render( $attributes ) {
		$atts    = self::normalize( $attributes );
		$current = self::current_post_id();
		$result  = self::query_ids( $attributes, $current );
		$items   = self::items( $result['ids'], $attributes, $current );

		// Nothing matched: hide the block (default), or say so.
		if ( empty( $items ) ) {
			if ( $atts['hideWhenEmpty'] ) {
				return '';
			}
			return sprintf(
				'<div %1$s><p class="gutslider-post-empty">%2$s</p></div>',
				get_block_wrapper_attributes( array( 'class' => esc_attr( $atts['uniqueId'] ) ) ),
				esc_html( '' !== $atts['emptyMessage'] ? $atts['emptyMessage'] : __( 'No posts found.', 'slider-blocks' ) )
			);
		}

		$show_thumbs = '1' === $atts['showThumbs'] && ! in_array( $atts['preset'], self::NO_THUMBS_DESIGNS, true );

		// "Load more" needs the block in this post's own content (the endpoint looks it up
		// there) and no infinite loop.
		$load_more = $result['more'] && $atts['uniqueId'] && $current
			&& ! $atts['infiniteLoop']
			&& ! in_array( $atts['preset'], self::NO_LOAD_MORE_DESIGNS, true )
			&& false !== strpos( (string) get_post_field( 'post_content', $current ), '"uniqueId":"' . $atts['uniqueId'] . '"' );

		$slider_options = $atts['sliderOptions'];
		if ( is_array( $slider_options ) && isset( self::PRESET_SWIPER_OPTIONS[ $atts['preset'] ] ) ) {
			foreach ( self::PRESET_SWIPER_OPTIONS[ $atts['preset'] ] as $key => $value ) {
				if ( null === $value ) {
					unset( $slider_options[ $key ] );
				} else {
					$slider_options[ $key ] = $value;
				}
			}
		}

		$block_classes = array(
			esc_attr( $atts['uniqueId'] ),
			esc_attr( $atts['navContainerPosition'] ),
			esc_attr( $atts['navPosition'] ),
			$atts['preset'] ? 'gutslider-preset-' . $atts['preset'] : '',
			'badge' === $atts['catStyle'] ? 'has-category-badges' : '',
			in_array( $atts['preset'], array( 'featured', 'timeline' ), true ) && self::has_custom_slide_bg( $attributes ) ? 'has-slide-bg' : '',
		);
		$block_props = get_block_wrapper_attributes( array( 'class' => implode( ' ', array_filter( $block_classes ) ) ) );

		ob_start();
		?>
		<div <?php echo wp_kses_data( $block_props ); ?> data-swiper-options="<?php echo esc_attr( wp_json_encode( $slider_options ) ); ?>"
			<?php
			if ( $show_thumbs ) {
				echo 'data-thumbs-options="' . esc_attr( wp_json_encode( $atts['thumbsOptions'] ) ) . '" ';
			}
			if ( $atts['enableRemoteNav'] ) {
				echo 'data-rprev="' . esc_attr( $atts['remotePrevSelector'] ) . '" ';
				echo 'data-rnext="' . esc_attr( $atts['remoteNextSelector'] ) . '" ';
			}
			if ( $load_more ) {
				printf(
					'data-load-more="%1$s" data-post="%2$d" data-block="%3$s" data-label-loading="%4$s" data-label-loaded="%5$s" ',
					esc_url( rest_url( 'gutslider/v1/post-slider/more' ) ),
					(int) $current,
					esc_attr( $atts['uniqueId'] ),
					esc_attr__( 'Loading…', 'slider-blocks' ),
					/* translators: %d: number of posts just added. */
					esc_attr__( '%d more posts loaded', 'slider-blocks' )
				);
			}
			?>
		>
				<div class="swiper">
					<div class="swiper-wrapper">
						<?php echo self::render_slides( $items, $atts, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<?php
					if ( '1' === $atts['showNavigation'] && 'nav_inside' === $atts['navContainerPosition'] && '1' !== $atts['enableRemoteNav'] ) {
						echo self::render_nav( $atts, 'nav_inside' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					if ( '1' === $atts['showPagination'] ) {
						echo '<div class="swiper-pagination"></div>';
					}
					?>
				</div>
				<?php
				if ( '1' === $atts['showNavigation'] && 'nav_outside' === $atts['navContainerPosition'] && '1' !== $atts['enableRemoteNav'] ) {
					echo self::render_nav( $atts, 'nav_outside' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				if ( $show_thumbs ) {
					echo self::render_thumbs( $items, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				if ( 'featured' === $atts['preset'] ) {
					echo self::render_list( $items, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				if ( $load_more ) {
					echo '<span class="gs-post-more__status screen-reader-text" role="status" aria-live="polite"></span>';
				}
				?>
			</div>
		<?php
		$html = ob_get_clean();

		if ( '1' === $atts['schemaMarkup'] ) {
			$html .= self::schema( $items );
		}

		return $html;
	}

	/**
	 * Slides (also used by the "Load more" endpoint).
	 *
	 * @param array[] $items Slides data.
	 * @param array   $atts  Normalised attributes.
	 * @param int     $start Position of the first slide in the whole list.
	 * @return string HTML.
	 */
	public static function render_slides( $items, $atts, $start = 0 ) {
		// Responsive images: slides fill the slider in "slider" mode, share the row otherwise.
		$image_sizes = 'slider' === $atts['sliderType'] && ! $atts['preset'] ? '100vw' : '(max-width: 767px) 100vw, 50vw';

		// Largest image to use. Browsers still pick smaller ones through srcset.
		$image_size = in_array( $atts['imageSize'], array( 'medium', 'large', 'full' ), true ) ? $atts['imageSize'] : 'full';
		$cap_srcset = null;
		if ( 'full' !== $image_size ) {
			$cap_width  = (int) get_option( $image_size . '_size_w' );
			$cap_srcset = static function ( $sources ) use ( $cap_width ) {
				return $cap_width ? array_filter( $sources, static fn( $width ) => $width <= $cap_width, ARRAY_FILTER_USE_KEY ) : $sources;
			};
			add_filter( 'wp_calculate_image_srcset', $cap_srcset );
		}

		$html = '';
		if ( 'grid' !== $atts['preset'] ) {
			foreach ( $items as $i => $item ) {
				$html .= self::render_slide( $item, $atts, $start + $i + 1, $image_size, $image_sizes );
			}
		} else {
			// Overlay Grid slides whole pages of tiles.
			foreach ( array_chunk( $items, self::GRID_PAGE_SIZE ) as $p => $page ) {
				$html .= '<div class="swiper-slide gs-post-page is-count-' . count( $page ) . '">';
				foreach ( $page as $i => $item ) {
					$html .= self::render_slide( $item, $atts, $start + $p * self::GRID_PAGE_SIZE + $i + 1, $image_size, $image_sizes );
				}
				$html .= '</div>';
			}
		}

		if ( $cap_srcset ) {
			remove_filter( 'wp_calculate_image_srcset', $cap_srcset );
		}

		return $html;
	}

	/**
	 * One slide.
	 *
	 * @param array  $item        Slide data.
	 * @param array  $atts        Normalised attributes.
	 * @param int    $index       1-based position.
	 * @param string $image_size  Image size.
	 * @param string $image_sizes `sizes` attribute.
	 * @return string HTML.
	 */
	private static function render_slide( $item, $atts, $index, $image_size, $image_sizes ) {
		$post_title    = $item['title'];
		$post_link     = $item['link'];
		$post_image_id = $item['image_id'];
		$post_image    = $post_image_id ? wp_get_attachment_image_url( $post_image_id, $image_size ) : '';
		// The first slide is likely the largest thing on screen: load it right away.
		$image_loading = 1 === $index ? 'eager' : 'lazy';
		$has_meta      = $atts['showDate'] || $atts['showAuthor'] || $atts['showReadingTime'] || $atts['showComments'];
		$is_current    = $item['is_current'] && '1' === $atts['highlightCurrent'];
		$badges        = 'badge' === $atts['catStyle'];
		// Badges sit on the image only when there is one; otherwise above the title.
		$cats_on_image = $badges && $post_image && ! $atts['detachContent'] && in_array( $atts['catPosition'], array( 'top-left', 'top-right' ), true );

		$focus           = $atts['focusPoints'][ $index - 1 ] ?? null;
		$object_position = isset( $focus['x'], $focus['y'] )
			? sprintf( 'object-position: %d%% %d%%;', round( $focus['x'] * 100 ), round( $focus['y'] * 100 ) )
			: 'object-position: center center;';

		$classes = array( 'swiper-slide' );
		if ( 'slider' === $atts['sliderType'] ) {
			$classes[] = 'slide-mode';
		}
		if ( ! empty( $atts['detachContent'] ) ) {
			$classes[] = 'detach-content';
		}
		if ( ! $post_image && $atts['preset'] ) {
			$classes[] = 'no-image';
		}
		if ( $is_current ) {
			$classes[] = 'is-current';
		}

		$categories = '';
		if ( $atts['showCat'] && ! empty( $item['terms'] ) ) {
			$categories .= '<div class="post-categories ' . esc_attr( $atts['catAnimation'] ) . ( $cats_on_image ? ' is-on-image is-' . esc_attr( $atts['catPosition'] ) : '' ) . '">';
			foreach ( $item['terms'] as $term ) {
				if ( $badges ) {
					$categories .= sprintf( '<span class="post-category is-badge" style="--gs-badge:%1$s">%2$s</span>', esc_attr( self::term_color( $term ) ), esc_html( $term->name ) );
				} else {
					$categories .= sprintf( '<span class="post-category">%s</span>', esc_html( $term->name ) );
				}
			}
			$categories .= '</div>';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>>
			<div class="swiper-container-outer">
				<?php
				// Featured image as a real, responsive image layer: srcset, lazy loading, focal point.
				if ( ! $atts['detachContent'] && $post_image ) {
					echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$post_image_id,
						$image_size,
						false,
						array(
							'class'    => 'gutslider-post-bg',
							// Decorative: the title right on top already names the slide.
							'alt'      => '',
							'sizes'    => $image_sizes,
							'loading'  => $image_loading,
							'decoding' => 'async',
							'style'    => $object_position,
						)
					);
				}
				?>
				<?php if ( $atts['enableOverlay'] ) : ?>
					<div class="gutslider-overlay"></div>
				<?php endif; ?>
				<?php if ( $atts['cardLink'] ) : ?>
					<?php // Whole slide clickable. Hidden from keyboards and screen readers: the title and button links cover them. ?>
					<a class="post-card-link" href="<?php echo esc_url( $post_link ); ?>" tabindex="-1" aria-hidden="true"></a>
				<?php endif; ?>
				<?php if ( $is_current ) : ?>
					<span class="post-now-reading"><?php esc_html_e( 'Now reading', 'slider-blocks' ); ?></span>
				<?php endif; ?>
				<?php
				if ( $cats_on_image ) {
					echo $categories; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				if ( $atts['detachContent'] && $post_image ) {
					$image_alt = get_post_meta( $post_image_id, '_wp_attachment_image_alt', true );
					?>
					<a class="detached-featured-image" href="<?php echo esc_url( $post_link ); ?>">
						<?php
						echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$post_image_id,
							$image_size,
							false,
							array(
								// The image is a link to the post, so it needs a name.
								'alt'      => $image_alt ? $image_alt : wp_strip_all_tags( $post_title ),
								'sizes'    => $image_sizes,
								'loading'  => $image_loading,
								'decoding' => 'async',
								'style'    => $object_position,
							)
						);
						?>
					</a>
					<?php
				}
				?>
				<div class="gutslider-content-wrapper<?php echo $atts['detachContent'] ? ' detach-content' : ''; ?>">
					<div class="gutslider-content-inner">
						<?php
						if ( ! $cats_on_image ) {
							echo $categories; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>

						<?php
						if ( $atts['showTitle'] ) {
							$title_tag     = tag_escape( $atts['titleTag'] );
							$title_content = sprintf(
								'<%1$s class="post-title %2$s">%3$s</%1$s>',
								$title_tag,
								esc_attr( $atts['titleAnimation'] ),
								esc_html( $post_title )
							);

							if ( $atts['linkTitle'] ) {
								printf(
									'<a href="%s" class="post-title-link">%s</a>',
									esc_url( $post_link ),
									$title_content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								);
							} else {
								echo wp_kses_post( $title_content );
							}
						}
						?>

						<?php if ( $has_meta ) : ?>
							<div class="post-meta">
								<?php if ( $atts['showAuthor'] && $item['author'] ) : ?>
									<span class="post-meta__author">
										<?php
										if ( $atts['showAvatar'] ) {
											echo get_avatar( $item['author_id'], 48, '', '', array( 'class' => 'post-meta__avatar', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
										?>
										<span><?php echo esc_html( $item['author'] ); ?></span>
									</span>
								<?php endif; ?>
								<?php if ( $atts['showDate'] ) : ?>
									<time class="post-meta__date" datetime="<?php echo esc_attr( $item['date_iso'] ); ?>"><?php echo esc_html( $item['date'] ); ?></time>
								<?php endif; ?>
								<?php if ( $atts['showReadingTime'] ) : ?>
									<span class="post-meta__reading">
										<?php
										/* translators: %d: minutes. */
										echo esc_html( sprintf( _n( '%d min read', '%d min read', $item['reading'], 'slider-blocks' ), $item['reading'] ) );
										?>
									</span>
								<?php endif; ?>
								<?php if ( $atts['showComments'] ) : ?>
									<span class="post-meta__comments">
										<?php
										/* translators: %s: number of comments. */
										echo esc_html( sprintf( _n( '%s comment', '%s comments', $item['comments'], 'slider-blocks' ), number_format_i18n( $item['comments'] ) ) );
										?>
									</span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( $atts['showExcerpt'] ) : ?>
							<div class="post-excerpt <?php echo esc_attr( $atts['exptAnimation'] ); ?>">
								<?php echo wp_kses_post( $item['excerpt'] ); ?>
							</div>
						<?php endif; ?>

						<?php if ( $atts['showBtn'] ) : ?>
							<div class="post-cta-wrapper <?php echo esc_attr( $atts['btnAnimation'] ); ?>">
								<a href="<?php echo esc_url( $post_link ); ?>" class="post-cta" aria-label="<?php
									/* translators: 1: button label, e.g. "Read More"; 2: post title. */
									echo esc_attr( sprintf( __( '%1$s: %2$s', 'slider-blocks' ), $atts['btnLabel'], wp_strip_all_tags( $post_title ) ) );
								?>">
									<?php echo esc_html( $atts['btnLabel'] ); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Arrows.
	 *
	 * @param array  $atts      Normalised attributes.
	 * @param string $container nav_inside | nav_outside.
	 * @return string HTML.
	 */
	private static function render_nav( $atts, $container ) {
		ob_start();
		?>
		<div class="gutslider-nav <?php echo esc_attr( $container . ' ' . $atts['navPosition'] ); ?>">
			<?php if ( $atts['customNavIcon'] ) : ?>
				<div class="gutslider-prev">
					<?php
					if ( 'library' === $atts['navIconSource'] ) {
						echo wp_kses( gutslider_display_icon( $atts['navPrevIcon'] ), gutslider_allowed_svg_tags() );
					} else {
						echo wp_kses( $atts['customPrevSVG'], gutslider_allowed_svg_tags() );
					}
					?>
				</div>
				<div class="gutslider-next">
					<?php
					if ( 'library' === $atts['navIconSource'] ) {
						echo wp_kses( gutslider_display_icon( $atts['navNextIcon'] ), gutslider_allowed_svg_tags() );
					} else {
						echo wp_kses( $atts['customNextSVG'], gutslider_allowed_svg_tags() );
					}
					?>
				</div>
			<?php else : ?>
				<div class="swiper-button-prev"></div>
				<div class="swiper-button-next"></div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Featured + List: the list beside the lead story, which it navigates.
	 *
	 * @param array[] $items Slides data.
	 * @param array   $atts  Normalised attributes.
	 * @return string HTML.
	 */
	private static function render_list( $items, $atts ) {
		ob_start();
		?>
		<div class="gs-post-list" role="group" aria-label="<?php esc_attr_e( 'Choose a story', 'slider-blocks' ); ?>">
			<?php
			foreach ( $items as $index => $item ) :
				$is_current = $item['is_current'] && '1' === $atts['highlightCurrent'];
				?>
				<button type="button" class="gs-post-list__item<?php echo 0 === $index ? ' is-active' : ''; ?><?php echo $is_current ? ' is-current' : ''; ?>"<?php echo 0 === $index ? ' aria-current="true"' : ''; ?>>
					<?php if ( $item['image_id'] ) : ?>
						<span class="gs-post-list__thumb">
							<?php echo wp_get_attachment_image( $item['image_id'], 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
					<?php endif; ?>
					<span class="gs-post-list__text">
						<?php if ( $atts['showCat'] && ! empty( $item['terms'] ) ) : ?>
							<span class="gs-post-list__cat" style="--gs-badge:<?php echo esc_attr( self::term_color( $item['terms'][0] ) ); ?>"><?php echo esc_html( $item['terms'][0]->name ); ?></span>
						<?php endif; ?>
						<span class="gs-post-list__title"><?php echo esc_html( wp_strip_all_tags( $item['title'] ) ); ?></span>
						<?php if ( $is_current ) : ?>
							<span class="gs-post-list__now"><?php esc_html_e( 'Now reading', 'slider-blocks' ); ?></span>
						<?php endif; ?>
						<?php if ( $atts['showDate'] ) : ?>
							<time class="gs-post-list__date" datetime="<?php echo esc_attr( $item['date_iso'] ); ?>"><?php echo esc_html( $item['date'] ); ?></time>
						<?php endif; ?>
					</span>
					<span class="gs-post-list__progress" aria-hidden="true"></span>
				</button>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Thumbnail strip.
	 *
	 * @param array[] $items Slides data.
	 * @param array   $atts  Normalised attributes.
	 * @return string HTML.
	 */
	private static function render_thumbs( $items, $atts ) {
		$classes = array( 'swiper', 'gutslider-thumbs', 'thumb-content-' . $atts['thumbContent'] );
		if ( 'both' === $atts['thumbContent'] ) {
			$classes[] = 'thumb-title-' . $atts['thumbTitlePosition'];
		}
		$placeholder = GUTSLIDER_URL . 'assets/images/placeholder.svg';

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<div class="swiper-wrapper">
				<?php
				foreach ( $items as $item ) {
					$thumb_url = $item['image_id'] ? wp_get_attachment_image_url( $item['image_id'], 'medium' ) : '';
					$thumb_alt = $item['image_id'] ? get_post_meta( $item['image_id'], '_wp_attachment_image_alt', true ) : '';
					?>
					<div class="swiper-slide">
						<?php if ( 'title' !== $atts['thumbContent'] ) : ?>
							<img src="<?php echo esc_url( $thumb_url ? $thumb_url : $placeholder ); ?>" alt="<?php echo esc_attr( $thumb_alt ); ?>" loading="lazy" decoding="async" />
						<?php endif; ?>
						<?php if ( 'image' !== $atts['thumbContent'] && $item['title'] ) : ?>
							<span class="gutslider-thumb-title"><?php echo wp_kses_post( $item['title'] ); ?></span>
						<?php endif; ?>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * JSON-LD: the slider as an ItemList of posts.
	 *
	 * @param array[] $items Slides data.
	 * @return string <script> element.
	 */
	public static function schema( $items ) {
		$list = array();
		foreach ( $items as $position => $item ) {
			$entry = array(
				'@type'         => 'post' === $item['type'] ? 'BlogPosting' : ( 'page' === $item['type'] ? 'WebPage' : 'CreativeWork' ),
				'headline'      => wp_strip_all_tags( $item['title'] ),
				'url'           => $item['link'],
				'datePublished' => $item['date_iso'],
				'dateModified'  => $item['modified'],
			);
			if ( $item['author'] ) {
				$entry['author'] = array(
					'@type' => 'Person',
					'name'  => $item['author'],
				);
			}
			if ( $item['image_id'] ) {
				$image = wp_get_attachment_image_url( $item['image_id'], 'large' );
				if ( $image ) {
					$entry['image'] = $image;
				}
			}
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $position + 1,
				'item'     => $entry,
			);
		}

		$data = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'itemListElement' => $list,
		);

		return '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
	}

	/* ---------------------------------------------------------------------
	 * "Load more" endpoint
	 * ------------------------------------------------------------------ */

	/**
	 * GET gutslider/v1/post-slider/more?post=ID&block=UNIQUE_ID&page=N
	 *
	 * Public and read only. The block's settings are read from the post it was saved
	 * in, never taken from the request, and only published, unprotected posts count.
	 */
	public function register_routes() {
		register_rest_route(
			'gutslider/v1',
			'/post-slider/more',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'more' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'post'  => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
					),
					'block' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_html_class',
					),
					'page'  => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
						'maximum'  => 50,
					),
				),
			)
		);
	}

	/**
	 * Next slides.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function more( $request ) {
		$post = get_post( (int) $request['post'] );
		if ( ! $post || 'publish' !== $post->post_status || post_password_required( $post ) || ! is_post_type_viewable( $post->post_type ) ) {
			return new \WP_Error( 'gutslider_not_found', __( 'Not found.', 'slider-blocks' ), array( 'status' => 404 ) );
		}

		$attributes = self::find_block( parse_blocks( $post->post_content ), (string) $request['block'] );
		if ( ! $attributes || empty( $attributes['loadMore'] ) ) {
			return new \WP_Error( 'gutslider_not_found', __( 'Not found.', 'slider-blocks' ), array( 'status' => 404 ) );
		}

		// Render as if on that post (related posts, "now reading", excluded current post).
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		$page   = (int) $request['page'];
		$result = self::query_ids( $attributes, $post->ID, $page );
		$items  = self::items( $result['ids'], $attributes, $post->ID );
		$atts   = self::normalize( $attributes );
		$per    = max( 1, (int) self::query_args( $attributes, $post->ID )['posts_per_page'] );
		$html   = self::render_slides( $items, $atts, $page * $per );

		wp_reset_postdata();

		return rest_ensure_response(
			array(
				'html'  => $html,
				'count' => count( $items ),
				'more'  => $result['more'],
			)
		);
	}

	/**
	 * Attributes of the Blog Post block with this unique id.
	 *
	 * @param array  $blocks    Parsed blocks.
	 * @param string $unique_id Block unique id.
	 * @return array|null
	 */
	private static function find_block( $blocks, $unique_id ) {
		foreach ( $blocks as $block ) {
			if ( 'gutsliders/post-slider' === $block['blockName'] && ( $block['attrs']['uniqueId'] ?? '' ) === $unique_id ) {
				return $block['attrs'];
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = self::find_block( $block['innerBlocks'], $unique_id );
				if ( $found ) {
					return $found;
				}
			}
		}
		return null;
	}

	/* ---------------------------------------------------------------------
	 * Cache
	 * ------------------------------------------------------------------ */

	/**
	 * Current cache version.
	 *
	 * @return string
	 */
	private static function cache_version() {
		return (string) get_option( self::CACHE_VERSION_OPTION, '1' );
	}

	/**
	 * Invalidate every cached slider result (old entries simply expire).
	 */
	public static function flush_cache() {
		update_option( self::CACHE_VERSION_OPTION, (string) microtime( true ), true );
	}

	/**
	 * Publishing, unpublishing or scheduling changes what sliders show.
	 *
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 */
	public function on_status_change( $new_status, $old_status ) {
		if ( $new_status !== $old_status && ( 'publish' === $new_status || 'publish' === $old_status ) ) {
			self::flush_cache();
		}
	}

	/**
	 * A featured image change matters ("Only posts with a featured image").
	 *
	 * @param mixed  $meta_ids  Meta ids.
	 * @param int    $object_id Post id.
	 * @param string $meta_key  Meta key.
	 */
	public function on_meta_change( $meta_ids, $object_id, $meta_key ) {
		if ( '_thumbnail_id' === $meta_key ) {
			self::flush_cache();
		}
	}
}
