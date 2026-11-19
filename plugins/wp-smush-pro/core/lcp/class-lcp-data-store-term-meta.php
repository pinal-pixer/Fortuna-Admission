<?php

namespace Smush\Core\LCP;

use Smush\Core\Array_Utils;

class LCP_Data_Store_Term_Meta extends LCP_Data_Store {
	private static $type = 'term-meta';

	private $term_id;
	/**
	 * @var Array_Utils
	 */
	private $array_utils;

	public function __construct() {
		$this->array_utils = new Array_Utils();

		parent::__construct();
	}

	public function save( $url, $is_mobile, $lcp_data ) {
		$term_meta = $lcp_data->to_array();
		$term_meta = ! empty( $term_meta ) && is_array( $term_meta )
			? $term_meta
			: array();

		if ( empty( $term_meta ) ) {
			return false;
		}

		return update_term_meta(
			$this->term_id,
			$this->make_key( $url, $is_mobile ),
			$term_meta
		);
	}

	public function get( $url, $is_mobile ) {
		$term_meta = get_term_meta(
			$this->term_id,
			$this->make_key( $url, $is_mobile ),
			true
		);
		$term_meta = ! empty( $term_meta ) && is_array( $term_meta )
			? $term_meta
			: array();

		return LCP_Data::from_array( $term_meta );
	}

	public function set_term_id( $term_id ) {
		$this->term_id = $term_id;

		return $this;
	}

	public function get_term_id() {
		return $this->term_id;
	}

	public function to_array() {
		return array(
			'term_id' => $this->term_id,
		);
	}

	public function from_array( $data ) {
		$this->term_id = $this->array_utils->get_array_value( $data, 'term_id' );
	}

	public function delete_all() {
		global $wpdb;

		$like      = $wpdb->esc_like( LCP_Helper::get_key_prefix() ) . '%';
		$query     = $wpdb->prepare(
			"SELECT meta_key FROM $wpdb->termmeta WHERE term_id = %d AND meta_key LIKE %s",
			$this->term_id,
			$like
		);
		$meta_keys = $wpdb->get_col( $query );

		if ( empty( $meta_keys ) ) {
			return false;
		}

		foreach ( $meta_keys as $meta_key ) {
			delete_term_meta( $this->term_id, $meta_key );
		}
		return true;
	}

	public function get_type() {
		return self::$type;
	}

	public static function get_type_key() {
		return self::$type;
	}

	public function get_object_id() {
		return $this->term_id;
	}
}
