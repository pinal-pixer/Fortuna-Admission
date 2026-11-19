<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function pmui_admin_notices() {

	$plugin_label = function() {
		return '<b>' . esc_html( sprintf(
				// translators: %s is the plugin name.
				__('%s Plugin', 'wpallimport-user-import'),
				PMUI_Plugin::getInstance()->getName()
		) ) . '</b>';
	};

	$allowed_html = array(
			'a' => array(
					'href'   => array(),
					'target' => array()
			),
			'b' => array()
	);

	if ( ! class_exists( 'PMXI_Plugin' ) ) {
		$free_edition_url = 'https://wordpress.org/plugins/wp-all-import/';
		$paid_edition_url = 'https://www.wpallimport.com/';
		?>
		<div class="error"><p>
			<?php
			echo wp_kses(
					sprintf(
							// translators: %1$s is the plugin name, %2$s is a link to the free edition of WP All Import, %3$s is a link to the paid edition.
							__('%1$s: WP All Import must be installed. Free edition of WP All Import at %2$s and the paid edition at %3$s', 'wpallimport-user-import'),
							$plugin_label(),
							'<a href="' . esc_url($free_edition_url) . '" target="_blank">' . esc_html($free_edition_url) . '</a>',
							'<a href="' . esc_url($paid_edition_url) . '">' . esc_html($paid_edition_url) . '</a>'
					),
					$allowed_html
			) ?>
		</p></div>
		<?php

		deactivate_plugins( PMUI_ROOT_DIR . '/wpallimport-user-import.php');

	}

	if ( class_exists( 'PMXI_Plugin' ) and ! pmui_core_is_supported() ) {
		?>
		<div class="error"><p>
			<?php
			echo wp_kses(
					sprintf(
							// translators: %s is the plugin name.
							__('%s: The latest version of WP All Import is required to use this add-on. Any imports that require this add-on will not run correctly until you update WP All Import.', 'wpallimport-user-import'),
							$plugin_label()
					),
					$allowed_html
			) ?>
		</p></div>
		<?php
	}
}
