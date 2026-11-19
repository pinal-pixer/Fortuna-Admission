<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<ul style="padding-left: 35px;">
    <?php if ( $post['is_update_first_name']): ?>
        <li> <?php esc_html_e('First Name', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_last_name']): ?>
        <li> <?php esc_html_e('Last Name', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['custom_type'] == 'import_users' && $post['is_update_role']): ?>
        <li> <?php esc_html_e('Role', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_nickname']): ?>
        <li> <?php esc_html_e('Nickname', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_description']): ?>
        <li> <?php esc_html_e('Description', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_login']): ?>
        <li> <?php esc_html_e('Login', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_password']): ?>
        <li> <?php esc_html_e('Password', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_nicename']): ?>
        <li> <?php esc_html_e('Nicename', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_email']): ?>
        <li> <?php esc_html_e('Email', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_registered']): ?>
        <li> <?php esc_html_e('Registered Date', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_display_name']): ?>
        <li> <?php esc_html_e('Display Name', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
    <?php if ( $post['is_update_url']): ?>
        <li> <?php esc_html_e('URL', 'wpallimport-user-import'); ?></li>
    <?php endif; ?>
</ul>