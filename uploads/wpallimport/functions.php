<?php
function fs_get_post_by_slug( $slug, $post_type = 'post' ) {
	if ( $post = get_page_by_path( $slug, OBJECT, $post_type ) ) {
		return $post->ID;
	}
}
?>