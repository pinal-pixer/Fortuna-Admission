<?php

namespace Smush\Core\LCP;

use Smush\Core\Array_Utils;

class LCP_Data_Store_User_Meta extends LCP_Data_Store {
	private static $type = 'user-meta';

	private $user_id;
	/**
	 * @var Array_Utils
	 */
	private $array_utils;

	public function __construct() {
		$this->array_utils = new Array_Utils();

		parent::__construct();
	}

	public function save( $url, $is_mobile, $lcp_data ) {
		$author_meta = $lcp_data->to_array();
		$author_meta = ! empty( $author_meta ) && is_array( $author_meta )
			? $author_meta
			: array();

		if ( empty( $author_meta ) ) {
			return false;
		}

		return update_user_meta(
			$this->user_id,
			$this->make_key( $url, $is_mobile ),
			$author_meta
		);
	}

	public function get( $url, $is_mobile ) {
		$author_meta = get_user_meta(
			$this->user_id,
			$this->make_key( $url, $is_mobile ),
			true
		);
		$author_meta = ! empty( $author_meta ) && is_array( $author_meta )
			? $author_meta
			: array();

		return LCP_Data::from_array( $author_meta );
	}

	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;

		return $this;
	}

	public function get_user_id() {
		return $this->user_id;
	}

	public function to_array() {
		return array(
			'user_id' => $this->user_id,
		);
	}

	public function from_array( $data ) {
		$this->user_id = $this->array_utils->get_array_value( $data, 'user_id' );
	}

	public function delete_all() {
		global $wpdb;

		$like      = $wpdb->esc_like( LCP_Helper::get_key_prefix() ) . '%';
		$query     = $wpdb->prepare(
			"SELECT meta_key FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s",
			$this->user_id,
			$like
		);
		$meta_keys = $wpdb->get_col( $query );

		if ( empty( $meta_keys ) ) {
			return false;
		}

		foreach ( $meta_keys as $meta_key ) {
			delete_user_meta( $this->user_id, $meta_key );
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
		return $this->user_id;
	}
}
