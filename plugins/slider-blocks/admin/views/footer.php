<?php
/**
 * Shared dashboard shell footer.
 *
 * @package GutSliderBlocks
 *
 * @var bool $has_pro Whether the Pro plugin is active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gs_has_pro = isset( $has_pro ) ? (bool) $has_pro : defined( 'GUTSLIDER_PRO_VERSION' );
?>
	</main>

	<footer class="gs-appfoot">
		<div class="gs-appfoot__inner">
			<span>
				<?php
				printf(
					/* translators: %s: link to the review page. */
					esc_html__( 'Enjoying GutSlider? %s on WordPress.org.', 'slider-blocks' ),
					'<a class="gs-appfoot__link" href="https://wordpress.org/support/plugin/slider-blocks/reviews/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Leave a review', 'slider-blocks' ) . '</a>'
				);
				?>
			</span>
			<span>
				<?php
				echo esc_html(
					$gs_has_pro
						? sprintf( 'GutSlider Pro %s', GUTSLIDER_VERSION )
						: sprintf( 'GutSlider %s', GUTSLIDER_VERSION )
				);
				?>
			</span>
		</div>
	</footer>

	<div class="gs-toasts" data-gs-toasts aria-live="polite" aria-atomic="true"></div>

	<div class="gs-savebar" data-gs-savebar aria-hidden="true">
		<div class="gs-savebar__inner">
			<span class="gs-savebar__dot" aria-hidden="true"></span>
			<span class="gs-savebar__text" data-gs-savebar-text></span>
			<div class="gs-savebar__actions">
				<button type="button" class="gs-btn gs-btn--ghost" data-gs-discard>
					<?php esc_html_e( 'Discard', 'slider-blocks' ); ?>
				</button>
				<button type="button" class="gs-btn gs-btn--primary" data-gs-save>
					<span class="gs-btn__spinner" aria-hidden="true"></span>
					<span class="gs-btn__label"><?php esc_html_e( 'Save changes', 'slider-blocks' ); ?></span>
					<kbd class="gs-kbd" data-gs-save-kbd>⌘S</kbd>
				</button>
			</div>
		</div>
	</div>
</div>
