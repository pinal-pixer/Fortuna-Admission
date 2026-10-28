<?php
/**
 * Shared dashboard shell header.
 *
 * @package GutSliderBlocks
 *
 * @var string $current Current page slug.
 * @var bool   $has_pro Whether the Pro plugin is active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gs_current = isset( $current ) ? $current : '';
$gs_has_pro = isset( $has_pro ) ? (bool) $has_pro : defined( 'GUTSLIDER_PRO_VERSION' );

$gs_nav = array(
	array(
		'slug'  => 'gutslider-blocks',
		'label' => __( 'Dashboard', 'slider-blocks' ),
		'url'   => admin_url( 'admin.php?page=gutslider-blocks' ),
	),
	array(
		'slug'  => 'gutslider-blocks-settings',
		'label' => __( 'Blocks', 'slider-blocks' ),
		'url'   => admin_url( 'admin.php?page=gutslider-blocks-settings' ),
	),
	array(
		'slug'  => 'gutslider-settings',
		'label' => __( 'Settings', 'slider-blocks' ),
		'url'   => admin_url( 'admin.php?page=gutslider-settings' ),
	),
);

if ( $gs_has_pro ) {
	$gs_nav[] = array(
		'slug'  => 'gutslider-license',
		'label' => __( 'License', 'slider-blocks' ),
		'url'   => admin_url( 'admin.php?page=gutslider-license' ),
	);
}

if ( GutSlider_Admin::show_compare() ) {
	$gs_nav[] = array(
		'slug'    => GutSlider_Admin::COMPARE_SLUG,
		'label'   => __( 'Free vs Pro', 'slider-blocks' ),
		'url'     => admin_url( 'admin.php?page=' . GutSlider_Admin::COMPARE_SLUG ),
		'upgrade' => true,
	);
}
?>
<div class="gs-app" data-gs-page="<?php echo esc_attr( $gs_current ); ?>">
	<header class="gs-bar">
		<div class="gs-bar__inner">
			<a class="gs-brand" href="<?php echo esc_url( admin_url( 'admin.php?page=gutslider-blocks' ) ); ?>">
				<span class="gs-brand__mark" aria-hidden="true">
					<svg viewBox="0 0 45 45" xmlns="http://www.w3.org/2000/svg" focusable="false">
						<path fill="currentColor" d="M5 45C3.625 45 2.44833 44.5108 1.47 43.5325C0.491667 42.5542 0.00166667 41.3767 0 40V5C0 3.625 0.49 2.44833 1.47 1.47C2.45 0.491667 3.62667 0.00166667 5 0H40C41.375 0 42.5525 0.49 43.5325 1.47C44.5125 2.45 45.0017 3.62667 45 5V40C45 41.375 44.5108 42.5525 43.5325 43.5325C42.5542 44.5125 41.3767 45.0017 40 45H5ZM17.5 35H27.5C28.875 35 30.0525 34.5108 31.0325 33.5325C32.0125 32.5542 32.5017 31.3767 32.5 30V20H22.5V25H27.5V30H17.5V15H32.5C32.5 13.625 32.0108 12.4483 31.0325 11.47C30.0542 10.4917 28.8767 10.0017 27.5 10H17.5C16.125 10 14.9483 10.49 13.97 11.47C12.9917 12.45 12.5017 13.6267 12.5 15V30C12.5 31.375 12.99 32.5525 13.97 33.5325C14.95 34.5125 16.1267 35.0017 17.5 35Z" />
					</svg>
				</span>
				<span class="gs-brand__name"><?php esc_html_e( 'GutSlider', 'slider-blocks' ); ?></span>
				<span class="gs-chip gs-chip--version"><?php echo esc_html( GUTSLIDER_VERSION ); ?></span>
				<?php if ( $gs_has_pro ) : ?>
					<span class="gs-chip gs-chip--pro"><?php esc_html_e( 'PRO', 'slider-blocks' ); ?></span>
				<?php endif; ?>
			</a>

			<nav class="gs-nav" aria-label="<?php esc_attr_e( 'GutSlider navigation', 'slider-blocks' ); ?>">
				<?php foreach ( $gs_nav as $gs_item ) : ?>
					<?php $gs_active = ( $gs_item['slug'] === $gs_current ); ?>
					<a
						class="gs-nav__link<?php echo $gs_active ? ' is-active' : ''; ?><?php echo empty( $gs_item['upgrade'] ) ? '' : ' gs-nav__link--upgrade'; ?>"
						href="<?php echo esc_url( $gs_item['url'] ); ?>"
						<?php echo empty( $gs_item['external'] ) ? '' : 'target="_blank" rel="noopener noreferrer"'; ?>
						<?php echo $gs_active ? 'aria-current="page"' : ''; ?>
					>
						<?php if ( ! empty( $gs_item['upgrade'] ) ) : ?>
							<?php GutSlider_Admin::icon( 'sparkles' ); ?>
						<?php endif; ?>
						<?php echo esc_html( $gs_item['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="gs-bar__actions">
				<button type="button" class="gs-icon-btn" data-gs-theme-toggle aria-label="<?php esc_attr_e( 'Toggle colour scheme', 'slider-blocks' ); ?>">
					<?php GutSlider_Admin::icon( 'sun', 'gs-icon--sun' ); ?>
					<?php GutSlider_Admin::icon( 'moon', 'gs-icon--moon' ); ?>
				</button>

				<a class="gs-icon-btn" href="https://gutslider.com/docs" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Documentation', 'slider-blocks' ); ?>">
					<?php GutSlider_Admin::icon( 'book-open' ); ?>
				</a>

				<?php if ( ! $gs_has_pro ) : ?>
					<a class="gs-btn gs-btn--quiet gs-btn--sm" href="https://gutslider.com/pricing" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Get Pro', 'slider-blocks' ); ?>
					</a>
				<?php endif; ?>

				<a class="gs-btn gs-btn--primary gs-btn--sm" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>">
					<?php GutSlider_Admin::icon( 'plus' ); ?>
					<?php esc_html_e( 'New slider', 'slider-blocks' ); ?>
				</a>
			</div>
		</div>
	</header>

	<main class="gs-main">
