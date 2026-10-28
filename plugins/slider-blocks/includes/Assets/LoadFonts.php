<?php
declare( strict_types=1 );

namespace GutSlider\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles loading of Google Fonts used in slider blocks.
 *
 * Collects font family attributes from rendered blocks and enqueues
 * the corresponding Google Fonts stylesheets.
 *
 * @package GutSlider\Assets
 * @since   3.0.0
 */
final class LoadFonts {

	/**
	 * System fonts that do not need to be loaded from Google Fonts.
	 *
	 * @since 3.0.0
	 * @var array<int, string>
	 */
	private const SYSTEM_FONTS = array(
		// Posts saved before the picker stored an empty value for "inherit".
		'Default',
		'Arial',
		'Tahoma',
		'Verdana',
		'Helvetica',
		'Times New Roman',
		'Trebuchet MS',
		'Georgia',
	);

	/**
	 * Collected font families from rendered blocks.
	 *
	 * @since 3.0.0
	 * @var array<int, string>
	 */
	private static array $all_fonts = array();

	/**
	 * Constructor.
	 *
	 * Registers WordPress hooks for font loading.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'fonts_loader' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'fonts_loader' ), 10 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_fonts' ) );
		add_action( 'gutsliders_render_block', array( $this, 'font_generator' ) );
	}

	/**
	 * Enqueue the fonts used by the post open in the block editor.
	 *
	 * Hooked to `enqueue_block_assets` because WordPress mirrors that hook into
	 * the editor canvas iframe, which is where apiVersion 3 blocks render.
	 * `admin_enqueue_scripts` only reaches the document around the canvas, so
	 * the preview would resolve `font-family` against a font that document has
	 * but the iframe never loaded.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function enqueue_editor_fonts(): void {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( null === $screen || ! $screen->is_block_editor() ) {
			return;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post || ! has_blocks( $post->post_content ) ) {
			return;
		}

		$this->collect_fonts( parse_blocks( $post->post_content ) );
		$this->fonts_loader();
	}

	/**
	 * Walk a parsed block tree and record every font family it uses.
	 *
	 * The front end collects fonts from `render_block`, which fires for nested
	 * blocks too. A parsed tree has to be walked explicitly.
	 *
	 * @since 3.0.0
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return void
	 */
	private function collect_fonts( array $blocks ): void {
		foreach ( $blocks as $block ) {
			if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
				$this->collect_block_fonts( $block['attrs'] );
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$this->collect_fonts( $block['innerBlocks'] );
			}
		}
	}

	/**
	 * Record the font families named in a single block's attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return void
	 */
	private function collect_block_fonts( array $attributes ): void {
		foreach ( $attributes as $key => $value ) {
			if ( ! empty( $value )
				&& is_string( $value )
				&& strpos( $key, 'gutsliders_' ) === 0
				&& strpos( $key, 'FontFamily' ) !== false
			) {
				self::$all_fonts[] = sanitize_text_field( $value );
			}
		}
	}

	/**
	 * Extract font families from a rendered block's attributes.
	 *
	 * Scans block attributes for font family values and triggers
	 * the font loader to enqueue any new fonts found.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $block The block data array.
	 * @return void
	 */
	public function font_generator( array $block ): void {
		if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
			$this->collect_block_fonts( $block['attrs'] );
			$this->fonts_loader();
		}
	}

	/**
	 * Enqueue Google Fonts stylesheets for collected font families.
	 *
	 * Filters out system fonts and duplicates, then registers and
	 * enqueues individual Google Fonts CSS files.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function fonts_loader(): void {
		if ( ! $this->is_google_fonts_enabled() ) {
			return;
		}

		if ( ! is_array( self::$all_fonts ) || count( self::$all_fonts ) === 0 ) {
			return;
		}

		$fonts = array_filter( array_unique( self::$all_fonts ) );

		if ( empty( $fonts ) ) {
			return;
		}

		$gfonts_attr = ':100,200,300,400,500,600,700,800,900';

		foreach ( $fonts as $font ) {
			if ( in_array( $font, self::SYSTEM_FONTS, true ) || empty( $font ) ) {
				continue;
			}

			$font_family = str_replace( ' ', '+', trim( $font ) ) . $gfonts_attr;
			$query_args  = array( 'family' => $font_family );
			$font_handle = 'gutsliders-font-' . sanitize_title( $font );

			wp_register_style(
				$font_handle,
				add_query_arg( $query_args, '//fonts.googleapis.com/css' ),
				array(),
				GUTSLIDER_VERSION,
				'all'
			);

			wp_enqueue_style( $font_handle );
		}
	}

	/**
	 * Whether Google Fonts requests are allowed.
	 *
	 * Site owners can turn this off from the GutSlider settings screen
	 * when the theme already loads the fonts or hosts them locally.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True when Google Fonts may be enqueued.
	 */
	private function is_google_fonts_enabled(): bool {
		$settings = get_option( 'gutslider_settings', array() );

		if ( is_array( $settings ) && array_key_exists( 'google_fonts', $settings ) ) {
			return (bool) $settings['google_fonts'];
		}

		return true;
	}
}
