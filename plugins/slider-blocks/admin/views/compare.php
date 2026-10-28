<?php
/**
 * Free vs Pro comparison (the Free vs Pro screen).
 *
 * @package GutSliderBlocks
 *
 * @var int $gs_free_total Number of free blocks.
 * @var int $gs_pro_total  Number of Pro blocks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gs_pricing_url = 'https://gutslider.com/pricing';
$gs_demos_url   = 'https://demos.gutslider.com/';

// Pro designs in the free blocks (the `pro: true` entries of each design picker).
$gs_pro_designs = 30;

/*
 * Rows: label, hint, free value, pro value. A value is true (included),
 * false (not included) or a short text.
 */
$gs_compare = array(
	array(
		'title' => __( 'Blocks', 'slider-blocks' ),
		'rows'  => array(
			array(
				__( 'Slider, carousel and marquee blocks', 'slider-blocks' ),
				__( 'Static, Flexible, Testimonial, Photo, Logo, Before After, Video, Blog Post and Marquee.', 'slider-blocks' ),
				(string) $gs_free_total,
				(string) ( $gs_free_total + $gs_pro_total ),
			),
			array(
				__( 'Creative effect sliders', 'slider-blocks' ),
				__( 'Shader, Shutters, Slicer, Fashion and Triple sliders.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( '3D and motion carousels', 'slider-blocks' ),
				__( '3D, Panorama, Spring, Material and Marquee carousels.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'Interactive sliders', 'slider-blocks' ),
				__( 'Card, Hover and Tinder-style swipe sliders.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'WooCommerce carousels', 'slider-blocks' ),
				__( 'Product and product category carousels that help you sell.', 'slider-blocks' ),
				false,
				true,
			),
		),
	),
	array(
		'title' => __( 'Designs', 'slider-blocks' ),
		'rows'  => array(
			array(
				__( 'Ready-made designs in every block', 'slider-blocks' ),
				__( 'Pick a look, then fine-tune it in the sidebar.', 'slider-blocks' ),
				__( 'Starter set', 'slider-blocks' ),
				__( 'Every design', 'slider-blocks' ),
			),
			array(
				__( 'Premium designs', 'slider-blocks' ),
				__( 'Glass cards, cinema and playlist videos, polaroid stacks, avatar switchers, tag clouds and more.', 'slider-blocks' ),
				false,
				/* translators: %d: number of designs. */
				sprintf( __( '%d+ designs', 'slider-blocks' ), $gs_pro_designs ),
			),
			array(
				__( 'Block patterns library', 'slider-blocks' ),
				__( 'Ready-made slider layouts to insert and edit.', 'slider-blocks' ),
				true,
				true,
			),
		),
	),
	array(
		'key'   => 'features',
		'title' => __( 'Features', 'slider-blocks' ),
		'rows'  => array(
			array(
				__( 'Responsive controls, typography and Google Fonts', 'slider-blocks' ),
				__( 'Separate Desktop, Tablet and Mobile values.', 'slider-blocks' ),
				true,
				true,
			),
			array(
				__( 'Lightweight output', 'slider-blocks' ),
				__( 'No jQuery, assets only where used, one CSS file per page.', 'slider-blocks' ),
				true,
				true,
			),
			array(
				__( 'SEO schema markup', 'slider-blocks' ),
				__( 'Video rich results for videos and ItemList markup for post sliders.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'Before-after video comparisons', 'slider-blocks' ),
				__( 'Two videos in sync, several comparisons with thumbnails, auto-play and fullscreen.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'Video extras', 'slider-blocks' ),
				__( 'Lightbox, preview on hover, silent loops, custom thumbnails and end times.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'Content reveal', 'slider-blocks' ),
				__( 'Subtitle, title, description and buttons play in one sequence: words rise, letters flip, blur in or mask reveal.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'Content animations and background effects', 'slider-blocks' ),
				__( 'Animated slide content, background motion and slide names as tabs.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'Advanced marquee motion', 'slider-blocks' ),
				__( 'Up to three rows, move with page scroll, drag to move, reverse and speed up on scroll.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'Clickable slides and remote navigation', 'slider-blocks' ),
				__( 'Link a whole slide, or control a slider from buttons anywhere on the page.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'Per-device visibility and backgrounds', 'slider-blocks' ),
				__( 'Show slides on chosen devices, with a different image for each screen size.', 'slider-blocks' ),
				false,
				true,
			),
			array(
				__( 'Thumbnail gallery navigation', 'slider-blocks' ),
				__( 'Thumbnail navigation and detached content for the Static and Blog Post sliders.', 'slider-blocks' ),
				false,
				true,
			),
		),
	),
	array(
		'title' => __( 'Support', 'slider-blocks' ),
		'rows'  => array(
			array(
				__( 'Help from the GutSlider team', 'slider-blocks' ),
				'',
				__( 'Community forum', 'slider-blocks' ),
				__( 'Priority support', 'slider-blocks' ),
			),
		),
	),
);

// Feature rows only Pro has, for the headline figure.
$gs_pro_features = 0;

foreach ( $gs_compare as $gs_group ) {
	foreach ( $gs_group['rows'] as $gs_row ) {
		if ( isset( $gs_group['key'] ) && 'features' === $gs_group['key'] && false === $gs_row[2] && true === $gs_row[3] ) {
			++$gs_pro_features;
		}
	}
}

/**
 * Print one comparison cell.
 *
 * @param bool|string $value Cell value.
 * @param bool        $pro   Whether this is the Pro column.
 * @return void
 */
$gs_cell = static function ( $value, $pro ) {
	if ( true === $value ) {
		echo '<span class="gs-compare__yes' . ( $pro ? ' is-pro' : '' ) . '">';
		GutSlider_Admin::icon( 'check' );
		echo '<span class="screen-reader-text">' . esc_html__( 'Included', 'slider-blocks' ) . '</span></span>';
	} elseif ( false === $value ) {
		echo '<span class="gs-compare__no" aria-hidden="true"></span>';
		echo '<span class="screen-reader-text">' . esc_html__( 'Not included', 'slider-blocks' ) . '</span>';
	} else {
		echo '<span class="gs-compare__text' . ( $pro ? ' is-pro' : '' ) . '">' . esc_html( $value ) . '</span>';
	}
};
?>

<section class="gs-compare" id="gs-compare" aria-labelledby="gs-compare-title">
	<div class="gs-upsell">
		<div class="gs-upsell__text">
			<span class="gs-upsell__eyebrow">
				<?php GutSlider_Admin::icon( 'sparkles' ); ?>
				<?php esc_html_e( 'GutSlider Pro', 'slider-blocks' ); ?>
			</span>
			<h2 class="gs-upsell__title" id="gs-compare-title"><?php esc_html_e( 'Make every slider unforgettable', 'slider-blocks' ); ?></h2>
			<p class="gs-upsell__sub">
				<?php esc_html_e( 'Unlock the premium blocks, every design and the effects that make visitors stop scrolling. Your current sliders keep working exactly as they are.', 'slider-blocks' ); ?>
			</p>
			<div class="gs-upsell__actions">
				<a class="gs-btn gs-btn--invert" href="<?php echo esc_url( $gs_pricing_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Get GutSlider Pro', 'slider-blocks' ); ?>
					<?php GutSlider_Admin::icon( 'arrow-right' ); ?>
				</a>
				<a class="gs-btn gs-upsell__ghost" href="<?php echo esc_url( $gs_demos_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'See live demos', 'slider-blocks' ); ?>
				</a>
			</div>
		</div>
		<ul class="gs-upsell__stats">
			<li>
				<strong><?php echo esc_html( '+' . $gs_pro_total ); ?></strong>
				<span><?php esc_html_e( 'premium blocks', 'slider-blocks' ); ?></span>
			</li>
			<li>
				<strong><?php echo esc_html( $gs_pro_designs . '+' ); ?></strong>
				<span><?php esc_html_e( 'premium designs', 'slider-blocks' ); ?></span>
			</li>
			<li>
				<strong><?php echo esc_html( '+' . $gs_pro_features ); ?></strong>
				<span><?php esc_html_e( 'pro-only features', 'slider-blocks' ); ?></span>
			</li>
		</ul>
	</div>

	<div class="gs-card gs-card--flush gs-compare__card">
		<header class="gs-card__head">
			<div>
				<h2 class="gs-card__heading"><?php esc_html_e( 'Free vs Pro', 'slider-blocks' ); ?></h2>
				<p class="gs-card__sub"><?php esc_html_e( 'Everything in Free, plus the blocks and features below.', 'slider-blocks' ); ?></p>
			</div>
		</header>

		<div class="gs-compare__scroll">
			<table class="gs-compare__table">
				<thead>
					<tr>
						<th scope="col" class="gs-compare__feature"><?php esc_html_e( 'Feature', 'slider-blocks' ); ?></th>
						<th scope="col" class="gs-compare__col"><?php esc_html_e( 'Free', 'slider-blocks' ); ?></th>
						<th scope="col" class="gs-compare__col gs-compare__col--pro">
							<span class="gs-compare__badge"><?php esc_html_e( 'Pro', 'slider-blocks' ); ?></span>
						</th>
					</tr>
				</thead>
				<?php foreach ( $gs_compare as $gs_group ) : ?>
					<tbody>
						<tr class="gs-compare__group">
							<th scope="colgroup" colspan="3"><?php echo esc_html( $gs_group['title'] ); ?></th>
						</tr>
						<?php foreach ( $gs_group['rows'] as $gs_row ) : ?>
							<tr>
								<th scope="row" class="gs-compare__feature">
									<span class="gs-compare__label"><?php echo esc_html( $gs_row[0] ); ?></span>
									<?php if ( '' !== $gs_row[1] ) : ?>
										<span class="gs-compare__hint"><?php echo esc_html( $gs_row[1] ); ?></span>
									<?php endif; ?>
								</th>
								<td class="gs-compare__col"><?php $gs_cell( $gs_row[2], false ); ?></td>
								<td class="gs-compare__col gs-compare__col--pro"><?php $gs_cell( $gs_row[3], true ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				<?php endforeach; ?>
			</table>
		</div>

		<footer class="gs-compare__foot">
			<p class="gs-compare__note"><?php esc_html_e( 'Install Pro next to the free plugin. Nothing to migrate or rebuild.', 'slider-blocks' ); ?></p>
			<a class="gs-btn gs-btn--primary" href="<?php echo esc_url( $gs_pricing_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php GutSlider_Admin::icon( 'sparkles' ); ?>
				<?php esc_html_e( 'Upgrade to Pro', 'slider-blocks' ); ?>
			</a>
		</footer>
	</div>
</section>
