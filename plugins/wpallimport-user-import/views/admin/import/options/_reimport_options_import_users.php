<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- template variables are local to the including controller method, hooks referenced here belong to WP All Import
// Check if $isWizard is defined
$isWizard = isset($isWizard) ? $isWizard : false;

if ( ! $isWizard  or ! empty(PMXI_Plugin::$session->deligate) and PMXI_Plugin::$session->deligate == 'wpallexport' or $isWizard and "new" != $post['wizard_type']): ?>
<h4><?php esc_html_e('For each record in this import file, every time this import is run...', 'wpallimport-user-import'); ?></h4>
<?php else: ?>
<h4><?php esc_html_e('If this import is run again and WP All Import finds new or changed data...', 'wpallimport-user-import'); ?></h4>
<?php endif; ?>
<?php
    $post_type = $post['custom_type'];
    if ( empty($custom_type) or empty($custom_type->labels->name) ) {
        $custom_type = get_post_type_object( $post['custom_type'] );
    }
    $cpt_name = ( ! empty($custom_type->labels->name)) ? $custom_type->labels->name : '';
?>
<div class="input">
	<input type="hidden" name="create_new_records" value="0" />
	<input type="checkbox" id="create_new_records" name="create_new_records" value="1" <?php if ( $post['create_new_records'] ) echo 'checked="checked"'; ?> />
	<label for="create_new_records"><?php /* translators: %s is the imported record type name. */ printf(esc_html__('Create new %s from records newly present in your file', 'wpallimport-user-import'), esc_html($cpt_name)); ?></label>
	<?php if ( ! empty(PMXI_Plugin::$session->deligate) and PMXI_Plugin::$session->deligate == 'wpallexport' ): ?>
	<a href="#help" class="wpallimport-help" title="<?php /* translators: %s is the imported record type name. */ printf(esc_attr__('New %s will only be created when ID column is present and value in ID column is unique.', 'wpallimport-user-import'), esc_attr($cpt_name)) ?>" style="top: -1px;">?</a>
	<?php endif; ?>
