<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Introduce special type for controllers which render pages inside admin area
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */
abstract class PMUI_Controller_Admin extends PMUI_Controller {
	/**
	 * Admin page base url (request url without all get parameters but `page`)
	 * @var string
	 */
	public $baseUrl;
	/**
	 * Whether controller is rendered inside wordpress page
	 * @var bool
	 */
	public $isInline = false;
	/**
	 * Constructor
	 */
	public function __construct() {

		$base_url_param_names = array('page', 'pagenum', 'order', 'order_by', 'type', 's', 'f');
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$remove = array_diff(array_keys($_GET), $base_url_param_names);
		if ($remove) {
			$this->baseUrl = remove_query_arg($remove, esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '')));
		} else {
			$this->baseUrl = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? ''));
		}

		parent::__construct();

		wp_enqueue_style('pmui-admin-style', PMUI_ROOT_URL . '/static/css/admin.css', array(), PMUI_VERSION);
	}
	
	/**
	 * @see Controller::render()
	 */
	protected function render($viewPath = NULL)
	{
		// assume template file name depending on calling function
		if (is_null($viewPath)) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- used to resolve the default view path from the calling controller, not for debugging
			$trace = debug_backtrace();
			$viewPath = str_replace('_', '/', preg_replace('%^' . preg_quote(PMUI_Plugin::PREFIX, '%') . '%', '', strtolower($trace[1]['class']))) . '/' . $trace[1]['function'];
		}
		parent::render($viewPath);
	}
	
}