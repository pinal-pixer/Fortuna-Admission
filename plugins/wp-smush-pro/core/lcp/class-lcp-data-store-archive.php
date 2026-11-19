<?php

namespace Smush\Core\LCP;

class LCP_Data_Store_Archive extends LCP_Data_Store_Option {
	private static $type = 'archive';

	public function __construct() {
		parent::__construct( $this->get_archive_key() );
	}

	private function get_archive_key() {
		global $wp_query;
		if ( empty( $wp_query ) ) {
			return self::$type . '-unknown';
		}

		$query_vars = array_filter( $wp_query->query_vars );
		$query_hash = md5( serialize( $query_vars ) );

		return self::$type . '-' . $query_hash;
	}
}
