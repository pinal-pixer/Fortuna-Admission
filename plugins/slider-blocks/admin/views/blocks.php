<?php
/**
 * Blocks manager screen.
 *
 * @package GutSliderBlocks
 *
 * @var GutSlider_Admin $admin   Admin controller.
 * @var array           $blocks  Decorated block definitions.
 * @var int             $total   Total block count.
 * @var int             $enabled Enabled block count.
 * @var string          $current Current page slug.
 * @var bool            $has_pro Whether the Pro plugin is active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require GUTSLIDER_DIR . '/admin/views/header.php';
?>

<div class="gs-shell gs-stack" data-gs-blocks>

	<div class="gs-page-head">
		<div>
			<h1 class="gs-page-title"><?php esc_html_e( 'Blocks', 'slider-blocks' ); ?></h1>
			<p class="gs-page-sub">
				<?php esc_html_e( 'Switch off what you do not need. Disabled blocks are never registered, so they add nothing to the editor or the front end.', 'slider-blocks' ); ?>
			</p>
		</div>
		<div class="gs-count" data-gs-counter>
			<?php
			printf(
				/* translators: 1: enabled block count, 2: total block count. */
				esc_html__( '%1$d of %2$d enabled', 'slider-blocks' ),
				(int) $enabled,
				(int) $total
			);
			?>
		</div>
	</div>

	<div class="gs-toolbar">
		<div class="gs-search">
			<svg class="gs-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false">
				<circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
			</svg>
			<input
				type="search"
				class="gs-search__input"
				data-gs-search
				placeholder="<?php esc_attr_e( 'Search blocks…', 'slider-blocks' ); ?>"
				aria-label="<?php esc_attr_e( 'Search blocks', 'slider-blocks' ); ?>"
				autocomplete="off"
			/>
			<kbd class="gs-kbd gs-search__kbd">/</kbd>
		</div>

		<div class="gs-segment" role="tablist" aria-label="<?php esc_attr_e( 'Filter blocks', 'slider-blocks' ); ?>">
			<?php
			$gs_filters = array(
				'all'      => __( 'All', 'slider-blocks' ),
				'free'     => __( 'Free', 'slider-blocks' ),
				'pro'      => __( 'Pro', 'slider-blocks' ),
				'enabled'  => __( 'Enabled', 'slider-blocks' ),
				'disabled' => __( 'Disabled', 'slider-blocks' ),
			);
			?>
			<?php foreach ( $gs_filters as $gs_key => $gs_label ) : ?>
				<button
					type="button"
					role="tab"
					class="gs-segment__btn<?php echo ( 'all' === $gs_key ) ? ' is-active' : ''; ?>"
					data-gs-filter="<?php echo esc_attr( $gs_key ); ?>"
					aria-selected="<?php echo ( 'all' === $gs_key ) ? 'true' : 'false'; ?>"
				>
					<?php echo esc_html( $gs_label ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="gs-toolbar__actions">
			<button type="button" class="gs-btn gs-btn--quiet gs-btn--sm" data-gs-bulk="on">
				<?php esc_html_e( 'Enable all', 'slider-blocks' ); ?>
			</button>
			<button type="button" class="gs-btn gs-btn--quiet gs-btn--sm" data-gs-bulk="off">
				<?php esc_html_e( 'Disable all', 'slider-blocks' ); ?>
			</button>
		</div>
	</div>

	<div class="gs-grid gs-grid--cards" data-gs-grid>
		<?php foreach ( $blocks as $gs_block ) : ?>
			<?php
			$gs_is_pro  = ! empty( $gs_block['is_pro'] );
			$gs_locked  = ! empty( $gs_block['locked'] );
			$gs_on      = ! empty( $gs_block['enabled'] );
			$gs_demo    = 'https://demos.gutslider.com/' . $gs_block['demo_slug'];
			$gs_toggle  = 'gs-toggle-' . sanitize_html_class( $gs_block['name'] );
			$gs_classes = 'gs-block';
			$gs_classes .= $gs_is_pro ? ' is-pro' : ' is-free';
			$gs_classes .= $gs_locked ? ' is-locked' : '';
			$gs_classes .= $gs_on ? ' is-on' : ' is-off';
			?>
			<article
				class="<?php echo esc_attr( $gs_classes ); ?>"
				data-gs-block="<?php echo esc_attr( $gs_block['name'] ); ?>"
				data-gs-tier="<?php echo $gs_is_pro ? 'pro' : 'free'; ?>"
				data-gs-locked="<?php echo $gs_locked ? '1' : '0'; ?>"
				data-gs-initial="<?php echo $gs_on ? '1' : '0'; ?>"
				data-gs-name="<?php echo esc_attr( strtolower( $gs_block['title'] . ' ' . $gs_block['description'] ) ); ?>"
			>
				<div class="gs-block__head">
					<h3 class="gs-block__title"><?php echo esc_html( $gs_block['title'] ); ?></h3>
					<?php if ( $gs_is_pro ) : ?>
						<span class="gs-tag gs-tag--pro"><?php esc_html_e( 'Pro', 'slider-blocks' ); ?></span>
					<?php else : ?>
						<span class="gs-tag"><?php esc_html_e( 'Free', 'slider-blocks' ); ?></span>
					<?php endif; ?>
				</div>

				<p class="gs-block__text"><?php echo esc_html( $gs_block['description'] ); ?></p>

				<div class="gs-block__foot">
					<a class="gs-link gs-link--sm" href="<?php echo esc_url( $gs_demo ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Demo', 'slider-blocks' ); ?>
						<svg class="gs-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
							<path d="M14 4h6v6M20 4l-8 8M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" />
						</svg>
					</a>

					<?php if ( $gs_locked ) : ?>
						<a class="gs-lock" href="https://gutslider.com/pricing" target="_blank" rel="noopener noreferrer">
							<svg class="gs-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
								<rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" />
							</svg>
							<?php esc_html_e( 'Unlock', 'slider-blocks' ); ?>
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
			</article>
		<?php endforeach; ?>
	</div>

	<div class="gs-empty" data-gs-empty hidden>
		<svg class="gs-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true" focusable="false">
			<circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
		</svg>
		<p><?php esc_html_e( 'No blocks match your search.', 'slider-blocks' ); ?></p>
		<button type="button" class="gs-btn gs-btn--quiet gs-btn--sm" data-gs-clear-filters>
			<?php esc_html_e( 'Clear filters', 'slider-blocks' ); ?>
		</button>
	</div>

	<?php if ( ! $has_pro ) : ?>
		<div class="gs-note">
			<svg class="gs-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
				<rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" />
			</svg>
			<p>
				<?php esc_html_e( 'Pro blocks are listed so you can see what is available. They activate automatically once GutSlider Pro is installed.', 'slider-blocks' ); ?>
				<a class="gs-link" href="https://gutslider.com/pricing" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'See pricing', 'slider-blocks' ); ?></a>
			</p>
		</div>
	<?php endif; ?>
</div>

<?php
require GUTSLIDER_DIR . '/admin/views/footer.php';
