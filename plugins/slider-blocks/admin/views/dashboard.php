<?php
/**
 * Dashboard (overview) screen.
 *
 * @package GutSliderBlocks
 *
 * @var GutSlider_Admin     $admin     Admin controller.
 * @var array               $blocks    Decorated block definitions.
 * @var int                 $total     Total block count.
 * @var int                 $enabled   Enabled block count.
 * @var string              $current   Current page slug.
 * @var bool                $has_pro   Whether the Pro plugin is active.
 * @var array<string,mixed> $settings  General settings.
 * @var array               $usage     Cached content usage figures.
 * @var array               $css_stats Generated stylesheet figures.
 * @var array               $changelog Release notes for this version.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gs_free_total = 0;
$gs_pro_total  = 0;

foreach ( $blocks as $gs_block ) {
	if ( ! empty( $gs_block['is_pro'] ) ) {
		++$gs_pro_total;
	} else {
		++$gs_free_total;
	}
}

$gs_disabled = max( 0, (int) $total - (int) $enabled );
$gs_license  = GutSlider_Admin::get_license();
$gs_notice   = GutSlider_Admin::get_dashboard_notice();
$gs_blocks_url   = admin_url( 'admin.php?page=gutslider-blocks-settings' );
$gs_settings_url = admin_url( 'admin.php?page=gutslider-settings' );

/*
 * Front-end weight is reported as the average size of one generated
 * stylesheet, which is what a single page actually downloads.
 */
$gs_inline    = isset( $settings['css_delivery'] ) && 'inline' === $settings['css_delivery'];
$gs_weight_kb = $css_stats['average'] > 0 ? max( 1, (int) round( $css_stats['average'] / 1024 ) ) : 0;
$gs_fonts_on  = ! empty( $settings['google_fonts'] );

$gs_steps = array(
	array(
		'n'     => '01',
		'title' => __( 'Pick your blocks', 'slider-blocks' ),
		'text'  => __( 'Disable anything you will not use so the editor stays fast.', 'slider-blocks' ),
		'link'  => $gs_blocks_url,
		'label' => __( 'Open blocks', 'slider-blocks' ),
		'done'  => GutSlider_Admin::onboarding_done( 'blocks' ),
	),
	array(
		'n'     => '02',
		'title' => __( 'Insert & style', 'slider-blocks' ),
		'text'  => __( 'Search "GutSlider" in the inserter, then style it in the sidebar.', 'slider-blocks' ),
		'link'  => 'https://gutslider.com/docs',
		'label' => __( 'Read the docs', 'slider-blocks' ),
		'ext'   => true,
		'done'  => $usage['sliders'] > 0,
	),
	array(
		'n'     => '03',
		'title' => __( 'Tune performance', 'slider-blocks' ),
		'text'  => __( 'Choose how CSS is delivered and whether fonts load on the front end.', 'slider-blocks' ),
		'link'  => $gs_settings_url,
		'label' => __( 'Open settings', 'slider-blocks' ),
		'done'  => GutSlider_Admin::onboarding_done( 'settings' ),
	),
);

$gs_steps_done = 0;

foreach ( $gs_steps as $gs_step ) {
	if ( ! empty( $gs_step['done'] ) ) {
		++$gs_steps_done;
	}
}

require GUTSLIDER_DIR . '/admin/views/header.php';
?>

