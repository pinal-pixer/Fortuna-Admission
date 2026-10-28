<?php
/**
 * Settings screen.
 *
 * @package GutSliderBlocks
 *
 * @var GutSlider_Admin     $admin    Admin controller.
 * @var array<string,mixed> $settings Current settings.
 * @var string              $current  Current page slug.
 * @var bool                $has_pro  Whether the Pro plugin is active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gs_upload   = wp_upload_dir();
$gs_css_dir  = trailingslashit( $gs_upload['basedir'] ) . 'gutslider-styles';
$gs_files    = is_dir( $gs_css_dir ) ? glob( $gs_css_dir . '/*.css' ) : array();
$gs_css_size = 0;

if ( is_array( $gs_files ) ) {
	foreach ( $gs_files as $gs_file ) {
		$gs_css_size += (int) filesize( $gs_file );
	}
} else {
	$gs_files = array();
}

require GUTSLIDER_DIR . '/admin/views/header.php';
?>

<div class="gs-shell gs-stack" data-gs-settings>

	<div class="gs-page-head">
		<div>
			<h1 class="gs-page-title"><?php esc_html_e( 'Settings', 'slider-blocks' ); ?></h1>
			<p class="gs-page-sub"><?php esc_html_e( 'How GutSlider behaves in the editor and what it loads on the front end.', 'slider-blocks' ); ?></p>
		</div>
	</div>

	<div class="gs-settings">
		<nav class="gs-sidenav" aria-label="<?php esc_attr_e( 'Settings sections', 'slider-blocks' ); ?>">
			<a class="gs-sidenav__link is-active" href="#gs-performance" data-gs-sidenav>
				<svg class="gs-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M13 2 4.1 13.4a.6.6 0 0 0 .5 1H11l-1 7.6 8.9-11.4a.6.6 0 0 0-.5-1H12z" /></svg>
				<?php esc_html_e( 'Performance', 'slider-blocks' ); ?>
			</a>
			<a class="gs-sidenav__link" href="#gs-editor" data-gs-sidenav>
				<svg class="gs-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z" /></svg>
				<?php esc_html_e( 'Editor', 'slider-blocks' ); ?>
			</a>
			<a class="gs-sidenav__link" href="#gs-typography" data-gs-sidenav>
				<svg class="gs-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 7V4h16v3M9 20h6M12 4v16" /></svg>
				<?php esc_html_e( 'Typography', 'slider-blocks' ); ?>
			</a>
			<a class="gs-sidenav__link" href="#gs-maintenance" data-gs-sidenav>
				<svg class="gs-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14.7 6.3a4 4 0 0 0 5 5L21 12l-9 9-4-4 9-9z" /><path d="m3 21 4-4" /></svg>
				<?php esc_html_e( 'Maintenance', 'slider-blocks' ); ?>
			</a>
		</nav>

		<div class="gs-settings__body">

			<section class="gs-panel" id="gs-performance">
				<header class="gs-panel__head">
					<h2 class="gs-panel__title"><?php esc_html_e( 'Performance', 'slider-blocks' ); ?></h2>
					<p class="gs-panel__sub"><?php esc_html_e( 'Control how the CSS your blocks generate reaches the browser.', 'slider-blocks' ); ?></p>
				</header>

				<div class="gs-field">
					<div class="gs-field__label">
						<span class="gs-field__name"><?php esc_html_e( 'CSS delivery', 'slider-blocks' ); ?></span>
						<p class="gs-field__help"><?php esc_html_e( 'External files are cacheable and best for most sites. Inline avoids an extra request, which can help pages with very little slider CSS.', 'slider-blocks' ); ?></p>
					</div>
					<div class="gs-field__control">
						<div class="gs-radio-cards" role="radiogroup" aria-label="<?php esc_attr_e( 'CSS delivery', 'slider-blocks' ); ?>">
							<?php
							$gs_delivery = array(
								'file'   => array(
									'title' => __( 'External file', 'slider-blocks' ),
									'text'  => __( 'One cached .css file per page', 'slider-blocks' ),
								),
								'inline' => array(
									'title' => __( 'Inline', 'slider-blocks' ),
									'text'  => __( 'Printed in a <style> tag', 'slider-blocks' ),
								),
							);
							?>
							<?php foreach ( $gs_delivery as $gs_value => $gs_opt ) : ?>
								<label class="gs-radio-card<?php echo ( $settings['css_delivery'] === $gs_value ) ? ' is-active' : ''; ?>">
									<input
										type="radio"
										name="css_delivery"
										value="<?php echo esc_attr( $gs_value ); ?>"
										data-gs-setting="css_delivery"
										data-gs-initial="<?php echo esc_attr( $settings['css_delivery'] ); ?>"
										<?php checked( $settings['css_delivery'], $gs_value ); ?>
									/>
									<span class="gs-radio-card__body">
										<span class="gs-radio-card__title"><?php echo esc_html( $gs_opt['title'] ); ?></span>
										<span class="gs-radio-card__text"><?php echo esc_html( $gs_opt['text'] ); ?></span>
									</span>
									<span class="gs-radio-card__mark" aria-hidden="true"></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</section>

			<section class="gs-panel" id="gs-editor">
				<header class="gs-panel__head">
					<h2 class="gs-panel__title"><?php esc_html_e( 'Editor', 'slider-blocks' ); ?></h2>
					<p class="gs-panel__sub"><?php esc_html_e( 'What GutSlider adds to the block editor interface.', 'slider-blocks' ); ?></p>
				</header>

				<div class="gs-field">
					<div class="gs-field__label">
						<label for="gs-pattern-library"><?php esc_html_e( 'Pattern library button', 'slider-blocks' ); ?></label>
						<p class="gs-field__help"><?php esc_html_e( 'Adds a "Patterns" button to the editor toolbar for inserting ready-made slider layouts. Turn it off for a cleaner toolbar — you can still insert every pattern from the block inserter.', 'slider-blocks' ); ?></p>
					</div>
					<div class="gs-field__control">
						<label class="gs-switch gs-switch--lg" for="gs-pattern-library">
							<input
								type="checkbox"
								id="gs-pattern-library"
								class="gs-switch__input"
								data-gs-setting="pattern_library"
								data-gs-initial="<?php echo $settings['pattern_library'] ? '1' : '0'; ?>"
								<?php checked( (bool) $settings['pattern_library'] ); ?>
							/>
							<span class="gs-switch__track" aria-hidden="true"><span class="gs-switch__thumb"></span></span>
						</label>
					</div>
				</div>
			</section>

			<section class="gs-panel" id="gs-typography">
				<header class="gs-panel__head">
					<h2 class="gs-panel__title"><?php esc_html_e( 'Typography', 'slider-blocks' ); ?></h2>
					<p class="gs-panel__sub"><?php esc_html_e( 'Fonts requested by the typography controls inside your blocks.', 'slider-blocks' ); ?></p>
				</header>

				<div class="gs-field">
					<div class="gs-field__label">
						<label for="gs-google-fonts"><?php esc_html_e( 'Load Google Fonts', 'slider-blocks' ); ?></label>
						<p class="gs-field__help"><?php esc_html_e( 'Turn this off if your theme already loads the fonts, or if you host them locally for privacy reasons. Blocks keep their font-family; only the request to Google is dropped.', 'slider-blocks' ); ?></p>
					</div>
					<div class="gs-field__control">
						<label class="gs-switch gs-switch--lg" for="gs-google-fonts">
							<input
								type="checkbox"
								id="gs-google-fonts"
								class="gs-switch__input"
								data-gs-setting="google_fonts"
								data-gs-initial="<?php echo $settings['google_fonts'] ? '1' : '0'; ?>"
								<?php checked( (bool) $settings['google_fonts'] ); ?>
							/>
							<span class="gs-switch__track" aria-hidden="true"><span class="gs-switch__thumb"></span></span>
						</label>
					</div>
				</div>
			</section>

			<section class="gs-panel" id="gs-maintenance">
				<header class="gs-panel__head">
					<h2 class="gs-panel__title"><?php esc_html_e( 'Maintenance', 'slider-blocks' ); ?></h2>
					<p class="gs-panel__sub"><?php esc_html_e( 'Housekeeping for generated files and stored data.', 'slider-blocks' ); ?></p>
				</header>

				<div class="gs-field">
					<div class="gs-field__label">
						<span class="gs-field__name"><?php esc_html_e( 'Generated stylesheets', 'slider-blocks' ); ?></span>
						<p class="gs-field__help">
							<?php
							$gs_size_label = $gs_css_size > 0 ? size_format( $gs_css_size, 1 ) : '0 B';
							printf(
								/* translators: 1: file count, 2: formatted file size. */
								esc_html__( '%1$s files · %2$s in your uploads folder. They are rebuilt automatically the next time each page is viewed.', 'slider-blocks' ),
								esc_html( number_format_i18n( count( $gs_files ) ) ),
								esc_html( (string) $gs_size_label )
							);
							?>
						</p>
					</div>
					<div class="gs-field__control">
						<button type="button" class="gs-btn gs-btn--quiet" data-gs-clear-cache>
							<span class="gs-btn__spinner" aria-hidden="true"></span>
							<span class="gs-btn__label"><?php esc_html_e( 'Clear style cache', 'slider-blocks' ); ?></span>
						</button>
					</div>
				</div>

				<div class="gs-field gs-field--danger">
					<div class="gs-field__label">
						<label for="gs-remove-data"><?php esc_html_e( 'Delete data on uninstall', 'slider-blocks' ); ?></label>
						<p class="gs-field__help"><?php esc_html_e( 'When enabled, removing the plugin also removes its options and generated stylesheets. Your posts and block content are never touched.', 'slider-blocks' ); ?></p>
					</div>
					<div class="gs-field__control">
						<label class="gs-switch gs-switch--lg gs-switch--danger" for="gs-remove-data">
							<input
								type="checkbox"
								id="gs-remove-data"
								class="gs-switch__input"
								data-gs-setting="remove_data"
								data-gs-initial="<?php echo $settings['remove_data'] ? '1' : '0'; ?>"
								<?php checked( (bool) $settings['remove_data'] ); ?>
							/>
							<span class="gs-switch__track" aria-hidden="true"><span class="gs-switch__thumb"></span></span>
						</label>
					</div>
				</div>
			</section>

			<section class="gs-panel gs-panel--info">
				<header class="gs-panel__head">
					<h2 class="gs-panel__title"><?php esc_html_e( 'System', 'slider-blocks' ); ?></h2>
				</header>
				<dl class="gs-defs">
					<div><dt><?php esc_html_e( 'GutSlider', 'slider-blocks' ); ?></dt><dd><?php echo esc_html( GUTSLIDER_VERSION ); ?></dd></div>
					<div>
						<dt><?php esc_html_e( 'GutSlider Pro', 'slider-blocks' ); ?></dt>
						<dd><?php echo $has_pro ? esc_html( GUTSLIDER_PRO_VERSION ) : esc_html__( 'Not installed', 'slider-blocks' ); ?></dd>
					</div>
					<div><dt><?php esc_html_e( 'WordPress', 'slider-blocks' ); ?></dt><dd><?php echo esc_html( get_bloginfo( 'version' ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'PHP', 'slider-blocks' ); ?></dt><dd><?php echo esc_html( PHP_VERSION ); ?></dd></div>
				</dl>
			</section>
		</div>
	</div>
</div>

<?php
require GUTSLIDER_DIR . '/admin/views/footer.php';
