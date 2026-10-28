<?php
declare( strict_types=1 );

namespace GutSlider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin singleton class.
 *
 * Orchestrates the initialization of all plugin components including
 * block registration, asset enqueuing, REST API, and style generation.
 *
 * @package GutSlider
 * @since   3.0.0
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @since 3.0.0
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 3.0.0
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance.
	 *
	 * Creates and initializes the plugin instance on first call.
	 * Subsequent calls return the existing instance.
	 *
	 * @since 3.0.0
	 *
	 * @return self The plugin instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initialize all plugin components.
	 *
	 * Instantiates each component class which registers its own hooks
	 * internally. Also sets up the activation redirect handler.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function init(): void {
		// Activation redirect.
		add_action( 'admin_init', array( $this, 'maybe_redirect_after_activation' ) );

		// Initialize components.
		new Blocks\BlocksCategory();
		new Blocks\RegisterBlocks();
		new Blocks\Pattern();
		new Blocks\VideoEmbeds();
		new Blocks\PostSliderRest();
		new Blocks\PostSlider();
		new Blocks\TermColors();
		new Blocks\FlexibleSlider();
		new Blocks\Marquee();
		new Blocks\StaticSlider();
		new Assets\EnqueueAssets();
		new Assets\LoadFonts();
		new Style\DynamicStyle();
		new Api\BlocksApi();
	}

	/**
	 * Handle post-activation redirect to the welcome page.
	 *
	 * Checks for the activation flag in options and redirects to the
	 * plugin's admin page on first admin page load after activation.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function maybe_redirect_after_activation(): void {
		if ( get_option( 'gutsliderblocks_do_activation_redirect', false ) ) {
			delete_option( 'gutsliderblocks_do_activation_redirect' );
			wp_safe_redirect( admin_url( 'admin.php?page=gutslider-blocks' ) );
			exit;
		}
	}

	/**
	 * Set the activation redirect flag.
	 *
	 * Called on plugin activation via register_activation_hook.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public static function on_activation(): void {
		add_option( 'gutsliderblocks_do_activation_redirect', true );
	}

	/**
	 * Clean up plugin data on deactivation.
	 *
	 * Removes generated CSS files and their directory from the
	 * WordPress uploads folder.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public static function cleanup(): void {
		$upload_dir = wp_upload_dir();
		$css_dir    = $upload_dir['basedir'] . '/gutslider-styles';

		if ( ! file_exists( $css_dir ) ) {
			return;
		}

		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$files = glob( $css_dir . '/*' );

		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					$wp_filesystem->delete( $file );
				}
			}
		}

		$wp_filesystem->rmdir( $css_dir );

		// Remove parent directory if empty.
		$parent_dir = dirname( $css_dir );
		if ( file_exists( $parent_dir ) ) {
			$parent_contents = glob( "$parent_dir/*" );
			if ( is_array( $parent_contents ) && count( $parent_contents ) === 0 ) {
				$wp_filesystem->rmdir( $parent_dir );
			}
		}
	}
}