<div class="gs-shell gs-page" data-gs-blocks>

	<?php if ( $gs_notice ) : ?>
		<div class="gs-banner gs-banner--<?php echo esc_attr( $gs_notice['tone'] ); ?>">
			<span class="gs-banner__icon" aria-hidden="true">
				<?php GutSlider_Admin::icon( 'error' === $gs_notice['tone'] ? 'alert-triangle' : 'key' ); ?>
			</span>
			<div class="gs-banner__text">
				<span class="gs-banner__title"><?php echo esc_html( $gs_notice['title'] ); ?></span>
				<?php if ( '' !== $gs_notice['text'] ) : ?>
					<p class="gs-banner__sub"><?php echo esc_html( $gs_notice['text'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $gs_notice['action_label'] && '' !== $gs_notice['action_url'] ) : ?>
				<a class="gs-btn gs-btn--primary" href="<?php echo esc_url( $gs_notice['action_url'] ); ?>">
					<?php echo esc_html( $gs_notice['action_label'] ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="gs-page-head">
		<div>
			<h1 class="gs-page-title"><?php esc_html_e( 'Dashboard', 'slider-blocks' ); ?></h1>
			<p class="gs-page-sub">
				<?php esc_html_e( 'Every slider, carousel and marquee you need — built natively for the block editor.', 'slider-blocks' ); ?>
			</p>
		</div>
		<div class="gs-page-head__actions">
			<?php if ( $gs_license ) : ?>
				<span class="gs-pill gs-pill--<?php echo esc_attr( $gs_license['state'] ); ?>">
					<span class="gs-pill__dot" aria-hidden="true"></span>
					<?php echo esc_html( $gs_license['label'] ); ?>
				</span>
			<?php endif; ?>
			<a class="gs-btn gs-btn--quiet" href="<?php echo esc_url( $gs_blocks_url ); ?>">
				<?php GutSlider_Admin::icon( 'sliders-horizontal' ); ?>
				<?php esc_html_e( 'Manage blocks', 'slider-blocks' ); ?>
			</a>
		</div>
	</div>

	<div class="gs-metrics">
		<div class="gs-metric">
			<div class="gs-metric__head">
				<span class="gs-metric__label"><?php esc_html_e( 'Blocks active', 'slider-blocks' ); ?></span>
				<span class="gs-metric__icon"><?php GutSlider_Admin::icon( 'layout-grid' ); ?></span>
			</div>
			<p class="gs-metric__value">
				<span data-gs-metric-enabled><?php echo esc_html( (string) $enabled ); ?></span>
				<span class="gs-metric__unit"><?php echo esc_html( '/ ' . $total ); ?></span>
			</p>
			<p class="gs-metric__foot" data-gs-metric-note>
				<?php
				if ( $gs_disabled > 0 ) {
					printf(
						/* translators: %d: number of disabled blocks. */
						esc_html__( '%d turned off', 'slider-blocks' ),
						(int) $gs_disabled
					);
				} else {
					esc_html_e( 'All blocks enabled', 'slider-blocks' );
				}
				?>
			</p>
		</div>

		<div class="gs-metric">
			<div class="gs-metric__head">
				<span class="gs-metric__label"><?php esc_html_e( 'Sliders in use', 'slider-blocks' ); ?></span>
				<span class="gs-metric__icon"><?php GutSlider_Admin::icon( 'layers' ); ?></span>
			</div>
			<p class="gs-metric__value"><?php echo esc_html( number_format_i18n( $usage['sliders'] ) ); ?></p>
			<p class="gs-metric__foot">
				<?php
				if ( $usage['sliders'] > 0 ) {
					printf(
						/* translators: %s: number of posts. */
						esc_html__( 'Across %s pages and posts', 'slider-blocks' ),
						esc_html( number_format_i18n( $usage['posts'] ) . ( $usage['capped'] ? '+' : '' ) )
					);
				} else {
					esc_html_e( 'None added to content yet', 'slider-blocks' );
				}
				?>
			</p>
		</div>

		<div class="gs-metric">
			<div class="gs-metric__head">
				<span class="gs-metric__label"><?php esc_html_e( 'Front-end weight', 'slider-blocks' ); ?></span>
				<span class="gs-metric__icon"><?php GutSlider_Admin::icon( 'gauge' ); ?></span>
			</div>
			<p class="gs-metric__value">
				<?php if ( $gs_weight_kb > 0 ) : ?>
					<?php echo esc_html( number_format_i18n( $gs_weight_kb ) ); ?>
					<span class="gs-metric__unit"><?php esc_html_e( 'KB', 'slider-blocks' ); ?></span>
				<?php else : ?>
					&mdash;
				<?php endif; ?>
			</p>
			<p class="gs-metric__foot">
				<?php
				if ( $gs_inline ) {
					esc_html_e( 'Inline delivery — nothing cached', 'slider-blocks' );
				} elseif ( $css_stats['files'] > 0 ) {
					printf(
						/* translators: %s: number of generated stylesheets. */
						esc_html__( 'Average of %s cached stylesheets', 'slider-blocks' ),
						esc_html( number_format_i18n( $css_stats['files'] ) )
					);
				} else {
					esc_html_e( 'No pages rendered yet', 'slider-blocks' );
				}
				?>
			</p>
		</div>

		<div class="gs-metric">
			<div class="gs-metric__head">
				<span class="gs-metric__label"><?php esc_html_e( 'Google Fonts', 'slider-blocks' ); ?></span>
				<span class="gs-metric__icon"><?php GutSlider_Admin::icon( 'type' ); ?></span>
			</div>
			<p class="gs-metric__value">
				<?php echo $gs_fonts_on ? esc_html__( 'On', 'slider-blocks' ) : esc_html__( 'Off', 'slider-blocks' ); ?>
			</p>
			<p class="gs-metric__foot">
				<?php
				echo $gs_fonts_on
					? esc_html__( 'Served from Google on the front end', 'slider-blocks' )
					: esc_html__( 'Not loaded on the front end', 'slider-blocks' );
				?>
			</p>
		</div>
	</div>

	<div class="gs-cols">
		<div class="gs-col">

			<section class="gs-card gs-card--flush">
				<header class="gs-card__head">
					<div>
						<h2 class="gs-card__heading"><?php esc_html_e( 'Your blocks', 'slider-blocks' ); ?></h2>
						<p class="gs-card__sub"><?php esc_html_e( 'Turn off what you don’t use to keep the editor light.', 'slider-blocks' ); ?></p>
					</div>
					<div class="gs-search gs-search--sm">
						<?php GutSlider_Admin::icon( 'search' ); ?>
						<input
							type="search"
							class="gs-search__input"
							data-gs-search
							placeholder="<?php esc_attr_e( 'Search blocks', 'slider-blocks' ); ?>"
							aria-label="<?php esc_attr_e( 'Search blocks', 'slider-blocks' ); ?>"
							autocomplete="off"
						/>
					</div>
				</header>

				<div class="gs-rows" data-gs-grid>
					<?php foreach ( $blocks as $gs_block ) : ?>
						<?php
						$gs_is_pro  = ! empty( $gs_block['is_pro'] );
						$gs_locked  = ! empty( $gs_block['locked'] );
						$gs_on      = ! empty( $gs_block['enabled'] );
						$gs_toggle  = 'gs-row-' . sanitize_html_class( $gs_block['name'] );
						$gs_classes = 'gs-row';
						$gs_classes .= $gs_locked ? ' is-locked' : '';
						$gs_classes .= $gs_on ? ' is-on' : ' is-off';
						?>
						<div
							class="<?php echo esc_attr( $gs_classes ); ?>"
							data-gs-block="<?php echo esc_attr( $gs_block['name'] ); ?>"
							data-gs-tier="<?php echo $gs_is_pro ? 'pro' : 'free'; ?>"
							data-gs-locked="<?php echo $gs_locked ? '1' : '0'; ?>"
							data-gs-initial="<?php echo $gs_on ? '1' : '0'; ?>"
							data-gs-name="<?php echo esc_attr( strtolower( $gs_block['title'] . ' ' . $gs_block['description'] ) ); ?>"
						>
							<span class="gs-row__icon">
								<?php GutSlider_Admin::icon( GutSlider_Admin::block_icon( $gs_block['name'] ) ); ?>
							</span>

							<div class="gs-row__text">
								<span class="gs-row__name"><?php echo esc_html( $gs_block['title'] ); ?></span>
								<span class="gs-row__desc"><?php echo esc_html( $gs_block['description'] ); ?></span>
							</div>

							<span class="gs-tag<?php echo $gs_is_pro ? ' gs-tag--pro' : ''; ?>">
								<?php echo $gs_is_pro ? esc_html__( 'Pro', 'slider-blocks' ) : esc_html__( 'Free', 'slider-blocks' ); ?>
							</span>

							<?php if ( $gs_locked ) : ?>
								<a class="gs-lock" href="https://gutslider.com/pricing" target="_blank" rel="noopener noreferrer">
									<?php GutSlider_Admin::icon( 'lock' ); ?>
									<span class="screen-reader-text">
										<?php
										printf(
											/* translators: %s: block title. */
											esc_html__( 'Unlock the %s block', 'slider-blocks' ),
											esc_html( $gs_block['title'] )
										);
										?>
									</span>
								</a>
							<?php else : ?>
								<label class="gs-switch" for="<?php echo esc_attr( $gs_toggle ); ?>">
									<span class="screen-reader-text">
										<?php
										printf(
											/* translators: %s: block title. */
											esc_html__( 'Enable the %s block', 'slider-blocks' ),
											esc_html( $gs_block['title'] )
										);
										?>
									</span>
									<input
										type="checkbox"
										id="<?php echo esc_attr( $gs_toggle ); ?>"
										class="gs-switch__input"
										data-gs-toggle="<?php echo esc_attr( $gs_block['name'] ); ?>"
										<?php checked( $gs_on ); ?>
									/>
									<span class="gs-switch__track" aria-hidden="true"><span class="gs-switch__thumb"></span></span>
								</label>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="gs-empty gs-empty--sm" data-gs-empty hidden>
					<?php GutSlider_Admin::icon( 'search' ); ?>
					<p><?php esc_html_e( 'No blocks match your search.', 'slider-blocks' ); ?></p>
				</div>

				<footer class="gs-card__foot">
					<span class="gs-card__summary" data-gs-summary>
						<?php
						printf(
							/* translators: 1: free block count, 2: pro block count, 3: enabled count, 4: total count. */
							esc_html__( '%1$d free blocks · %2$d pro blocks · %3$d of %4$d enabled', 'slider-blocks' ),
							(int) $gs_free_total,
							(int) $gs_pro_total,
							(int) $enabled,
							(int) $total
						);
						?>
					</span>
					<a class="gs-link" href="<?php echo esc_url( $gs_blocks_url ); ?>">
						<?php esc_html_e( 'View all blocks', 'slider-blocks' ); ?>
						<?php GutSlider_Admin::icon( 'arrow-right' ); ?>
					</a>
				</footer>
			</section>

			<section class="gs-card gs-card--pad">
				<header class="gs-card__head gs-card__head--plain">
					<div>
						<h2 class="gs-card__heading"><?php esc_html_e( 'Get started', 'slider-blocks' ); ?></h2>
						<p class="gs-card__sub"><?php esc_html_e( 'Three minutes from install to your first slider.', 'slider-blocks' ); ?></p>
					</div>
					<div class="gs-progress">
						<span class="gs-progress__count">
							<?php
							printf(
								/* translators: 1: completed step count, 2: total step count. */
								esc_html__( '%1$d of %2$d done', 'slider-blocks' ),
								(int) $gs_steps_done,
								count( $gs_steps )
							);
							?>
						</span>
						<span class="gs-progress__track">
							<span
								class="gs-progress__fill"
								style="width:<?php echo esc_attr( (string) round( ( $gs_steps_done / count( $gs_steps ) ) * 100 ) ); ?>%"
							></span>
						</span>
					</div>
				</header>

				<div class="gs-steps">
					<?php foreach ( $gs_steps as $gs_step ) : ?>
						<div class="gs-step<?php echo ! empty( $gs_step['done'] ) ? ' is-done' : ''; ?>">
							<div class="gs-step__top">
								<?php if ( ! empty( $gs_step['done'] ) ) : ?>
									<span class="gs-step__check" aria-hidden="true"><?php GutSlider_Admin::icon( 'check' ); ?></span>
								<?php else : ?>
									<span class="gs-step__n"><?php echo esc_html( $gs_step['n'] ); ?></span>
								<?php endif; ?>
								<h3 class="gs-step__title"><?php echo esc_html( $gs_step['title'] ); ?></h3>
							</div>
							<p class="gs-step__text"><?php echo esc_html( $gs_step['text'] ); ?></p>
							<a class="gs-link" href="<?php echo esc_url( $gs_step['link'] ); ?>" <?php echo empty( $gs_step['ext'] ) ? '' : 'target="_blank" rel="noopener noreferrer"'; ?>>
								<?php echo esc_html( $gs_step['label'] ); ?>
								<?php GutSlider_Admin::icon( 'arrow-right' ); ?>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		</div>

		<aside class="gs-col gs-col--side">

			<section class="gs-card gs-card--flush">
				<button
					type="button"
					class="gs-video"
					data-gs-video="https://www.youtube-nocookie.com/embed/Hob3WgBTVZI?autoplay=1"
					data-gs-video-title="<?php esc_attr_e( 'GutSlider: all in one slider and carousel blocks', 'slider-blocks' ); ?>"
				>
					<span class="gs-video__stage" aria-hidden="true">
						<span class="gs-video__slides">
							<span class="gs-video__slide"></span>
							<span class="gs-video__slide gs-video__slide--main"></span>
							<span class="gs-video__slide"></span>
						</span>
						<span class="gs-video__dots">
							<span class="gs-video__dot is-active"></span>
							<span class="gs-video__dot"></span>
							<span class="gs-video__dot"></span>
						</span>
						<span class="gs-video__play"><?php GutSlider_Admin::icon( 'play' ); ?></span>
					</span>
					<span class="screen-reader-text"><?php esc_html_e( 'Play the GutSlider video', 'slider-blocks' ); ?></span>
				</button>
				<div class="gs-card__body">
					<span class="gs-eyebrow"><?php esc_html_e( 'Watch the video', 'slider-blocks' ); ?></span>
					<h2 class="gs-card__heading"><?php esc_html_e( 'See GutSlider in action', 'slider-blocks' ); ?></h2>
					<p class="gs-card__sub"><?php esc_html_e( 'The blocks, the ready-made designs and what Pro adds, in one short video.', 'slider-blocks' ); ?></p>
				</div>
			</section>

			<?php if ( ! empty( $changelog ) ) : ?>
				<section class="gs-card gs-card--pad">
					<header class="gs-card__head gs-card__head--plain">
						<h2 class="gs-card__heading"><?php esc_html_e( 'What’s new', 'slider-blocks' ); ?></h2>
						<span class="gs-chip gs-chip--pro"><?php echo esc_html( 'v' . GUTSLIDER_VERSION ); ?></span>
					</header>

					<ul class="gs-news">
						<?php foreach ( $changelog as $gs_entry ) : ?>
							<li class="gs-news__item">
								<span class="gs-tag gs-tag--<?php echo esc_attr( $gs_entry['tag'] ); ?>"><?php echo esc_html( $gs_entry['label'] ); ?></span>
								<span class="gs-news__text"><?php echo esc_html( $gs_entry['text'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>

					<a class="gs-link" href="https://wordpress.org/plugins/slider-blocks/#developers" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Read the changelog', 'slider-blocks' ); ?>
						<?php GutSlider_Admin::icon( 'arrow-right' ); ?>
					</a>
				</section>
			<?php endif; ?>

			<section class="gs-card gs-card--flush">
				<header class="gs-card__head gs-card__head--tight">
					<h2 class="gs-card__heading"><?php esc_html_e( 'Help & support', 'slider-blocks' ); ?></h2>
				</header>

				<?php
				$gs_support = array(
					array(
						'icon'  => 'book-open',
						'title' => __( 'Documentation', 'slider-blocks' ),
						'text'  => __( 'Guides and block references', 'slider-blocks' ),
						'link'  => 'https://gutslider.com/docs',
					),
					array(
						'icon'  => 'life-buoy',
						'title' => __( 'Open a ticket', 'slider-blocks' ),
						'text'  => __( 'Usually answered within a day', 'slider-blocks' ),
						'link'  => 'https://gutslider.com/support',
					),
					array(
						'icon'  => 'star',
						'title' => __( 'Leave a review', 'slider-blocks' ),
						'text'  => __( 'Reviews keep GutSlider free', 'slider-blocks' ),
						'link'  => 'https://wordpress.org/support/plugin/slider-blocks/reviews/',
					),
				);
				?>
				<?php foreach ( $gs_support as $gs_item ) : ?>
					<a class="gs-help" href="<?php echo esc_url( $gs_item['link'] ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="gs-help__icon"><?php GutSlider_Admin::icon( $gs_item['icon'] ); ?></span>
						<span class="gs-help__text">
							<span class="gs-help__title"><?php echo esc_html( $gs_item['title'] ); ?></span>
							<span class="gs-help__sub"><?php echo esc_html( $gs_item['text'] ); ?></span>
						</span>
						<?php GutSlider_Admin::icon( 'chevron-right', 'gs-help__chevron' ); ?>
					</a>
				<?php endforeach; ?>
			</section>
		</aside>
	</div>

	<section class="gs-workshop">
		<div class="gs-workshop__head">
			<h2 class="gs-card__heading"><?php esc_html_e( 'More from the workshop', 'slider-blocks' ); ?></h2>
			<p class="gs-card__sub"><?php esc_html_e( 'Other block plugins built with the same care.', 'slider-blocks' ); ?></p>
		</div>

		<div class="gs-workshop__grid">
			<?php
			$gs_products = array(
				array(
					'icon'  => 'layout-template',
					'title' => __( 'GutenLayouts', 'slider-blocks' ),
					'text'  => __( 'Ready-made layouts and patterns built entirely from core blocks.', 'slider-blocks' ),
					'link'  => 'https://gutenlayouts.com',
				),
				array(
					'icon'  => 'chevrons-down-up',
					'title' => __( 'Easy Accordion', 'slider-blocks' ),
					'text'  => __( 'Accessible, responsive accordions with a block-native editing flow.', 'slider-blocks' ),
					'link'  => 'https://accordion.gutenbergkits.com',
				),
				array(
					'icon'  => 'map-pin',
					'title' => __( 'Gmap Block', 'slider-blocks' ),
					'text'  => __( 'Interactive Google Maps with markers, styles and zero fuss.', 'slider-blocks' ),
					'link'  => 'https://gmap.gutenbergkits.com',
				),
			);
			?>
			<?php foreach ( $gs_products as $gs_product ) : ?>
				<a class="gs-card gs-card--pad gs-card--link" href="<?php echo esc_url( $gs_product['link'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="gs-product__top">
						<span class="gs-help__icon"><?php GutSlider_Admin::icon( $gs_product['icon'] ); ?></span>
						<span class="gs-product__title"><?php echo esc_html( $gs_product['title'] ); ?></span>
					</span>
					<span class="gs-card__sub"><?php echo esc_html( $gs_product['text'] ); ?></span>
					<span class="gs-link">
						<?php esc_html_e( 'Take a look', 'slider-blocks' ); ?>
						<?php GutSlider_Admin::icon( 'arrow-right' ); ?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
</div>

<?php
require GUTSLIDER_DIR . '/admin/views/footer.php';
