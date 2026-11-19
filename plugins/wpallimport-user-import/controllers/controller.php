<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Common logic for all shortcodes plugin implements
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */
abstract class PMUI_Controller {
	/**
	 * Error messages
	 * @var WP_Error
	 */
	protected $errors;
	/**
	 * Associative array of data which will be automatically available as variables when template is rendered
	 * @var array
	 */
	public $data = array();
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->errors = new WP_Error();
		
		$this->init();
	}
	
	/**
	 * Method to put controller initialization logic to
	 */
	protected function init() {}
	
	/**
	 * Checks wether protocol is HTTPS and redirects user to secure connection if not
	 */
	protected function force_ssl() {
		if (force_ssl_admin() && ! is_ssl()) {
			$request_uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? ''));
			$http_host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'] ?? ''));
			if ( 0 === strpos($request_uri, 'http') ) {
				wp_safe_redirect(preg_replace('|^http://|', 'https://', $request_uri)); die();
			} else {
				wp_safe_redirect('https://' . $http_host . $request_uri); die();
			}
		}
	}
	
	/**
	 * Method returning resolved template content
	 * 
	 * @param string[optional] $viewPath Template path to render
	 */
	protected function render($viewPath = null) {
		// assume template file name depending on calling function
		if (is_null($viewPath)) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- used to resolve the default view path from the calling controller, not for debugging
			$trace = debug_backtrace();
			$viewPath = str_replace('_', '/', preg_replace('%^' . preg_quote(PMUI_Plugin::PREFIX, '%') . '%', '', strtolower($trace[1]['class']))) . '/' . $trace[1]['function'];
		}
		// append file extension if not specified
		if ( ! preg_match('%\.php$%', $viewPath)) {
			$viewPath .= '.php';
		}
		$filePath = PMUI_Plugin::ROOT_DIR . '/views/' . $viewPath;
		if (is_file($filePath)) {
			extract($this->data);
			include $filePath;
		} else {
			throw new Exception("Requested template file " . esc_html($filePath) . " is not found.");
		}
	}
	
	/**
	 * Display list of errors
	 * 
	 * @param string|array|WP_Error[optional] $msgs
	 */
	protected function error($msgs = NULL) {
		if (is_null($msgs)) {
			$msgs = $this->errors;
		}
		if (is_wp_error($msgs)) {
			$msgs = $msgs->get_error_messages();
		}
		if ( ! is_array($msgs)) {
			$msgs = array($msgs);
		}
		$this->data['errors'] = $msgs;
		
		$viewPathRel = str_replace('_', '/', preg_replace('%^' . preg_quote(PMUI_Plugin::PREFIX, '%') . '%', '', strtolower(get_class($this)))) . '/error.php';
		if (is_file(PMUI_Plugin::ROOT_DIR . '/views/' . $viewPathRel)) { // if calling controller class has specific error view
			$this->render($viewPathRel);
		} else { // render default error view
			$this->render('controller/error.php');
		}
	}
	
}