</div>
<div class="input">
	<input type="hidden" id="is_keep_former_posts" name="is_keep_former_posts" value="yes" />
	<input type="checkbox" id="is_not_keep_former_posts" name="is_keep_former_posts" value="no" <?php if ( "yes" != $post['is_keep_former_posts'] ) echo 'checked="checked"'; ?> class="switcher" />
	<label for="is_not_keep_former_posts"><?php /* translators: %s is the imported record type name. */ printf(esc_html__('Update existing %s with changed data in your file', 'wpallimport-user-import'), esc_html($cpt_name)); ?></label>
	<?php if ( $isWizard and "new" == $post['wizard_type'] and empty(PMXI_Plugin::$session->deligate)): ?>
	<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php esc_attr_e('These options will only be used if you run this import again later. All data is imported the first time you run an import.', 'wpallimport-user-import') ?>">?</a>
	<?php endif; ?>

	<div class="switcher-target-is_not_keep_former_posts" style="padding-left:17px;">

        <div class="input" style="margin-left: 4px;">
            <input type="hidden" name="is_selective_hashing" value="0" />
            <input type="checkbox" id="is_selective_hashing" name="is_selective_hashing" value="1" <?php if ( $post['is_selective_hashing'] ) echo 'checked="checked"'; ?> <?php if ( defined('PMXI_EDITION') and 'free' === PMXI_EDITION ) echo 'class="switcher"'; ?> />
            <label for="is_selective_hashing"><?php /* translators: %s is the imported record type name. */ printf(esc_html__('Skip %s if their data in your file has not changed', 'wpallimport-user-import'), esc_html($cpt_name)); ?></label>
            <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php echo esc_attr( implode( '<br/><br/>', array(
                __('When enabled, WP All Import will keep track of every user\'s data as it is imported. When the import is run again, users will be skipped if their data in the import file has not changed since the last run.', 'wpallimport-user-import'),
                __('Users will not be skipped if the import template or settings change, or if you make changes to the custom code in the Function Editor.', 'wpallimport-user-import'),
            ) ) ) ?>">?</a>
            <?php if ( defined('PMXI_EDITION') and 'free' === PMXI_EDITION ): ?>
            <div class="switcher-target-is_selective_hashing" style="padding-left:17px;">
                <div class="wpallimport-free-edition-notice" style="margin: 0 0 30px 7px;">
                    <a href="<?php echo esc_url( pmui_core_upgrade_url('selective-hashing') ); ?>" target="_blank" class="upgrade_link"><?php esc_html_e('Upgrade to the Pro edition of WP All Import to use this option', 'wpallimport-user-import'); ?></a>
                    <p style="margin-top:16px;"><?php esc_html_e('If you already own it, remove the free edition and install the Pro edition.', 'wpallimport-user-import'); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

		<input type="radio" id="update_all_data" class="switcher" name="update_all_data" value="yes" <?php if ( 'no' != $post['update_all_data'] ) echo 'checked="checked"'; ?>/>
		<label for="update_all_data"><?php esc_html_e('Update all data', 'wpallimport-user-import' )?></label><br>

		<input type="radio" id="update_choosen_data" class="switcher" name="update_all_data" value="no" <?php if ( 'no' == $post['update_all_data'] ) echo 'checked="checked"'; ?>/>
		<label for="update_choosen_data"><?php esc_html_e('Choose which data to update', 'wpallimport-user-import' )?></label><br>
		<div class="switcher-target-update_choosen_data"  style="padding-left:27px;">
			<div class="input">
				<h4 class="wpallimport-trigger-options wpallimport-select-all" rel="<?php esc_attr_e("Unselect All", "wpallimport-user-import"); ?>"><?php esc_html_e("Select All", "wpallimport-user-import"); ?></h4>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_first_name" value="0" />
				<input type="checkbox" id="is_update_first_name" name="is_update_first_name" value="1" <?php if ( $post['is_update_first_name'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_first_name"><?php esc_html_e('First Name', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their first name.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_last_name" value="0" />
				<input type="checkbox" id="is_update_last_name" name="is_update_last_name" value="1" <?php if ( $post['is_update_last_name'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_last_name"><?php esc_html_e('Last Name', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their last name.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_role" value="0" />
				<input type="checkbox" id="is_update_role" name="is_update_role" value="1" <?php if ( $post['is_update_role'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_role"><?php esc_html_e('Role', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their role.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_nickname" value="0" />
				<input type="checkbox" id="is_update_nickname" name="is_update_nickname" value="1" <?php if ( $post['is_update_nickname'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_nickname"><?php esc_html_e('Nickname', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their nickname.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_description" value="0" />
				<input type="checkbox" id="is_update_description" name="is_update_description" value="1" <?php if ( $post['is_update_description'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_description"><?php esc_html_e('Description', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their description.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_login" value="0" />
				<input type="checkbox" id="is_update_login" name="is_update_login" value="1" <?php if ( $post['is_update_login'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_login"><?php esc_html_e('Login', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their login.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_password" value="0" />
				<input type="checkbox" id="is_update_password" name="is_update_password" value="1" <?php if ( $post['is_update_password'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_password"><?php esc_html_e('Password', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their password.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_nicename" value="0" />
				<input type="checkbox" id="is_update_nicename" name="is_update_nicename" value="1" <?php if ( $post['is_update_nicename'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_nicename"><?php esc_html_e('Nicename', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their nicename.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_email" value="0" />
				<input type="checkbox" id="is_update_email" name="is_update_email" value="1" <?php if ( $post['is_update_email'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_email"><?php esc_html_e('Email', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their email.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_registered" value="0" />
				<input type="checkbox" id="is_update_registered" name="is_update_registered" value="1" <?php if ( $post['is_update_registered'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_registered"><?php esc_html_e('Registered Date', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their registered date.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_display_name" value="0" />
				<input type="checkbox" id="is_update_display_name" name="is_update_display_name" value="1" <?php if ( $post['is_update_display_name'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_display_name"><?php esc_html_e('Display Name', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their display name.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_url" value="0" />
				<input type="checkbox" id="is_update_url" name="is_update_url" value="1" <?php if ( $post['is_update_url'] ) echo 'checked="checked"'; ?> />
				<label for="is_update_url"><?php esc_html_e('URL', 'wpallimport-user-import') ?></label>
				<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('Check this option if you want previously imported users to change their URL.', 'wpallimport-user-import') ?>">?</a>
			</div>
			<?php

			// add-ons re-import options
			do_action('pmxi_reimport', $post['custom_type'], $post);

			?>
		</div>
	</div>
</div>
<div class="switcher-target-auto_matching">
    <?php
    $hidden_delete_missing_options = [
        'is_send_removed_to_trash',
        'is_change_post_status_of_removed'
    ];
    if (file_exists(WP_ALL_IMPORT_ROOT_DIR . '/views/admin/import/options/_delete_missing_options.php')) {
        include( WP_ALL_IMPORT_ROOT_DIR . '/views/admin/import/options/_delete_missing_options.php' );
    }
    ?>
</div>
