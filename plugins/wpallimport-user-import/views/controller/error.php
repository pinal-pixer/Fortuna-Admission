<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- template variables are local to the including controller method

foreach ($errors as $msg): ?>
	<div class="error"><p><?php echo esc_html($msg) ?></p></div>
<?php endforeach ?>
