<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wpallimport-collapsed">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">
			<h3><?php esc_html_e('User Account Info','wpallimport-user-import');?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner wpallimport-user-data">
				<table class="form-table pmui-account-fields">
				<input type="hidden" name="pmui[import_users]" value="1"/>
					<tr class="pmui-half">
						<td class="pmui-half-left">
							<div class="input">
								<p style="margin-bottom:5px;"><b><?php esc_html_e('First Name', 'wpallimport-user-import');?></b></p>
								<input name="pmui[first_name]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['first_name']) ?>"/>
							</div>
						</td>
						<td class="pmui-half-right">
							<div class="input">
								<p style="margin-bottom:5px;"><b><?php esc_html_e('Last Name', 'wpallimport-user-import');?></b></p>
								<input name="pmui[last_name]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['last_name']) ?>"/>
							</div>
						</td>
					</tr>
					<tr class="pmui-half">
						<td class="pmui-half-left">
							<div class="input">
								<p style="margin-bottom:5px;">

								<b><?php

								if ( $post['wizard_type'] != 'matching' ) {
									esc_html_e('Login (required)', 'wpallimport-user-import');
								} else {
									esc_html_e('Login', 'wpallimport-user-import');
								}

								?></b>

								<a class="wpallimport-help" href="#help" title="<?php esc_attr_e('A string that contains the user\'s username for logging in.', 'wpallimport-user-import'); ?>">?</a></p>
								<input name="pmui[login]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['login']) ?>"/>
							</div>
						</td>
						<td class="pmui-half-right">
							<div class="input">
								<p style="margin-bottom:5px;">

								<b><?php

								if ( $post['wizard_type'] != 'matching' ) {
									esc_html_e('Email (required)', 'wpallimport-user-import');
								} else {
									esc_html_e('Email', 'wpallimport-user-import');
								}

								?></b>

								</p>
								<input name="pmui[email]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['email']); ?>"/>
							</div>
						</td>
					</tr>
					<tr class="pmui-full">
						<td colspan="2">
							<div class="input">
								<p style="margin-bottom:5px;"><b><?php esc_html_e('Password', 'wpallimport-user-import');?></b></p>
								<input name="pmui[pass]" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['pmui']['pass']); ?>"/>
								<div class="input" style="margin:3px;">
									<input type="hidden" name="is_hashed_wordpress_password" value="0" />
									<input type="checkbox" id="is_hashed_wordpress_password" name="is_hashed_wordpress_password" value="1" <?php if ( $post['is_hashed_wordpress_password'] ) echo 'checked="checked"'; ?> class="fix_checkbox"/>
									<label for="is_hashed_wordpress_password"><?php esc_html_e('This is a hashed password from another WordPress site','wpallimport-user-import');?> </label>
									<a href="#help" class="wpallimport-help" title="<?php esc_attr_e('WordPress encrypts passwords and stores them as a hashed value in the database. Disable this feature if you are importing a plaintext password. Enable this feature if you are importing hashed passwords that were exported from another WordPress install.', 'wpallimport-user-import') ?>" style="position: relative; top: 0px;">?</a>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>
