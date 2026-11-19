<?php

function pmuip_admin_notices() {

	if ( ! class_exists( 'PMXI_Plugin' ) ) {
		if ( ! defined('PMUI_EDITION') ) {
			?>
			<div class="error"><p>
				<?php printf(
						__('<b>%s Plugin</b>: WP All Import must be installed. Free edition of WP All Import at <a href="http://wordpress.org/plugins/wp-all-import/" target="_blank">http://wordpress.org/plugins/wp-all-import/</a> and the paid edition at <a href="http://www.wpallimport.com/">http://www.wpallimport.com/</a>', 'wp_all_import_user_add_on'),
						'WP All Import - User Import Add-On Pro'
				) ?>
			</p></div>
			<?php
		}

		deactivate_plugins( PMUI_PRO_ROOT_DIR . '/wpai-user-add-on.php');

	}

	if ( ! defined('PMUI_EDITION') and class_exists( 'PMXI_Plugin' ) and ( version_compare(PMXI_VERSION, '4.7.8') < 0 and PMXI_EDITION == 'paid' or version_compare(PMXI_VERSION, '3.6.9') < 0 and PMXI_EDITION == 'free') ) {
		?>
		<div class="error"><p>
			<?php printf(
					__('<b>%s Plugin</b>: The latest version of WP All Import is required to use this add-on. Any imports that require this add-on will not run correctly until you update WP All Import.', 'wp_all_import_user_add_on'),
					'WP All Import - User Import Add-On Pro'
			) ?>
		</p></div>
		<?php
	}
}
