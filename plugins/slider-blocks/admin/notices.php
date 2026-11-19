<?php
/**
 * GutSlider admin notices.
 *
 * One dismissible notice at a time on the WP Dashboard, the Plugins screen
 * and the GutSlider screens: a review request for sites that use GutSlider,
 * and dated campaign notices (e.g. Black Friday) for sites without Pro.
 *
 * @package GutSliderBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GutSlider_Notices' ) ) {

	/**
	 * Admin notices controller.
	 */
	class GutSlider_Notices {

		/**
		 * Option holding the time GutSlider was first seen in wp-admin.
		 *
		 * @var string
		 */
		const INSTALLED_OPTION = 'gutslider_installed_at';

		/**
		 * User meta prefix for per-notice state (dismissed or snoozed).
		 *
		 * @var string
		 */
		const META_PREFIX = 'gutslider_notice_';

		/**
		 * Query arg used by the notice action links.
		 *
		 * @var string
		 */
		const ACTION_ARG = 'gutslider_notice';

		/**
		 * Days after the first admin visit before asking for a review.
		 *
		 * @var int
		 */
		const REVIEW_DELAY_DAYS = 7;

		/**
		 * Days a "Maybe later" hides the review request.
		 *
		 * @var int
		 */
		const REVIEW_SNOOZE_DAYS = 30;

		/**
		 * Hook the notices in.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'remember_install_time' ) );
			add_action( 'admin_init', array( $this, 'handle_action' ) );
			add_action( 'admin_notices', array( $this, 'render' ) );
		}

		/**
		 * Record when GutSlider was first seen, so the review request can wait.
		 *
		 * @return void
		 */
		public function remember_install_time() {
			if ( ! get_option( self::INSTALLED_OPTION ) ) {
				add_option( self::INSTALLED_OPTION, time(), '', false );
			}
		}

		/**
		 * Dated campaign notices for sites without GutSlider Pro.
		 *
		 * Times are UTC. Each notice shows between `start` and `end` and can be
		 * dismissed once per user.
		 *
		 * @return array<int, array<string, string>> Campaign definitions.
		 */
		public static function campaigns() {
			$utm = '?utm_source=plugin&utm_medium=admin-notice&utm_campaign=bf2026';

			$campaigns = array(
				array(
					'id'           => 'bf2026_early',
					'start'        => '2026-11-10 00:00:00',
					'end'          => '2026-11-23 23:59:59',
					'title'        => __( 'Early Black Friday: GutSlider Pro lifetime from $69', 'slider-blocks' ),
					'text'         => __( 'The Black Friday prices are live now: lifetime from $69 and 40% off yearly plans. 15 Pro blocks, 70+ designs and WooCommerce carousels. Ends 1 December.', 'slider-blocks' ),
					'action_label' => __( 'Get the deal', 'slider-blocks' ),
					'action_url'   => 'https://gutslider.com/black-friday/' . $utm . '&utm_content=early',
				),
				array(
					'id'           => 'bf2026_sale',
					'start'        => '2026-11-24 00:00:00',
					'end'          => '2026-12-01 23:59:59',
					'title'        => __( 'Black Friday: GutSlider Pro lifetime from $69', 'slider-blocks' ),
					'text'         => __( 'Lifetime from $69 and 40% off yearly plans. 15 Pro blocks, 70+ designs and WooCommerce carousels. Ends 1 December.', 'slider-blocks' ),
					'action_label' => __( 'Get the deal', 'slider-blocks' ),
					'action_url'   => 'https://gutslider.com/black-friday/' . $utm . '&utm_content=sale',
				),
			);

			/**
			 * Filter the dated campaign notices.
			 *
			 * @param array $campaigns Campaign definitions.
			 */
			return apply_filters( 'gutslider_campaign_notices', $campaigns );
		}

		/**
		 * Whether the current user may see notices on the current screen.
		 *
		 * @return bool True when a notice may be shown.
		 */
		private static function can_show() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

			if ( ! $screen || $screen->is_block_editor() ) {
				return false;
			}

			return in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) || GutSlider_Admin::is_dashboard_screen( $screen->id );
		}

		/**
		 * The notice to show now, if any. Campaigns win over the review request.
		 *
		 * @return array<string, string>|null Notice definition.
		 */
		private static function current_notice() {
			$user_id = get_current_user_id();

			if ( ! defined( 'GUTSLIDER_PRO_PATH' ) ) {
				$now = time();

				foreach ( self::campaigns() as $campaign ) {
					$start = strtotime( $campaign['start'] . ' UTC' );
					$end   = strtotime( $campaign['end'] . ' UTC' );

					if ( $now >= $start && $now <= $end && ! get_user_meta( $user_id, self::META_PREFIX . $campaign['id'], true ) ) {
						return $campaign + array( 'type' => 'campaign' );
					}
				}
			}

			return self::review_notice( $user_id );
		}

		/**
		 * The review request, when it is due for this user.
		 *
		 * Waits a week after GutSlider is first seen, needs at least one
		 * GutSlider block in use, and respects "Maybe later" and "I already did".
		 *
		 * @param int $user_id Current user ID.
		 * @return array<string, string>|null Notice definition.
		 */
		private static function review_notice( $user_id ) {
			$installed = (int) get_option( self::INSTALLED_OPTION, time() );

			if ( time() - $installed < self::REVIEW_DELAY_DAYS * DAY_IN_SECONDS ) {
				return null;
			}

			$state = get_user_meta( $user_id, self::META_PREFIX . 'review', true );

			if ( 'done' === $state || ( is_numeric( $state ) && time() < (int) $state ) ) {
				return null;
			}

			$usage = GutSlider_Admin::get_usage_stats();

			if ( empty( $usage['sliders'] ) ) {
				return null;
			}

			return array(
				'type'         => 'review',
				'id'           => 'review',
				'title'        => __( 'Enjoying GutSlider? A quick review would mean a lot', 'slider-blocks' ),
				/* translators: %d: number of GutSlider sliders on the site. */
				'text'         => sprintf( _n( 'You have %d GutSlider slider on your site. GutSlider is built by a tiny team, and an honest review on WordPress.org helps other people find it.', 'You have %d GutSlider sliders on your site. GutSlider is built by a tiny team, and an honest review on WordPress.org helps other people find it.', (int) $usage['sliders'], 'slider-blocks' ), (int) $usage['sliders'] ),
				'action_label' => __( 'Leave a review', 'slider-blocks' ),
				'action_url'   => 'https://wordpress.org/support/plugin/slider-blocks/reviews/#new-post',
			);
		}

		/**
		 * Signed URL for a notice action (dismiss, later, done).
		 *
		 * @param string $id     Notice ID.
		 * @param string $action Action name.
		 * @return string Action URL.
		 */
		private static function action_url( $id, $action ) {
			return wp_nonce_url(
				add_query_arg(
					array(
						self::ACTION_ARG => $id,
						'gutslider_do'   => $action,
					)
				),
				'gutslider_notice_' . $id
			);
		}

		/**
		 * Store a notice action for the current user, then return to the page.
		 *
		 * @return void
		 */
		public function handle_action() {
			if ( empty( $_GET[ self::ACTION_ARG ] ) || empty( $_GET['gutslider_do'] ) ) {
				return;
			}

			$id     = sanitize_key( wp_unslash( $_GET[ self::ACTION_ARG ] ) );
			$action = sanitize_key( wp_unslash( $_GET['gutslider_do'] ) );

			check_admin_referer( 'gutslider_notice_' . $id );

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$value = 'later' === $action ? time() + self::REVIEW_SNOOZE_DAYS * DAY_IN_SECONDS : 'done';

			update_user_meta( get_current_user_id(), self::META_PREFIX . $id, $value );

			wp_safe_redirect( remove_query_arg( array( self::ACTION_ARG, 'gutslider_do', '_wpnonce' ) ) );
			exit;
		}

		/**
		 * Print the current notice.
		 *
		 * @return void
		 */
		public function render() {
			if ( ! self::can_show() ) {
				return;
			}

			$notice = self::current_notice();

			if ( ! $notice ) {
				return;
			}

			$is_review = 'review' === $notice['type'];
			$done_url  = self::action_url( $notice['id'], 'done' );
			?>
			<div class="notice notice-info gutslider-notice" style="display:flex;gap:14px;align-items:center;padding:12px 14px;border-left-color:#5325e8;">
				<img src="<?php echo esc_url( GUTSLIDER_URL . 'admin/img/gutslider.svg' ); ?>" alt="" width="40" height="40" style="flex:none;">
				<div style="flex:1;min-width:0;">
					<p style="margin:0 0 4px;font-size:14px;"><strong><?php echo esc_html( $notice['title'] ); ?></strong></p>
					<p style="margin:0 0 8px;"><?php echo esc_html( $notice['text'] ); ?></p>
					<p style="margin:0;display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;">
						<a class="button button-primary" href="<?php echo esc_url( $notice['action_url'] ); ?>" target="_blank" rel="noopener noreferrer"
							<?php if ( $is_review ) : ?>
							onclick="setTimeout(function(){window.location.href=<?php echo esc_attr( wp_json_encode( $done_url ) ); ?>;},300);"
							<?php endif; ?>
						><?php echo esc_html( $notice['action_label'] ); ?></a>
						<?php if ( $is_review ) : ?>
							<a href="<?php echo esc_url( self::action_url( $notice['id'], 'later' ) ); ?>"><?php esc_html_e( 'Maybe later', 'slider-blocks' ); ?></a>
							<a href="<?php echo esc_url( $done_url ); ?>"><?php esc_html_e( 'I already did', 'slider-blocks' ); ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( $done_url ); ?>"><?php esc_html_e( 'Dismiss', 'slider-blocks' ); ?></a>
						<?php endif; ?>
					</p>
				</div>
			</div>
			<?php
		}
	}
}

new GutSlider_Notices();
