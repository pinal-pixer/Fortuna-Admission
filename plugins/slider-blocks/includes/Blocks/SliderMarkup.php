<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tidies every GutSlider slider's HTML as it renders, free and Pro. Done while rendering, so
 * saved markup (and block validation) is untouched and sliders saved long ago benefit too.
 *
 * - Images that were saved without width, height or srcset get them from their attachment, so
 *   the browser keeps their space before they load (no layout shift), picks a size that fits,
 *   and WordPress can lazy-load the ones further down the page (it skips images without a size).
 * - A slider whose slides all use an H1 title keeps only the first: the others become H2, so the
 *   page has one main heading. They keep the H1's look: the theme's H1 typography (theme.json) is
 *   copied onto them, losing to the block's own typography. Block themes only, since a classic
 *   theme's stylesheet can't be read; there the H1s stay.
 *
 * @package GutSlider\Blocks
 * @since   3.5.0
 */
final class SliderMarkup {

	/**
	 * Blocks inside a slider; their HTML is handled with the slider's.
	 *
	 * @var string[]
	 */
	public const CHILD_BLOCKS = array(
		'gutsliders/slide',
		'gutsliders/gutslide',
		'gutsliders/gutcarousel',
		'gutsliders/backdrop-slide',
		'gutsliders/marquee-item',
	);

	/**
	 * Attachment IDs found for image URLs during this request.
	 *
	 * @var array<string, int>
	 */
	private array $ids = array();


	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'render_block', array( $this, 'render' ), 20, 2 );
	}

	/**
	 * Tidies a slider's HTML.
	 *
	 * @param string $content Block HTML.
	 * @param array  $block   Parsed block.
	 * @return string Block HTML.
	 */
	public function render( $content, $block ) {
		$name = $block['blockName'] ?? '';
		if ( is_admin() || ! is_string( $content ) || '' === $content || ! is_string( $name ) || 0 !== strpos( $name, 'gutsliders/' ) || in_array( $name, self::CHILD_BLOCKS, true ) ) {
			return $content;
		}

		if ( false !== stripos( $content, '<img' ) ) {
			$content = $this->size_images( $content );
		}
		if ( substr_count( strtolower( $content ), '<h1' ) > 1 && wp_is_block_theme() ) {
			$content = $this->one_h1( $content );
		}

		return $content;
	}

	/**
	 * Width, height, srcset and sizes for images saved without them.
	 *
	 * @param string $content Block HTML.
	 * @return string Block HTML.
	 */
	private function size_images( string $content ): string {
		return (string) preg_replace_callback(
			'/<img\b[^>]*>/i',
			function ( $match ) {
				$image = $match[0];
				$sized = preg_match( '/\swidth=["\']?\d/i', $image ) && preg_match( '/\sheight=["\']?\d/i', $image );
				if ( $sized && false !== stripos( $image, ' srcset=' ) ) {
					return $image;
				}
				$id = $this->attachment_id( $image );
				if ( ! $id ) {
					return $image;
				}
				if ( ! preg_match( '/\bwp-image-\d+\b/', $image ) ) {
					// lets WordPress find the attachment too (the_content's own image handling)
					$image = preg_match( '/\sclass=(["\'])/i', $image )
						? (string) preg_replace( '/\sclass=(["\'])/i', ' class=$1wp-image-' . $id . ' ', $image, 1 )
						: (string) preg_replace( '/^<img\b/i', '<img class="wp-image-' . $id . '"', $image, 1 );
				}
				if ( ! $sized ) {
					$image = wp_img_tag_add_width_and_height_attr( $image, 'gutslider', $id );
				}
				if ( false === stripos( $image, ' srcset=' ) ) {
					$image = wp_img_tag_add_srcset_and_sizes_attr( $image, 'gutslider', $id );
				}
				return $image;
			},
			$content
		);
	}

	/**
	 * The attachment an image tag shows: from its wp-image-{id} class, else from its URL.
	 *
	 * @param string $image Image tag.
	 * @return int Attachment ID, or 0.
	 */
	private function attachment_id( string $image ): int {
		if ( preg_match( '/\bwp-image-(\d+)\b/', $image, $match ) && wp_attachment_is_image( (int) $match[1] ) ) {
			return (int) $match[1];
		}
		if ( ! preg_match( '/\ssrc=(["\'])([^"\']+)\1/i', $image, $match ) ) {
			return 0;
		}
		$url = html_entity_decode( $match[2], ENT_QUOTES );
		// saved as /wp-content/uploads/...: WordPress only matches full URLs
		if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
			$url = home_url( $url );
		}
		// only this site's uploads can be attachments
		$uploads = wp_get_upload_dir();
		if ( empty( $uploads['baseurl'] ) || false === strpos( $url, (string) wp_parse_url( $uploads['baseurl'], PHP_URL_PATH ) ) ) {
			return 0;
		}
		if ( ! isset( $this->ids[ $url ] ) ) {
			$key = 'gs_att_' . md5( $url );
			$id  = wp_cache_get( $key, 'gutslider' );
			if ( false === $id ) {
				$id = attachment_url_to_postid( $url );
				wp_cache_set( $key, $id, 'gutslider', HOUR_IN_SECONDS );
			}
			$this->ids[ $url ] = (int) $id;
		}
		return $this->ids[ $url ];
	}

	/**
	 * Keeps the first H1 and turns the rest into H2s that look like it.
	 *
	 * @param string $content Block HTML.
	 * @return string Block HTML.
	 */
	private function one_h1( string $content ): string {
		$first = true;
		$html  = (string) preg_replace_callback(
			'#<h1\b([^>]*)>(.*?)</h1>#is',
			function ( $match ) use ( &$first ) {
				if ( $first ) {
					$first = false;
					return $match[0];
				}
				return '<h2 data-gs-h1' . $match[1] . '>' . $match[2] . '</h2>';
			},
			$content
		);

		if ( ! wp_style_is( 'gutslider-h1', 'enqueued' ) ) {
			\GutSlider\Assets\RuntimeStyles::queue( 'gutslider-h1', 'h2[data-gs-h1]{' . $this->h1_typography() . '}' );
		}
		return $html;
	}

	/**
	 * The theme's H1 typography as CSS declarations (from theme.json: the H1 element, then all
	 * headings), with the browser's own H1 size when the theme sets none.
	 *
	 * @return string CSS declarations.
	 */
	private function h1_typography(): string {
		$styles  = wp_get_global_styles();
		$h1      = $styles['elements']['h1']['typography'] ?? array();
		$heading = $styles['elements']['heading']['typography'] ?? array();
		$props   = array(
			'fontSize'      => 'font-size',
			'fontFamily'    => 'font-family',
			'fontWeight'    => 'font-weight',
			'fontStyle'     => 'font-style',
			'lineHeight'    => 'line-height',
			'letterSpacing' => 'letter-spacing',
			'textTransform' => 'text-transform',
		);
		$css     = array();
		foreach ( $props as $key => $property ) {
			$value = $h1[ $key ] ?? ( $heading[ $key ] ?? ( 'fontSize' === $key ? '2em' : null ) );
			if ( ! is_string( $value ) || '' === $value ) {
				continue;
			}
			// "var:preset|font-size|x-large" is var(--wp--preset--font-size--x-large)
			if ( 0 === strpos( $value, 'var:' ) ) {
				$value = 'var(--wp--' . str_replace( '|', '--', substr( $value, 4 ) ) . ')';
			}
			$css[] = $property . ':' . preg_replace( '/[;{}<>]/', '', $value );
		}
		return implode( ';', $css );
	}
}
