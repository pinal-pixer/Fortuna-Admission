<?php if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- template variables are local to the including controller method, hooks referenced here belong to WP All Import
?>

<?php
$custom_type = get_post_type_object( $post['custom_type'] );
if (empty($custom_type)) {
    $custom_type = new stdClass();
    $custom_type->labels = new stdClass();
    $custom_type->labels->name = ( 'shop_customer' == $post['custom_type'] ) ? __('WooCommerce Customers', 'wpallimport-user-import') : __('Users', 'wpallimport-user-import');
}
// Check if $existing_meta_keys is defined
$existing_meta_keys = isset($existing_meta_keys) ? $existing_meta_keys : array();
// Check if $isWizard is defined
$isWizard = isset($isWizard) ? $isWizard : false;

// Set up $cpt_name for consistent labeling
$cpt_name = ( ! empty($post['custom_type']) and ! empty($custom_type->labels->name)) ? $custom_type->labels->name : '';

// WP All Import's admin.js evaluates __META_KEYS even with no autocomplete on the page.
wp_add_inline_script( 'pmxi-admin-script', 'var __META_KEYS = ' . wp_json_encode($existing_meta_keys) . ';', 'before' );

$unique_key_help = implode( '<br/><br/>', array(
	__('Adjusting the Unique Identifier', 'wpallimport-user-import'),
	/* translators: %s is the imported post type name. */
	sprintf( __('If you run this import again with an updated file, the Unique Identifier allows WP All Import to correctly link the records in your updated file with the %s it will create right now. If multiple records in this import file have the same Unique Identifier, only the first will be created. The others will be detected as duplicates.', 'wpallimport-user-import'), $custom_type->labels->name ),
	__('If you find that the autodetected Unique Identifier is not unique enough you can drag in any combination of elements. The Unique Identifier should be unique for each record in this import file, and should stay the same even if this import file is updated. Things like product IDs, titles, and SKUs are good Unique Identifiers because they probably won\'t change. Don\'t use a description or price, since that might later change.', 'wpallimport-user-import'),
) );
?>
<div class="wpallimport-collapsed wpallimport-section">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">
			<h3><?php /* translators: %s is the imported post type name. */ printf(esc_html__('Configure how WP All Import will handle %s matching', 'wpallimport-user-import'), esc_html($custom_type->labels->name)); ?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<table class="form-table" style="max-width:none;">
					<tr>
						<td>
							<input type="hidden" name="duplicate_matching" value="<?php echo esc_attr($post['duplicate_matching']); ?>"/>
							<!-- First Radio Option: New Items Import -->
							<div>
								<label>
									<input type="radio" id="wizard_type_new" name="wizard_type" value="new" <?php if ( ($post['wizard_type'] == 'new' || empty($post['wizard_type'])) ) echo 'checked="checked"'; ?> class="switcher"/>
									<h4 style="margin: 0; display: inline-block; font-size: 14px;"><?php /* translators: %s is the imported record type name. */ printf(esc_html__('Create new %s for each record in this import file', 'wpallimport-user-import'), esc_html($cpt_name)); ?></h4>
								</label>
							</div>
							<div class="wpallimport-wizard-description">
								<?php /* translators: %1$s and %2$s are the imported record type name. */ printf(esc_html__('First run creates new %1$s. Re-running this import will update those same %2$s using the Unique Identifier.', 'wpallimport-user-import'), esc_html($cpt_name), esc_html($cpt_name)); ?>
							</div>
							<div class="switcher-target-wizard_type_new" <?php if ($post['wizard_type'] != 'new' && !empty($post['wizard_type'])) { ?> style="display: none;" <?php } ?>>
									<div class="wpallimport-wizard-content-wrapper">
										<div class="wpallimport-unique-key-wrapper" <?php if (!empty(PMXI_Plugin::$session->deligate)):?>style="display:none;"<?php endif; ?>>
											<label style="font-weight: bold;"><?php esc_html_e("Unique Identifier", "wpallimport-user-import"); ?></label>
											<input type="text" class="smaller-text wpallimport-unique-key-input" name="unique_key" value="<?php if ( ! $isWizard ) echo esc_attr($post['unique_key']); elseif ($post['tmp_unique_key']) echo esc_attr($post['unique_key']); ?>" <?php if ( ( ! $isWizard and ! empty($post['unique_key']) ) ) echo 'disabled="disabled"'; ?>/>

											<?php if ( $isWizard ): ?>
											<input type="hidden" name="tmp_unique_key" value="<?php echo esc_attr($post['unique_key'] ? $post['unique_key'] : $post['tmp_unique_key']); ?>"/>
											<a href="javascript:void(0);" class="wpallimport-auto-detect-unique-key"><?php esc_html_e('Auto-detect', 'wpallimport-user-import'); ?></a>
											<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php echo esc_attr($unique_key_help); ?>">?</a>
											<?php else: ?>
												<?php if ( ! empty($post['unique_key']) ): ?>
												<a href="javascript:void(0);" class="wpallimport-change-unique-key"><?php esc_html_e('Edit', 'wpallimport-user-import'); ?></a>
												<div id="dialog-confirm" title="<?php esc_attr_e('Warning: Are you sure you want to edit the Unique Identifier?','wpallimport-user-import');?>" style="display:none;">
													<p><?php /* translators: %s is the imported post type name. */ printf(esc_html__('It is recommended you delete all %s associated with this import before editing the unique identifier.', 'wpallimport-user-import'), esc_html(strtolower($custom_type->labels->name))); ?></p>
													<p><?php /* translators: %1$s and %2$s are the imported post type name. */ printf(esc_html__('Editing the unique identifier will dissociate all existing %1$s linked to this import. Future runs of the import will result in duplicates, as WP All Import will no longer be able to update these %2$s.', 'wpallimport-user-import'), esc_html(strtolower($custom_type->labels->name)), esc_html(strtolower($custom_type->labels->name))); ?></p>
													<p><?php esc_html_e('You really should just re-create your import, and pick the right unique identifier to start with.', 'wpallimport-user-import'); ?></p>
												</div>
												<?php else:?>
												<input type="hidden" name="tmp_unique_key" value="<?php echo esc_attr($post['unique_key'] ? $post['unique_key'] : $post['tmp_unique_key']); ?>"/>
												<a href="javascript:void(0);" class="wpallimport-auto-detect-unique-key"><?php esc_html_e('Auto-detect', 'wpallimport-user-import'); ?></a>
												<a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php echo esc_attr($unique_key_help); ?>">?</a>
												<?php endif; ?>
											<?php endif; ?>


										</div>
									</div>
								</div>
							</div>

							<!-- Second Radio Option: Existing Items Import -->
							<div class="wpallimport-wizard-section-spacing">
								<label>
									<input type="radio" id="wizard_type_matching" name="wizard_type" value="matching" <?php if ( ($post['wizard_type'] == 'matching') ) echo 'checked="checked"'; ?> class="switcher"/>
									<h4 style="margin: 0; display: inline-block; font-size: 14px;"><?php /* translators: %s is the imported record type name. */ printf(esc_html__('Attempt to match to existing %s before creating new ones', 'wpallimport-user-import'), esc_html($cpt_name)); ?></h4>
								</label>
							</div>
							<div class="wpallimport-wizard-description">
								<?php /* translators: %s is the imported record type name. */ printf(esc_html__('Records in this import file will be matched with %s on your site based on...', 'wpallimport-user-import'), esc_html($cpt_name)); ?>
							</div>
							<div class="switcher-target-wizard_type_matching" <?php if ($post['wizard_type'] != 'matching') { ?> style="display: none;" <?php } ?>>
									<div class="wpallimport-wizard-type-options">
										<input type="radio" id="duplicate_indicator_title" class="switcher" name="duplicate_indicator" value="title" <?php if ( 'title' == $post['duplicate_indicator'] ) echo 'checked="checked"'; ?>/>
										<label for="duplicate_indicator_title"><?php esc_html_e('Login', 'wpallimport-user-import' )?></label><br>

										<input type="radio" id="duplicate_indicator_content" class="switcher" name="duplicate_indicator" value="content" <?php if ( 'content' == $post['duplicate_indicator'] ) echo 'checked="checked"'; ?>/>
										<label for="duplicate_indicator_content"><?php esc_html_e('Email', 'wpallimport-user-import' )?></label><br>

										<input type="radio" id="duplicate_indicator_custom_field" class="switcher" name="duplicate_indicator" value="custom field" <?php if ( 'custom field' == $post['duplicate_indicator'] ) echo 'checked="checked"'; ?>/>
										<label for="duplicate_indicator_custom_field"><?php esc_html_e('Custom field', 'wpallimport-user-import' )?></label><br>
										<span class="switcher-target-duplicate_indicator_custom_field" style="padding-left: 24px;">
											<?php esc_html_e('Name', 'wpallimport-user-import') ?>
											<input type="text" name="custom_duplicate_name" value="<?php echo esc_attr($post['custom_duplicate_name']) ?>" />
											<?php esc_html_e('Value', 'wpallimport-user-import') ?>
											<input type="text" name="custom_duplicate_value" value="<?php echo esc_attr($post['custom_duplicate_value']) ?>" />
										</span>

										<input type="radio" id="duplicate_indicator_pid" class="switcher" name="duplicate_indicator" value="pid" <?php if ( 'pid' == $post['duplicate_indicator'] ) echo 'checked="checked"'; ?>/>
										<label for="duplicate_indicator_pid"><?php
                                            if( 'shop_customer' == $post['custom_type'] ) {
	                                            esc_html_e( 'Customer ID', 'wpallimport-user-import' );
                                            } else {
	                                            esc_html_e( 'User ID', 'wpallimport-user-import' );
                                            }
                                            ?></label><br>
										<span class="switcher-target-duplicate_indicator_pid" style="padding-left: 24px;">
											<input type="text" name="pid_xpath" value="<?php echo esc_attr($post['pid_xpath']) ?>" />
										</span>
										</div>
									</div>
								</div>
							</div>

							<?php include( '_reimport_options_import_users.php' ); ?>

							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>

<?php if ( apply_filters( 'pmui_is_show_pro_notice', true, 'notifications' ) ): ?>

<div class="wpallimport-collapsed wpallimport-section">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">
			<h3><?php esc_html_e('Block email notifications during import', 'wpallimport-user-import'); ?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<?php pmui_pro_notice( __('Upgrade to User Import Add-On Pro to block email notifications during import.', 'wpallimport-user-import'), 'notifications' ); ?>
			</div>
		</div>
	</div>
</div>

<?php endif; ?>