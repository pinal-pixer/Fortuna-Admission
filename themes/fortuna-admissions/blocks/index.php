<?php

add_action( 'init', 'register_acf_blocks' );
function register_acf_blocks() {
    register_block_type( __DIR__ . '/accordion' );
    register_block_type( __DIR__ . '/coach-carousel' );
    register_block_type( __DIR__ . '/coach-grid' );
    register_block_type( __DIR__ . '/mobile-menu' );
    register_block_type( __DIR__ . '/nav-menu' );
}