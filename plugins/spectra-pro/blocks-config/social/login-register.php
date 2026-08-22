<?php
namespace SpectraPro\BlocksConfig\Social;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Class Login.
 */
class LoginRegister {
	/**
	 * Hold Social Admin settings data
	 *
	 * @var mixed
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = \UAGB_Admin_Helper::get_admin_settings_option(
			'uag_social',
			[
				'socialRegister'    => true,
				'googleClientId'    => '',
				'facebookAppId'     => '',
				'facebookAppSecret' => '',
			]
		);
	}



	/**
	 * Verify reCaptcha
	 *
	 * @param string $form_recaptcha_response reCaptcha token.
	 * @param string $server_remoteip server IP.
	 * @param string $recaptcha_secret_key secret key.
	 * @return boolean
	 * @since 1.0.0
	 */
	public function verify_recaptcha( $form_recaptcha_response, $server_remoteip, $recaptcha_secret_key ) {
		$google_url      = 'https://www.google.com/recaptcha/api/siteverify';
		$google_response = add_query_arg(
			array(
				'secret'   => $recaptcha_secret_key,
				'response' => $form_recaptcha_response,
				'remoteip' => $server_remoteip,
			),
			$google_url
		);
		$google_response = wp_remote_get( $google_response );
		if ( is_wp_error( $google_response ) ) {
			return false;
		}
		$decode_google_response = json_decode( $google_response['body'] );
		return $decode_google_response->success;
	}


}
