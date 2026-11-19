<?php

if ( ! defined( 'ABSPATH' ) ) exit; ?>

<!-- User Account Info Section -->

<?php include( 'user_personal_section.php' ); ?>

<!-- Other User Info Section -->

<?php include( 'user_other_section.php' ); ?>

<!-- User Meta Section -->

<?php if ( apply_filters( 'pmui_is_show_pro_notice', true, 'user-meta' ) ): ?>
<div class="wpallimport-collapsed wpallimport-section closed">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">
			<h3><?php esc_html_e('User Meta (Custom Fields)','wpallimport-user-import');?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<?php pmui_pro_notice( __('Importing user meta (Custom Fields) and WooCommerce Customers requires WP All Import Pro and User Import Add-On Pro.', 'wpallimport-user-import'), 'user-meta' ); ?>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>
