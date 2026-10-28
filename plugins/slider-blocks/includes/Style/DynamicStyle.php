<?php
declare( strict_types=1 );

namespace GutSlider\Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates and enqueues dynamic CSS for rendered slider blocks.
 *
 * Collects block-level styles during rendering, combines and minifies
 * them, and then either writes them to a cached file in the uploads
 * directory or prints them inline, depending on the configured delivery
 * mode. Inline output is also used as a fallback whenever the file
 * cannot be written, so a page is never served without its styles.
 *
 * @package GutSlider\Style
 * @since   3.0.0
 */
final class DynamicStyle {

	/**
	 * Collected CSS styles from rendered blocks.
	 *
	 * Keyed by a hash of the style so that repeated blocks -- a synced
	 * pattern used twice, for instance -- contribute their CSS only once.
	 *
	 * @since 3.0.0
	 * @var array<string, string>
	 */
	private array $styles = array();

	/**
	 * Absolute path to the CSS upload directory, or null until resolved.
	 *
	 * @since 3.0.0
	 * @var string|null
	 */
	private ?string $upload_dir = null;

	/**
	 * Public URL to the CSS upload directory, or null until resolved.
	 *
	 * @since 3.0.0
	 * @var string|null
	 */
	private ?string $upload_url = null;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'register_render_hooks' ) );
	}

	/**
	 * Register the collection and output hooks for a front end request.
	 *
	 * Deferred to `wp` so the active theme is fully resolved before
	 * `wp_is_block_theme()` decides where the stylesheet is printed, and so
	 * admin, REST and cron requests never collect styles they cannot use.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_render_hooks(): void {
		if ( is_admin() || is_feed() ) {
			return;
		}

		add_filter( 'render_block', array( $this, 'collect_block_styles' ), 10, 2 );

		if ( wp_is_block_theme() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'generate_and_enqueue_combined_css' ) );
		} else {
			add_action( 'wp_footer', array( $this, 'generate_and_enqueue_combined_css' ) );
		}
	}

	/**
	 * Collect CSS styles from a rendered block.
	 *
	 * Inspects the block's attributes for inline styles and stores
	 * them for later combination into a single stylesheet.
	 *
	 * @since 3.0.0
	 *
	 * @param string               $block_content The rendered block HTML.
	 * @param array<string, mixed> $block         The block data array.
	 * @return string The unmodified block content.
	 */
	public function collect_block_styles( string $block_content, array $block ): string {
		if ( isset( $block['blockName'] ) && str_starts_with( (string) $block['blockName'], 'gutsliders/' ) ) {
			do_action( 'gutsliders_render_block', $block );

			if ( isset( $block['attrs']['blockStyle'] ) ) {
				$style = $block['attrs']['blockStyle'];

				if ( is_array( $style ) && ! empty( $style ) ) {
					$style = implode( ' ', $style );
				}

				if ( is_string( $style ) && '' !== $style ) {
					$style = $this->sanitize_css( $style );

					if ( '' !== $style ) {
						$this->styles[ md5( $style ) ] = $style;
					}
				}
			}
		}

		return $block_content;
	}

	/**
	 * Generate and output the combined CSS for the current page.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function generate_and_enqueue_combined_css(): void {
		if ( empty( $this->styles ) ) {
			return;
		}

		$minified_css = $this->minify_css( implode( "\n", $this->styles ) );

		if ( '' === $minified_css ) {
			return;
		}

		/*
		 * Inline output doubles as the fallback: if the stylesheet cannot be
		 * written or enqueued for any reason, print it rather than serving
		 * the page unstyled.
		 */
		if ( 'inline' === $this->get_delivery_mode() || ! $this->enqueue_css_file( $minified_css ) ) {
			$this->print_inline_css( $minified_css );
		}
	}

	/**
	 * Write the combined CSS to the uploads directory and enqueue it.
	 *
	 * @since 3.0.0
	 *
	 * @param string $css The minified CSS string.
	 * @return bool True when the stylesheet was enqueued.
	 */
	private function enqueue_css_file( string $css ): bool {
		$filesystem = $this->filesystem();

		if ( null === $filesystem || ! $this->prepare_upload_dir( $filesystem ) ) {
			return false;
		}

		/*
		 * Use the queried object rather than get_the_ID(): by the time styles
		 * are output the loop has finished, so the global post still points at
		 * the last post rendered and an archive would otherwise write its
		 * combined CSS over that post's own stylesheet. Anything that is not a
		 * single post gets a content derived name instead, which keeps two
		 * unrelated archives from sharing a file.
		 */
		$queried_id  = is_singular() ? get_queried_object_id() : 0;
		$file_suffix = $queried_id ? (string) $queried_id : 'archive-' . md5( $css );
		$file_name   = 'gutslider-styles-' . $file_suffix . '.min.css';
		$file_path   = $this->upload_dir . $file_name;

		$existing = $filesystem->exists( $file_path ) ? $filesystem->get_contents( $file_path ) : false;

		if ( $existing !== $css && ! $this->write_file( $filesystem, $file_path, $css ) ) {
			return false;
		}

		$version = file_exists( $file_path ) ? filemtime( $file_path ) : false;

		if ( false === $version ) {
			return false;
		}

		wp_enqueue_style(
			'gutslider-combined-styles',
			$this->upload_url . $file_name,
			array(),
			(string) $version
		);

		return true;
	}

	/**
	 * Write a file, replacing any existing one atomically.
	 *
	 * The contents go to a temporary file first and are then renamed over the
	 * target, so a concurrent request never reads a half written stylesheet.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Filesystem_Base $filesystem The filesystem abstraction.
	 * @param string              $path       Absolute destination path.
	 * @param string              $contents   File contents.
	 * @return bool True on success.
	 */
	private function write_file( \WP_Filesystem_Base $filesystem, string $path, string $contents ): bool {
		$temp_path = $path . '.' . wp_generate_password( 8, false ) . '.tmp';

		if ( ! $filesystem->put_contents( $temp_path, $contents, FS_CHMOD_FILE ) ) {
			$filesystem->delete( $temp_path );

			return false;
		}

		if ( ! $filesystem->move( $temp_path, $path, true ) ) {
			$filesystem->delete( $temp_path );

			return false;
		}

		return true;
	}

	/**
	 * Resolve the upload paths and make sure the directory exists.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Filesystem_Base $filesystem The filesystem abstraction.
	 * @return bool True when the directory is available.
	 */
	private function prepare_upload_dir( \WP_Filesystem_Base $filesystem ): bool {
		if ( null === $this->upload_dir ) {
			$upload_dir = wp_upload_dir();

			if ( ! empty( $upload_dir['error'] ) ) {
				return false;
			}

			$this->upload_dir = trailingslashit( $upload_dir['basedir'] ) . 'gutslider-styles/';
			$this->upload_url = trailingslashit( $upload_dir['baseurl'] ) . 'gutslider-styles/';
		}

		if ( ! is_dir( $this->upload_dir ) && ! wp_mkdir_p( $this->upload_dir ) ) {
			return false;
		}

		// Keep the directory from being listed on servers with indexes enabled.
		$index_file = $this->upload_dir . 'index.php';

		if ( ! $filesystem->exists( $index_file ) ) {
			$filesystem->put_contents( $index_file, "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
		}

		return true;
	}

	/**
	 * Get the initialized filesystem abstraction.
	 *
	 * @since 3.0.0
	 *
	 * @return \WP_Filesystem_Base|null The filesystem, or null when unavailable.
	 */
	private function filesystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;

		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
	}

	/**
	 * Get the configured CSS delivery mode.
	 *
	 * @since 3.0.0
	 *
	 * @return string Either 'file' or 'inline'.
	 */
	private function get_delivery_mode(): string {
		$settings = get_option( 'gutslider_settings', array() );
		$mode     = is_array( $settings ) && isset( $settings['css_delivery'] ) ? $settings['css_delivery'] : 'file';

		return 'inline' === $mode ? 'inline' : 'file';
	}

	/**
	 * Print the combined CSS in a style tag instead of a cached file.
	 *
	 * @since 3.0.0
	 *
	 * @param string $css The minified CSS string.
	 * @return void
	 */
	private function print_inline_css( string $css ): void {
		$handle = 'gutslider-inline-styles';

		if ( ! wp_style_is( $handle, 'registered' ) ) {
			wp_register_style( $handle, false, array(), GUTSLIDER_VERSION );
		}

		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $css );
	}

	/**
	 * Make a block's CSS safe to embed in a style element.
	 *
	 * Only a closing style tag can break out of the surrounding element, so
	 * that is all this removes. A blanket tag strip would also swallow valid
	 * CSS -- `@media (width<600px)` reads as an unclosed tag and would take
	 * the rest of the stylesheet with it.
	 *
	 * Removal repeats until the string stops changing, because a single pass
	 * can reassemble the very sequence it just removed: `<</style/style>`
	 * collapses into `</style>` once the inner match is taken out. Each pass
	 * shortens the string, so this always terminates.
	 *
	 * @since 3.0.0
	 *
	 * @param string $css The raw CSS string.
	 * @return string The sanitized CSS string.
	 */
	private function sanitize_css( string $css ): string {
		do {
			$previous = $css;
			$css      = (string) preg_replace( '#</\s*style#i', '', $css );
		} while ( $css !== $previous );

		return $css;
	}

	/**
	 * Minify a CSS string.
	 *
	 * Removes comments, collapses runs of whitespace, and drops the spaces
	 * surrounding characters that never need them. Whitespace that separates
	 * two values is preserved as a single space, so multi-line declarations
	 * such as `grid-template-areas` survive intact.
	 *
	 * @since 3.0.0
	 *
	 * @param string $css The raw CSS string.
	 * @return string The minified CSS string.
	 */
	private function minify_css( string $css ): string {
		// Remove comments.
		$css = (string) preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

		/*
		 * Set quoted strings aside so their whitespace is not collapsed, then
		 * restore them once the surrounding CSS has been minified.
		 */
		$strings = array();

		$css = (string) preg_replace_callback(
			'/"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'/',
			static function ( array $match ) use ( &$strings ): string {
				$strings[] = $match[0];

				return "\0gs" . ( count( $strings ) - 1 ) . "\0";
			},
			$css
		);

		// Collapse every run of whitespace into a single space.
		$css = (string) preg_replace( '/\s+/', ' ', $css );

		/*
		 * Drop the spaces around structural characters. Combinators such as
		 * `+` and `~` are left alone because they also appear inside values
		 * like `calc()` and `nth-child()`, as is `(`, which needs its leading
		 * space in `@media screen and (min-width: 600px)`.
		 */
		$css = (string) preg_replace( '/\s*([{}:;,>])\s*/', '$1', $css );

		// Drop the final semicolon of each rule.
		$css = str_replace( ';}', '}', $css );
		$css = trim( $css );

		if ( ! empty( $strings ) ) {
			$css = (string) preg_replace_callback(
				"/\0gs(\d+)\0/",
				static function ( array $match ) use ( $strings ): string {
					return $strings[ (int) $match[1] ];
				},
				$css
			);
		}

		return $css;
	}
}
