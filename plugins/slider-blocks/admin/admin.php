<?php
/**
 * GutSlider admin dashboard bootstrap.
 *
 * Registers the admin menu, loads dashboard assets, and exposes the REST
 * endpoints used by the dashboard UI to persist changes.
 *
 * @package GutSliderBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GutSlider_Admin' ) ) {

	/**
	 * Admin dashboard controller.
	 */
	class GutSlider_Admin {

		/**
		 * Option key holding the general settings array.
		 *
		 * @var string
		 */
		const SETTINGS_OPTION = 'gutslider_settings';

		/**
		 * Top level menu slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'gutslider-blocks';

		/**
		 * Page slug for the blocks manager.
		 *
		 * @var string
		 */
		const BLOCKS_SLUG = 'gutslider-blocks-settings';

		/**
		 * Page slug for the settings screen.
		 *
		 * @var string
		 */
		const SETTINGS_SLUG = 'gutslider-settings';

		/**
		 * Page slug for the Free vs Pro comparison.
		 *
		 * @var string
		 */
		const COMPARE_SLUG = 'gutslider-free-vs-pro';

		/**
		 * Transient holding the cached content-usage scan.
		 *
		 * @var string
		 */
		const USAGE_TRANSIENT = 'gutslider_usage_stats';

		/**
		 * Option holding the onboarding checklist state.
		 *
		 * @var string
		 */
		const ONBOARDING_OPTION = 'gutslider_onboarding';

		/**
		 * Maximum number of posts inspected by the usage scan.
		 *
		 * @var int
		 */
		const USAGE_SCAN_LIMIT = 500;

		/**
		 * Default values for the general settings.
		 *
		 * @var array<string, mixed>
		 */
		private static $settings_defaults = array(
			'css_delivery'    => 'file',
			'google_fonts'    => true,
			'pattern_library' => true,
			'remove_data'     => false,
		);

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
			add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
			add_action( 'save_post', array( self::class, 'flush_usage_cache' ) );
			add_action( 'deleted_post', array( self::class, 'flush_usage_cache' ) );
		}

		/* ---------------------------------------------------------------
		 * Settings helpers
		 * ------------------------------------------------------------- */

		/**
		 * Get the general settings, merged over the defaults.
		 *
		 * @return array<string, mixed> Settings array.
		 */
		public static function get_settings() {
			$stored = get_option( self::SETTINGS_OPTION, array() );

			if ( ! is_array( $stored ) ) {
				$stored = array();
			}

			return wp_parse_args( $stored, self::$settings_defaults );
		}

		/**
		 * Get a single setting value.
		 *
		 * @param string $key     Setting key.
		 * @param mixed  $default Fallback when the key is unknown.
		 * @return mixed Setting value.
		 */
		public static function get_setting( $key, $default = null ) {
			$settings = self::get_settings();

			return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
		}

		/**
		 * Build the option key that stores a block's enabled state.
		 *
		 * @param string $block_name Block directory name.
		 * @return string Option key.
		 */
		public static function block_option_key( $block_name ) {
			return 'gut_' . str_replace( '-', '_', $block_name );
		}

		/**
		 * Read the block definitions from the shared data file.
		 *
		 * @return array<int, array<string, mixed>> Block definitions.
		 */
		public function get_blocks() {
			$blocks_file = GUTSLIDER_DIR . '/includes/Api/blocks.php';

			if ( ! file_exists( $blocks_file ) ) {
				return array();
			}

			$blocks = include $blocks_file;

			return is_array( $blocks ) ? $blocks : array();
		}

		/**
		 * Decorate the block definitions with their current state.
		 *
		 * @return array<int, array<string, mixed>> Block definitions.
		 */
		public function get_blocks_with_state() {
			$has_pro = defined( 'GUTSLIDER_PRO_VERSION' );
			$blocks  = array();

			foreach ( $this->get_blocks() as $block ) {
				$is_pro = ! empty( $block['is_pro'] );

				$block['is_pro']     = $is_pro;
				$block['locked']     = $is_pro && ! $has_pro;
				$block['option_key'] = self::block_option_key( $block['name'] );
				$block['enabled']    = $block['locked']
					? false
					: (bool) get_option( $block['option_key'], true );

				$blocks[] = $block;
			}

			return $blocks;
		}

		/**
		 * Count how many blocks are currently enabled.
		 *
		 * @param array<int, array<string, mixed>> $blocks Decorated blocks.
		 * @return int Enabled block count.
		 */
		public static function count_enabled( array $blocks ) {
			$count = 0;

			foreach ( $blocks as $block ) {
				if ( ! empty( $block['enabled'] ) ) {
					++$count;
				}
			}

			return $count;
		}

		/* ---------------------------------------------------------------
		 * Menu + assets
		 * ------------------------------------------------------------- */

		/**
		 * Register the admin menu and its sub pages.
		 *
		 * @return void
		 */
		public function admin_menu() {
			$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg width="20" height="20" viewBox="0 0 45 45" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M5 45C3.625 45 2.44833 44.5108 1.47 43.5325C0.491667 42.5542 0.00166667 41.3767 0 40V5C0 3.625 0.49 2.44833 1.47 1.47C2.45 0.491667 3.62667 0.00166667 5 0H40C41.375 0 42.5525 0.49 43.5325 1.47C44.5125 2.45 45.0017 3.62667 45 5V40C45 41.375 44.5108 42.5525 43.5325 43.5325C42.5542 44.5125 41.3767 45.0017 40 45H5ZM17.5 35H27.5C28.875 35 30.0525 34.5108 31.0325 33.5325C32.0125 32.5542 32.5017 31.3767 32.5 30V20H22.5V25H27.5V30H17.5V15H32.5C32.5 13.625 32.0108 12.4483 31.0325 11.47C30.0542 10.4917 28.8767 10.0017 27.5 10H17.5C16.125 10 14.9483 10.49 13.97 11.47C12.9917 12.45 12.5017 13.6267 12.5 15V30C12.5 31.375 12.99 32.5525 13.97 33.5325C14.95 34.5125 16.1267 35.0017 17.5 35Z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Inline menu icon.

			add_menu_page(
				__( 'GutSlider', 'slider-blocks' ),
				__( 'GutSlider', 'slider-blocks' ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render_dashboard_page' ),
				$icon,
				100
			);

			add_submenu_page(
				self::MENU_SLUG,
				__( 'Dashboard', 'slider-blocks' ),
				__( 'Dashboard', 'slider-blocks' ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render_dashboard_page' )
			);

			add_submenu_page(
				self::MENU_SLUG,
				__( 'Blocks', 'slider-blocks' ),
				__( 'Blocks', 'slider-blocks' ),
				'manage_options',
				self::BLOCKS_SLUG,
				array( $this, 'render_blocks_page' )
			);

			add_submenu_page(
				self::MENU_SLUG,
				__( 'Settings', 'slider-blocks' ),
				__( 'Settings', 'slider-blocks' ),
				'manage_options',
				self::SETTINGS_SLUG,
				array( $this, 'render_settings_page' )
			);

			if ( self::show_compare() ) {
				add_submenu_page(
					self::MENU_SLUG,
					__( 'Free vs Pro', 'slider-blocks' ),
					__( 'Free vs Pro', 'slider-blocks' ),
					'manage_options',
					self::COMPARE_SLUG,
					array( $this, 'render_compare_page' )
				);
			}
		}

		/**
		 * Whether the Free vs Pro page is offered.
		 *
		 * @return bool True while GutSlider Pro isn't active.
		 */
		public static function show_compare() {
			/**
			 * Whether the dashboard offers the Free vs Pro comparison.
			 *
			 * @param bool $show True while GutSlider Pro isn't active.
			 */
			return (bool) apply_filters( 'gutslider_show_pro_compare', ! defined( 'GUTSLIDER_PRO_VERSION' ) );
		}

		/**
		 * Screen IDs that should receive the dashboard assets.
		 *
		 * @return array<int, string> Screen IDs.
		 */
		public static function screen_ids() {
			return array(
				'toplevel_page_' . self::MENU_SLUG,
				'gutslider_page_' . self::BLOCKS_SLUG,
				'gutslider_page_' . self::SETTINGS_SLUG,
				'gutslider_page_' . self::COMPARE_SLUG,
				'gutslider_page_gutslider-license',
			);
		}

		/**
		 * Whether the current request is a GutSlider dashboard screen.
		 *
		 * @param string $screen Current screen ID.
		 * @return bool True on a dashboard screen.
		 */
		public static function is_dashboard_screen( $screen = '' ) {
			if ( '' === $screen ) {
				$current = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
				$screen  = $current ? $current->id : '';
			}

			return in_array( $screen, self::screen_ids(), true );
		}

		/**
		 * Enqueue the dashboard stylesheet and script.
		 *
		 * @param string $screen Current screen ID.
		 * @return void
		 */
		public function admin_assets( $screen ) {
			if ( ! self::is_dashboard_screen( $screen ) ) {
				return;
			}

			wp_enqueue_style(
				'gutslider-admin',
				GUTSLIDER_URL . 'admin/css/admin.css',
				array(),
				GUTSLIDER_VERSION
			);

			wp_enqueue_script(
				'gutslider-admin',
				GUTSLIDER_URL . 'admin/js/admin.js',
				array( 'wp-i18n' ),
				GUTSLIDER_VERSION,
				true
			);

			wp_localize_script(
				'gutslider-admin',
				'gutslider',
				array(
					'version'    => GUTSLIDER_VERSION,
					'isPro'      => defined( 'GUTSLIDER_PRO_VERSION' ),
					'proVersion' => defined( 'GUTSLIDER_PRO_VERSION' ) ? GUTSLIDER_PRO_VERSION : '',
					'restUrl'    => esc_url_raw( rest_url( 'gutslider/v1/' ) ),
					'nonce'      => wp_create_nonce( 'wp_rest' ),
					'pricingUrl' => 'https://gutslider.com/pricing',
					'i18n'       => array(
						'saved'         => __( 'Changes saved', 'slider-blocks' ),
						'saveFailed'    => __( 'Could not save. Please try again.', 'slider-blocks' ),
						'discarded'     => __( 'Changes discarded', 'slider-blocks' ),
						'cacheCleared'  => __( 'Style cache cleared', 'slider-blocks' ),
						/* translators: %d: number of unsaved changes. */
						'changeSingle'  => __( '%d unsaved change', 'slider-blocks' ),
						/* translators: %d: number of unsaved changes. */
						'changePlural'  => __( '%d unsaved changes', 'slider-blocks' ),
						'leaveWarning'  => __( 'You have unsaved changes.', 'slider-blocks' ),
						/* translators: 1: enabled block count, 2: total block count. */
						'enabledCount'  => __( '%1$d of %2$d enabled', 'slider-blocks' ),
						/* translators: 1: free block count, 2: pro block count, 3: enabled count, 4: total count. */
						'blockSummary'  => __( '%1$d free blocks · %2$d pro blocks · %3$d of %4$d enabled', 'slider-blocks' ),
						'allEnabled'    => __( 'All blocks enabled', 'slider-blocks' ),
						/* translators: %d: number of disabled blocks. */
						'someDisabled'  => __( '%d turned off', 'slider-blocks' ),
					),
				)
			);
		}

		/**
		 * Add a marker class so the stylesheet can reset the admin chrome.
		 *
		 * @param string $classes Existing body classes.
		 * @return string Filtered body classes.
		 */
		public function admin_body_class( $classes ) {
			if ( self::is_dashboard_screen() ) {
				$classes .= ' gutslider-admin-page ';
			}

			return $classes;
		}

		/* ---------------------------------------------------------------
		 * Dashboard data
		 * ------------------------------------------------------------- */

		/**
		 * Scan post content for GutSlider blocks.
		 *
		 * Counts how many slider blocks are in use and how many posts they
		 * live on. The result is cached because it is a full-text scan.
		 *
		 * @return array{sliders:int, posts:int, capped:bool} Usage figures.
		 */
		public static function get_usage_stats() {
			$cached = get_transient( self::USAGE_TRANSIENT );

			if ( is_array( $cached ) ) {
				return $cached;
			}

			global $wpdb;

			$needle = '<!-- wp:gutsliders/';
			$like   = '%' . $wpdb->esc_like( $needle ) . '%';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Content scan, cached in a transient below.
			$contents = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_content FROM {$wpdb->posts}
					WHERE post_type NOT IN ( 'revision', 'nav_menu_item' )
					AND post_status NOT IN ( 'auto-draft', 'trash', 'inherit' )
					AND post_content LIKE %s
					LIMIT %d",
					$like,
					self::USAGE_SCAN_LIMIT
				)
			);

			$contents = is_array( $contents ) ? $contents : array();
			$sliders  = 0;

			foreach ( $contents as $content ) {
				$sliders += substr_count( (string) $content, $needle );
			}

			$stats = array(
				'sliders' => $sliders,
				'posts'   => count( $contents ),
				'capped'  => count( $contents ) >= self::USAGE_SCAN_LIMIT,
			);

			set_transient( self::USAGE_TRANSIENT, $stats, 12 * HOUR_IN_SECONDS );

			return $stats;
		}

		/**
		 * Drop the cached usage scan.
		 *
		 * @return void
		 */
		public static function flush_usage_cache() {
			delete_transient( self::USAGE_TRANSIENT );
		}

		/**
		 * Measure the stylesheets GutSlider has generated.
		 *
		 * @return array{files:int, bytes:int, average:int} Stylesheet figures.
		 */
		public static function get_css_stats() {
			$upload = wp_upload_dir();
			$dir    = trailingslashit( $upload['basedir'] ) . 'gutslider-styles';
			$files  = is_dir( $dir ) ? glob( $dir . '/*.css' ) : array();
			$files  = is_array( $files ) ? $files : array();
			$bytes  = 0;

			foreach ( $files as $file ) {
				$bytes += (int) filesize( $file );
			}

			$count = count( $files );

			return array(
				'files'   => $count,
				'bytes'   => $bytes,
				'average' => $count > 0 ? (int) round( $bytes / $count ) : 0,
			);
		}

		/**
		 * Get the Pro licence status.
		 *
		 * The free plugin has no licence data of its own, so the Pro plugin
		 * supplies it through the `gutslider_license_status` filter. Anything
		 * missing simply degrades to a quieter pill.
		 *
		 * @return array{state:string, label:string}|null Licence data, or null when unavailable.
		 */
		public static function get_license() {
			if ( ! defined( 'GUTSLIDER_PRO_VERSION' ) ) {
				return null;
			}

			/**
			 * Filter the licence status shown in the dashboard header.
			 *
			 * @param array|null $status Associative array with `state` (active|expiring|inactive)
			 *                           and either a ready-made `label` or an `expires` timestamp.
			 */
			$status = apply_filters( 'gutslider_license_status', null );

			if ( ! is_array( $status ) ) {
				return array(
					'state' => 'active',
					'label' => __( 'GutSlider Pro active', 'slider-blocks' ),
				);
			}

			$state = isset( $status['state'] ) ? (string) $status['state'] : 'active';
			$label = isset( $status['label'] ) ? (string) $status['label'] : '';

			if ( '' === $label && ! empty( $status['expires'] ) ) {
				$expires = is_numeric( $status['expires'] ) ? (int) $status['expires'] : strtotime( (string) $status['expires'] );

				if ( $expires ) {
					$label = sprintf(
						/* translators: %s: licence renewal date. */
						'expiring' === $state ? __( 'License expires %s', 'slider-blocks' ) : __( 'License active · renews %s', 'slider-blocks' ),
						date_i18n( 'j M Y', $expires )
					);
				}
			}

			if ( '' === $label ) {
				$label = 'inactive' === $state
					? __( 'License inactive', 'slider-blocks' )
					: __( 'GutSlider Pro active', 'slider-blocks' );
			}

			return array(
				'state' => in_array( $state, array( 'active', 'expiring', 'inactive' ), true ) ? $state : 'active',
				'label' => $label,
			);
		}

		/**
		 * Get the banner shown across the top of the dashboard.
		 *
		 * The free plugin owns the markup and styling; anything with
		 * something to say — the Pro plugin's licence prompt, for example —
		 * supplies the copy through the filter.
		 *
		 * @return array{tone:string, title:string, text:string, action_label:string, action_url:string}|null
		 *         Normalised notice, or null when there is nothing to show.
		 */
		public static function get_dashboard_notice() {
			/**
			 * Filter the dashboard banner.
			 *
			 * Return an empty array for no banner, or an array with:
			 * `tone` (info|warning|error), `title`, `text`, `action_label`
			 * and `action_url`. A notice without a title is discarded.
			 *
			 * @param array $notice Notice definition.
			 */
			$notice = apply_filters( 'gutslider_dashboard_notice', array() );

			if ( ! is_array( $notice ) || empty( $notice['title'] ) ) {
				return null;
			}

			$tone = isset( $notice['tone'] ) ? (string) $notice['tone'] : 'info';

			return array(
				'tone'         => in_array( $tone, array( 'info', 'warning', 'error' ), true ) ? $tone : 'info',
				'title'        => (string) $notice['title'],
				'text'         => isset( $notice['text'] ) ? (string) $notice['text'] : '',
				'action_label' => isset( $notice['action_label'] ) ? (string) $notice['action_label'] : '',
				'action_url'   => isset( $notice['action_url'] ) ? (string) $notice['action_url'] : '',
			);
		}

		/**
		 * Read the current release notes out of readme.txt.
		 *
		 * @param int $limit Maximum number of entries to return.
		 * @return array<int, array{tag:string, label:string, text:string}> Release notes.
		 */
		public static function get_changelog( $limit = 4 ) {
			$readme = GUTSLIDER_DIR . '/readme.txt';

			if ( ! is_readable( $readme ) ) {
				return array();
			}

			$contents = file_get_contents( $readme ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.

			if ( ! is_string( $contents ) ) {
				return array();
			}

			// Grab the block of lines under the heading for the running version.
			$pattern = '/^=\s*' . preg_quote( GUTSLIDER_VERSION, '/' ) . '\s*=\s*$(.*?)(?=^=\s|\z)/ms';

			if ( ! preg_match( $pattern, $contents, $matches ) ) {
				return array();
			}

			$tags = array(
				'added'     => 'new',
				'new'       => 'new',
				'fixed'     => 'fixed',
				'fix'       => 'fixed',
				'improved'  => 'improved',
				'updated'   => 'improved',
				'optimized' => 'improved',
				'enhanced'  => 'improved',
				'changed'   => 'improved',
				'checked'   => 'improved',
			);

			$labels = array(
				'new'      => __( 'New', 'slider-blocks' ),
				'improved' => __( 'Improved', 'slider-blocks' ),
				'fixed'    => __( 'Fixed', 'slider-blocks' ),
			);

			$entries = array();

			foreach ( preg_split( '/\R/', $matches[1] ) as $line ) {
				$line = trim( $line );

				if ( '' === $line || '*' !== $line[0] ) {
					continue;
				}

				$text = trim( ltrim( $line, '*' ) );
				$tag  = 'new';

				if ( preg_match( '/^([A-Za-z]+)\s*:\s*(.+)$/', $text, $prefix ) ) {
					$key = strtolower( $prefix[1] );

					if ( isset( $tags[ $key ] ) ) {
						$tag  = $tags[ $key ];
						$text = trim( $prefix[2] );
					}
				} else {
					$first = strtolower( strtok( $text, ' ' ) );
					$tag   = isset( $tags[ $first ] ) ? $tags[ $first ] : 'new';
				}

				$entries[] = array(
					'tag'   => $tag,
					'label' => $labels[ $tag ],
					'text'  => $text,
				);

				if ( count( $entries ) >= $limit ) {
					break;
				}
			}

			return $entries;
		}

		/**
		 * Mark an onboarding step as done.
		 *
		 * @param string $step Step key.
		 * @return void
		 */
		public static function complete_onboarding_step( $step ) {
			$state = get_option( self::ONBOARDING_OPTION, array() );

			if ( ! is_array( $state ) ) {
				$state = array();
			}

			if ( empty( $state[ $step ] ) ) {
				$state[ $step ] = true;
				update_option( self::ONBOARDING_OPTION, $state );
			}
		}

		/**
		 * Whether an onboarding step has been completed.
		 *
		 * @param string $step Step key.
		 * @return bool True when done.
		 */
		public static function onboarding_done( $step ) {
			$state = get_option( self::ONBOARDING_OPTION, array() );

			return is_array( $state ) && ! empty( $state[ $step ] );
		}

		/* ---------------------------------------------------------------
		 * Icons
		 * ------------------------------------------------------------- */

		/**
		 * Inline SVG bodies, drawn on a 24x24 grid with a 1.7px stroke.
		 *
		 * @return array<string, string> Map of icon name to SVG children.
		 */
		private static function icon_paths() {
			static $paths = null;

			if ( null !== $paths ) {
				return $paths;
			}

			$paths = array(
				'sun'                 => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
				'moon'                => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
				'book-open'           => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
				'plus'                => '<path d="M5 12h14M12 5v14"/>',
				'sliders-horizontal'  => '<path d="M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4"/>',
				'layout-grid'         => '<rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/>',
				'layers'              => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
				'gauge'               => '<path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
				'type'                => '<path d="M4 7V4h16v3M9 20h6M12 4v16"/>',
				'search'              => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
				'arrow-right'         => '<path d="M5 12h14M12 5l7 7-7 7"/>',
				'check'               => '<path d="M20 6 9 17l-5-5"/>',
				'play'                => '<path d="M7 4.5v15l13-7.5z" fill="currentColor" stroke="none"/>',
				'life-buoy'           => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><path d="m4.93 4.93 4.24 4.24M14.83 9.17l4.24-4.24M14.83 14.83l4.24 4.24M9.17 14.83l-4.24 4.24"/>',
				'star'                => '<path d="m12 2.6 2.9 5.9 6.5.9-4.7 4.6 1.1 6.4-5.8-3-5.8 3 1.1-6.4L2.6 9.4l6.5-.9z"/>',
				'chevron-right'       => '<path d="m9 18 6-6-6-6"/>',
				'layout-template'     => '<rect width="18" height="7" x="3" y="3" rx="1.5"/><rect width="9" height="7" x="3" y="14" rx="1.5"/><rect width="5" height="7" x="16" y="14" rx="1.5"/>',
				'chevrons-down-up'    => '<path d="m7 20 5-5 5 5M7 4l5 5 5-5"/>',
				'map-pin'             => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
				'lock'                => '<rect width="16" height="11" x="4" y="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
				'key'                 => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.3-9.3M17 5l2.5 2.5M14 8l2.5 2.5"/>',
				'alert-triangle'      => '<path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3z"/><path d="M12 9v4M12 17h.01"/>',
				'external'            => '<path d="M14 4h6v6M20 4l-8 8M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',

				/* Block glyphs. */
				'gallery-horizontal'  => '<path d="M2 7v10M6 5v14"/><rect width="12" height="18" x="10" y="3" rx="2"/>',
				'move-horizontal'     => '<path d="m18 8 4 4-4 4M2 12h20M6 8l-4 4 4 4"/>',
				'quote'               => '<path d="M10 11H6.5A2.5 2.5 0 0 1 4 8.5v-.5A3 3 0 0 1 7 5h1M10 11v3a5 5 0 0 1-5 5"/><path d="M21 11h-3.5A2.5 2.5 0 0 1 15 8.5v-.5a3 3 0 0 1 3-3h1M21 11v3a5 5 0 0 1-5 5"/>',
				'images'             => '<rect width="16" height="16" x="6" y="2" rx="2"/><path d="M18 22H4a2 2 0 0 1-2-2V6"/><circle cx="12" cy="8" r="1.6"/><path d="m22 13-1.3-1.3a2.4 2.4 0 0 0-3.4 0L11 18"/>',
				'image'               => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="1.8"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
				'video'               => '<path d="m22 8-6 4 6 4z"/><rect width="14" height="12" x="2" y="6" rx="2"/>',
				'newspaper'           => '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2"/><path d="M18 14h-8M15 18h-5M10 6h8v4h-8z"/>',
				'gallery-thumbnails'  => '<rect width="18" height="14" x="3" y="3" rx="2"/><path d="M4 21h1M9.5 21h1M15 21h1M20 21h1"/>',
				'columns'             => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M12 3v18"/>',
				'blinds'              => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18M15 3v18"/>',
				'sparkles'            => '<path d="m12 3 1.9 5 5.1 1.9-5.1 1.9L12 17l-1.9-5.2L5 9.9 10.1 8z"/><path d="M19 15.5 19.7 18l2.3.8-2.3.8L19 22l-.7-2.4-2.3-.8 2.3-.8z"/>',
				'scissors'            => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M8.12 8.12 20 20M20 4 8.12 15.88"/>',
				'shirt'               => '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
				'rows'                => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M21 9H3M21 15H3"/>',
				'box'                 => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
				'panorama'            => '<rect width="20" height="12" x="2" y="6" rx="2"/><path d="m6 14 3-3 3 3 3-4 3 4"/>',
				'credit-card'         => '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>',
				'heart'               => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7z"/>',
				'pointer'             => '<path d="m3.7 3.04 6.5 16a.5.5 0 0 0 .95-.06l1.57-6.09a2 2 0 0 1 1.44-1.48l6.13-1.58a.5.5 0 0 0 .06-.94l-16-6.5a.5.5 0 0 0-.65.65z"/>',
				'shopping-bag'        => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/>',
				'tags'                => '<path d="M12 2H2v10l9.29 9.29a2 2 0 0 0 2.83 0l6.58-6.58a2 2 0 0 0 0-2.83z"/><path d="M7 7h.01"/>',
			);

			return $paths;
		}

		/**
		 * Render an inline icon.
		 *
		 * @param string $name    Icon name.
		 * @param string $classes Additional classes.
		 * @return string SVG markup, or an empty string for unknown icons.
		 */
		public static function get_icon( $name, $classes = '' ) {
			$paths = self::icon_paths();

			if ( ! isset( $paths[ $name ] ) ) {
				return '';
			}

			return sprintf(
				'<svg class="gs-icon%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
				$classes ? ' ' . esc_attr( $classes ) : '',
				$paths[ $name ]
			);
		}

		/**
		 * Echo an inline icon.
		 *
		 * @param string $name    Icon name.
		 * @param string $classes Additional classes.
		 * @return void
		 */
		public static function icon( $name, $classes = '' ) {
			echo self::get_icon( $name, $classes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static inline SVG.
		}

		/**
		 * Map a block to its glyph.
		 *
		 * @param string $block_name Block directory name.
		 * @return string Icon name.
		 */
		public static function block_icon( $block_name ) {
			$map = array(
				'content-slider'              => 'gallery-horizontal',
				'marquee'                     => 'move-horizontal',
				'any-content'                 => 'layout-template',
				'testimonial-slider'          => 'quote',
				'photo-carousel'              => 'images',
				'logo-carousel'               => 'gallery-thumbnails',
				'before-after'                => 'columns',
				'videos-carousel'             => 'video',
				'post-slider'                 => 'newspaper',
				'shader-slider'               => 'sparkles',
				'shutters-slider'             => 'blinds',
				'slicer-slider'               => 'scissors',
				'fashion-slider'              => 'shirt',
				'triple-slider'               => 'rows',
				'spring-carousel'             => 'box',
				'panorama-carousel'           => 'panorama',
				'three-d-carousel'            => 'box',
				'card-slider'                 => 'credit-card',
				'marquee-carousel'            => 'move-horizontal',
				'material-carousel'           => 'layout-grid',
				'tinder-slider'               => 'heart',
				'hover-slider'                => 'pointer',
				'product-carousel'            => 'shopping-bag',
				'product-categories-carousel' => 'tags',
			);

			return isset( $map[ $block_name ] ) ? $map[ $block_name ] : 'layers';
		}

		/* ---------------------------------------------------------------
		 * Views
		 * ------------------------------------------------------------- */

		/**
		 * Load a dashboard view.
		 *
		 * @param string               $view View file name without extension.
		 * @param array<string, mixed> $args Variables exposed to the view.
		 * @return void
		 */
		private function view( $view, array $args = array() ) {
			$file = GUTSLIDER_DIR . '/admin/views/' . $view . '.php';

			if ( ! file_exists( $file ) ) {
				return;
			}

			$admin   = $this;
			$has_pro = defined( 'GUTSLIDER_PRO_VERSION' );

			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Controlled, internal view data.
			extract( $args, EXTR_SKIP );

			include $file;
		}

		/**
		 * Render the dashboard (overview) screen.
		 *
		 * @return void
		 */
		public function render_dashboard_page() {
			$blocks = $this->get_blocks_with_state();

			$this->view(
				'dashboard',
				array(
					'blocks'    => $blocks,
					'total'     => count( $blocks ),
					'enabled'   => self::count_enabled( $blocks ),
					'current'   => self::MENU_SLUG,
					'settings'  => self::get_settings(),
					'usage'     => self::get_usage_stats(),
					'css_stats' => self::get_css_stats(),
					'changelog' => self::get_changelog(),
				)
			);
		}

		/**
		 * Render the blocks manager screen.
		 *
		 * @return void
		 */
		public function render_blocks_page() {
			$blocks = $this->get_blocks_with_state();

			$this->view(
				'blocks',
				array(
					'blocks'  => $blocks,
					'total'   => count( $blocks ),
					'enabled' => self::count_enabled( $blocks ),
					'current' => self::BLOCKS_SLUG,
				)
			);
		}

		/**
		 * Render the Free vs Pro screen.
		 *
		 * @return void
		 */
		public function render_compare_page() {
			$this->view(
				'compare-page',
				array(
					'blocks'  => $this->get_blocks_with_state(),
					'current' => self::COMPARE_SLUG,
				)
			);
		}

		/**
		 * Render the settings screen.
		 *
		 * @return void
		 */
		public function render_settings_page() {
			$this->view(
				'settings',
				array(
					'settings' => self::get_settings(),
					'current'  => self::SETTINGS_SLUG,
				)
			);
		}

		/* ---------------------------------------------------------------
		 * REST endpoints
		 * ------------------------------------------------------------- */

		/**
		 * Register the dashboard REST routes.
		 *
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				'gutslider/v1',
				'/dashboard/blocks',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rest_save_blocks' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'blocks' => array(
							'required' => true,
							'type'     => 'object',
						),
					),
				)
			);

			register_rest_route(
				'gutslider/v1',
				'/dashboard/settings',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rest_save_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'settings' => array(
							'required' => true,
							'type'     => 'object',
						),
					),
				)
			);

			register_rest_route(
				'gutslider/v1',
				'/dashboard/clear-cache',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rest_clear_cache' ),
					'permission_callback' => array( $this, 'can_manage' ),
				)
			);
		}

		/**
		 * Permission callback for the dashboard routes.
		 *
		 * @return bool True when the user may manage options.
		 */
		public function can_manage() {
			return current_user_can( 'manage_options' );
		}

		/**
		 * Persist the enabled state of one or more blocks.
		 *
		 * Writes both the per-block option consumed by the block registrar
		 * and the `active` flag on the cached block list, keeping the two
		 * storage formats in sync.
		 *
		 * @param WP_REST_Request $request Incoming request.
		 * @return WP_REST_Response|WP_Error Response payload.
		 */
		public function rest_save_blocks( WP_REST_Request $request ) {
			$requested = $request->get_param( 'blocks' );

			if ( ! is_array( $requested ) || empty( $requested ) ) {
				return new WP_Error(
					'gutslider_invalid_payload',
					__( 'No block changes were supplied.', 'slider-blocks' ),
					array( 'status' => 400 )
				);
			}

			$has_pro = defined( 'GUTSLIDER_PRO_VERSION' );
			$known   = array();

			foreach ( $this->get_blocks() as $block ) {
				$known[ $block['name'] ] = ! empty( $block['is_pro'] );
			}

			$saved = array();

			foreach ( $requested as $name => $enabled ) {
				$name = sanitize_text_field( (string) $name );

				if ( ! isset( $known[ $name ] ) ) {
					continue;
				}

				// Pro blocks cannot be enabled without the Pro plugin.
				if ( $known[ $name ] && ! $has_pro ) {
					continue;
				}

				$enabled = rest_sanitize_boolean( $enabled );

				/*
				 * Store '1'/'0' rather than a boolean: update_option() treats a
				 * `false` value as identical to a missing option and skips the
				 * write, which would silently drop the first "disable" of a
				 * block that has never been toggled before.
				 */
				update_option( self::block_option_key( $name ), $enabled ? '1' : '0' );
				$saved[ $name ] = $enabled;
			}

			$this->sync_block_list( $saved );
			self::complete_onboarding_step( 'blocks' );

			return rest_ensure_response(
				array(
					'success' => true,
					'blocks'  => $saved,
				)
			);
		}

		/**
		 * Mirror enabled states onto the cached `gutslider_blocks` option.
		 *
		 * @param array<string, bool> $saved Map of block name to enabled state.
		 * @return void
		 */
		private function sync_block_list( array $saved ) {
			if ( empty( $saved ) ) {
				return;
			}

			$blocks = get_option( 'gutslider_blocks' );

			if ( ! is_array( $blocks ) || empty( $blocks ) ) {
				return;
			}

			$changed = false;

			foreach ( $blocks as $index => $block ) {
				if ( isset( $block['name'], $saved[ $block['name'] ] ) ) {
					$blocks[ $index ]['active'] = $saved[ $block['name'] ];
					$changed                    = true;
				}
			}

			if ( $changed ) {
				update_option( 'gutslider_blocks', $blocks );
			}
		}

		/**
		 * Persist the general settings.
		 *
		 * @param WP_REST_Request $request Incoming request.
		 * @return WP_REST_Response|WP_Error Response payload.
		 */
		public function rest_save_settings( WP_REST_Request $request ) {
			$incoming = $request->get_param( 'settings' );

			if ( ! is_array( $incoming ) ) {
				return new WP_Error(
					'gutslider_invalid_payload',
					__( 'No settings were supplied.', 'slider-blocks' ),
					array( 'status' => 400 )
				);
			}

			$settings          = self::get_settings();
			$previous_delivery = $settings['css_delivery'];

			if ( isset( $incoming['css_delivery'] ) ) {
				$settings['css_delivery'] = in_array( $incoming['css_delivery'], array( 'file', 'inline' ), true )
					? $incoming['css_delivery']
					: 'file';
			}

			if ( isset( $incoming['google_fonts'] ) ) {
				$settings['google_fonts'] = rest_sanitize_boolean( $incoming['google_fonts'] );
			}

			if ( isset( $incoming['pattern_library'] ) ) {
				$settings['pattern_library'] = rest_sanitize_boolean( $incoming['pattern_library'] );
			}

			if ( isset( $incoming['remove_data'] ) ) {
				$settings['remove_data'] = rest_sanitize_boolean( $incoming['remove_data'] );
			}

			update_option( self::SETTINGS_OPTION, $settings );
			self::complete_onboarding_step( 'settings' );

			/*
			 * Switching to inline delivery leaves the previously generated
			 * stylesheets orphaned in the uploads directory, so clear them out.
			 */
			if ( 'inline' === $settings['css_delivery'] && 'inline' !== $previous_delivery ) {
				self::delete_generated_css();
			}

			return rest_ensure_response(
				array(
					'success'  => true,
					'settings' => $settings,
				)
			);
		}

		/**
		 * Delete every generated stylesheet in the uploads directory.
		 *
		 * @return WP_REST_Response Response payload.
		 */
		public function rest_clear_cache() {
			return rest_ensure_response(
				array(
					'success' => true,
					'deleted' => self::delete_generated_css(),
				)
			);
		}

		/**
		 * Remove all generated stylesheets from the uploads directory.
		 *
		 * @return int Number of files deleted.
		 */
		private static function delete_generated_css() {
			$upload_dir = wp_upload_dir();
			$css_dir    = trailingslashit( $upload_dir['basedir'] ) . 'gutslider-styles';
			$deleted    = 0;

			if ( is_dir( $css_dir ) ) {
				$files = glob( $css_dir . '/*.css' );

				if ( is_array( $files ) ) {
					foreach ( $files as $file ) {
						if ( is_file( $file ) && wp_delete_file_from_directory( $file, $css_dir ) ) {
							++$deleted;
						}
					}
				}
			}

			return $deleted;
		}
	}
}

new GutSlider_Admin();
