<?php if ($post['custom_type'] == 'import_users'): ?>

<div class="wpallimport-collapsed wpallimport-section">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">	
			<h3><?php _e('Email Notifications For Imported Users', 'wp_all_import_user_add_on'); ?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<div class="input">
					<input type="hidden" name="do_not_send_password_notification" value="0" />
					<input type="checkbox" id="do_not_send_password_notification" name="do_not_send_password_notification" value="1" <?php echo empty($post['do_not_send_password_notification']) ? '': 'checked="checked"' ?> class="switcher"/>
					<label for="do_not_send_password_notification"><?php _e('Block email notifications during import', 'wp_all_import_user_add_on') ?></label>
					<a href="#help" class="wpallimport-help" title="<?php _e('If enabled, WP All Import will prevent WordPress from sending notification emails to imported users while the import is processing.', 'wp_all_import_user_add_on') ?>" style="position:relative; top: 0;">?</a>
				</div>	
			</div>
		</div>			
	</div>
</div>

<?php endif; ?>

<?php if ($post['custom_type'] == 'shop_customer'): ?>

<div class="wpallimport-collapsed wpallimport-section">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">	
			<h3><?php _e('Email Notifications For Imported Customers', 'wp_all_import_user_add_on'); ?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<div class="input">
					<input type="hidden" name="do_not_send_password_notification" value="0" />
					<input type="checkbox" id="do_not_send_password_notification" name="do_not_send_password_notification" value="1" <?php echo empty($post['do_not_send_password_notification']) ? '': 'checked="checked"' ?> class="switcher"/>
					<label for="do_not_send_password_notification"><?php _e('Block email notifications during import', 'wp_all_import_user_add_on') ?></label>
					<a href="#help" class="wpallimport-help" title="<?php _e('If enabled, WP All Import will prevent WordPress from sending notification emails to imported customers while the import is processing.', 'wp_all_import_user_add_on') ?>" style="position:relative; top: 0;">?</a>
				</div>	
			</div>
		</div>			
	</div>
</div>

<?php endif; ?>