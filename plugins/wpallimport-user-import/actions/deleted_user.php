<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Removes the cache contents.
 *
 * @param $user_id
 * @param $reassign
 * @param $user_object
 * @return void
 */
function pmui_deleted_user( $user_id, $reassign = null, $user_object = null ) {
    wp_cache_delete( $user_id, 'users' );
}
