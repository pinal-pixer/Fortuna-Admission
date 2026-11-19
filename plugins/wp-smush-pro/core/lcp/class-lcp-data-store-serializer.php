<?php

namespace Smush\Core\LCP;

use Smush\Core\Array_Utils;

class LCP_Data_Store_Serializer {
	/**
	 * @var Array_Utils
	 */
	private $array_utils;

	public function __construct() {
		$this->array_utils = new Array_Utils();
	}

	public function serialize( $object ) {
		if ( ! ( $object instanceof LCP_Data_Store ) ) {
			return array();
		}

		$data = $object->to_array();

		if ( $object instanceof LCP_Data_Store_Option ) {
			return array(
				'type' => 'option',
				'data' => $data,
			);
		}

		return array(
			'type' => $object->get_type(),
			'data' => $data,
		);
	}

	public function deserialize( $serialized ) {
		$type = $this->array_utils->get_array_value( $serialized, 'type' );
		$data = $this->array_utils->get_array_value( $serialized, 'data' );

		if ( is_null( $type ) || is_null( $data ) ) {
			return null;
		}

		$type_class_map = array(
			'post-meta' => LCP_Data_Store_Post_Meta::class,
			'term-meta' => LCP_Data_Store_Term_Meta::class,
			'user-meta' => LCP_Data_Store_User_Meta::class,
			// Option-backed stores (home/archive variants) are serialized as "option".
			'option'    => LCP_Data_Store_Option::class,
		);

		if ( ! isset( $type_class_map[ $type ] ) ) {
			return null;
		}

		$object = new $type_class_map[ $type ]();
		$object->from_array( $data );
		return $object;
	}
}
