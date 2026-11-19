<?php
declare( strict_types=1 );

namespace GutSlider\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles enqueuing of editor and front-end assets.
 *
 * Registers and enqueues JavaScript and CSS assets required by the
 * plugin in both the block editor and the front-end.
 *
 * @package GutSlider\Assets
 * @since   3.0.0
 */
final class EnqueueAssets {

	/**
	 * Preview images for blocks.
	 *
	 * Maps block identifiers to their preview image filenames.
	 *
	 * @since 3.0.0
	 * @var array<string, string>
	 */
	private const PREVIEW_IMAGES = array(
		'content'            => 'content.svg',
		'photo_carousel'     => 'photo.svg',
		'testimonial_slider' => 'testimonial.svg',
		'before_after'       => 'ba.svg',
		'placeholder'        => 'placeholder.svg',
	);

	/**
	 * Constructor.
	 *
	 * Registers WordPress hooks for asset enqueuing.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ), 2 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_both_assets' ) );
	}

	/**
	 * Enqueue editor-specific assets.
	 *
	 * Loads JavaScript and CSS required only within the block editor,
	 * and localizes preview image URLs for block previews.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$this->enqueue_asset( 'global', true, true );
		$this->enqueue_asset( 'modules', false );
		$this->localize_preview_images();
		$this->localize_editor_settings();
	}

	/**
	 * Expose editor-facing settings to the global editor bundle.
	 *
	 * Printed as an inline script rather than via wp_localize_script so the
	 * values keep their real types — wp_localize_script casts top level
	 * scalars to strings, which would turn `false` into a truthy "".
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function localize_editor_settings(): void {
		$settings = get_option( 'gutslider_settings', array() );

		$pattern_library = ! is_array( $settings ) || ! array_key_exists( 'pattern_library', $settings )
			? true
			: (bool) $settings['pattern_library'];

		wp_add_inline_script(
			'gutslider-blocks-global-script',
			'window.gutsliderEditor = ' . wp_json_encode(
				array(
					'patternLibrary' => $pattern_library,
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Enqueue front-end and shared assets.
	 *
	 * Registers Swiper library assets and the lightbox script
	 * for use by individual blocks on the front-end.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function enqueue_both_assets(): void {
		wp_register_style(
			'gutslider-swiper-style',
			GUTSLIDER_URL . 'assets/css/swiper-bundle.min.css',
			array(),
			GUTSLIDER_VERSION
		);

		wp_register_script(
			'gutslider-swiper-script',
			GUTSLIDER_URL . 'assets/js/swiper-bundle.min.js',
			array(),
			GUTSLIDER_VERSION,
			true
		);

		/*
		 * Mouse wheel control: once the first or last slide is reached, the wheel scrolls the
		 * page again instead of being held by the slider. Saved options say `mousewheel: true`,
		 * which Swiper fills in from these defaults.
		 */
		wp_add_inline_script(
			'gutslider-swiper-script',
			'window.Swiper && window.Swiper.extendDefaults({ mousewheel: { releaseOnEdges: true } });'
		);

		wp_localize_script(
			'gutslider-swiper-script',
			'gutslider_swiper',
			array(
				'placeholder' => esc_url( GUTSLIDER_URL . 'assets/images/placeholder.svg' ),
			)
		);

		if ( ! is_admin() ) {
			wp_register_script(
				'gutslider-fslightbox',
				GUTSLIDER_URL . 'assets/js/fslightbox.js',
				array(),
				GUTSLIDER_VERSION,
				true
			);
		}
	}

	/**
	 * Enqueue or register a build asset by name.
	 *
	 * Loads the asset's dependency file and uses it to register or
	 * enqueue the corresponding JavaScript and optionally CSS file.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name          The asset name matching the build directory.
	 * @param bool   $enqueue_style Whether to also enqueue the CSS file.
	 * @param bool   $register_mode Whether to register only (true) or enqueue (false).
	 * @return void
	 */
	private function enqueue_asset( string $name, bool $enqueue_style = true, bool $register_mode = false ): void {
		$asset_path = GUTSLIDER_DIR_PATH . "build/{$name}/{$name}.asset.php";

		if ( file_exists( $asset_path ) ) {
			$dependency_file = include $asset_path;

			if ( is_array( $dependency_file ) && ! empty( $dependency_file ) ) {
				$handle       = "gutslider-blocks-{$name}-script";
				$src          = GUTSLIDER_URL . "build/{$name}/{$name}.js";
				$dependencies = $dependency_file['dependencies'] ?? array();
				$version      = $dependency_file['version'] ?? GUTSLIDER_VERSION;
				$in_footer    = ( 'global' === $name );

				if ( $register_mode ) {
					wp_register_script( $handle, $src, $dependencies, $version, $in_footer );
				} else {
					wp_enqueue_script( $handle, $src, $dependencies, $version, $in_footer );
				}
			}
		}

		if ( $enqueue_style ) {
			$style_handle = "gutslider-blocks-{$name}-style";
			$style_src    = GUTSLIDER_URL . "build/{$name}/{$name}.css";

			if ( $register_mode ) {
				wp_register_style( $style_handle, $style_src, array(), GUTSLIDER_VERSION );
			} else {
				wp_enqueue_style( $style_handle, $style_src, array(), GUTSLIDER_VERSION );
			}
		}
	}

	/**
	 * Localize preview image URLs for the block editor.
	 *
	 * Passes preview image URLs and pro status to the modules script
	 * for use in block preview rendering.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function localize_preview_images(): void {
		$preview_urls = array();
		foreach ( self::PREVIEW_IMAGES as $key => $image ) {
			$preview_urls[ $key ] = esc_url( GUTSLIDER_URL . "assets/images/{$image}" );
		}

		$preview_urls['is_pro'] = defined( 'GUTSLIDER_PRO_VERSION' );

		wp_localize_script(
			'gutslider-blocks-modules-script',
			'gutsliderData',
			array(
				'hasPro' => defined( 'GUTSLIDER_PRO_VERSION' ),
			)
		);

		wp_localize_script(
			'gutslider-blocks-modules-script',
			'gutslider_preview',
			$preview_urls
		);
	}
}
