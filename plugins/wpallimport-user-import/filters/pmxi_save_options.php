<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function pmui_pmxi_save_options($post){

	return apply_filters('pmui_save_options', $post, $post);
}