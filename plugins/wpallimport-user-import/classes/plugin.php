<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin file, Introduces MVC pattern
 *
 * @singletone
 * @author Maksym Tsypliakov <maksym.tsypliakov@gmail.com>
 */

final class PMUI_Plugin {
	/**
	 * Singletone instance
	 * @var PMUI_Plugin
	 */
	protected static $instance;

	/**
	 * Plugin root dir
	 * @var string
	 */
	const ROOT_DIR = PMUI_ROOT_DIR;
	/**
	 * Plugin root URL
	 * @var string
	 */
	const ROOT_URL = PMUI_ROOT_URL;
	/**
	 * Prefix used for names of shortcodes, action handlers, filter functions etc.
	 * @var string
	 */
	const PREFIX = PMUI_PREFIX;
	/**
	 * Plugin file path
	 * @var string
	 */
	const FILE = PMUI_ROOT_DIR . '/wpallimport-user-import.php';

	/**
	 * Return singletone instance
	 * @return PMUI_Plugin
	 */
	static public function getInstance() {
		if (self::$instance == NULL) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Common logic for requestin plugin info fields
	 */
	public function __call($method, $args) {
		if (preg_match('%^get(.+)%i', $method, $mtch)) {
			$info = get_plugin_data(self::FILE);
			if (isset($info[$mtch[1]])) {
				return $info[$mtch[1]];
			}
		}
		throw new Exception("Requested method " . esc_html(get_class($this)) . "::" . esc_html($method) . " doesn't exist.");
	}

	/**
	 * Get path to plagin dir relative to wordpress root
	 * @param bool[optional] $noForwardSlash Whether path should be returned withot forwarding slash
	 * @return string
	 */
	public function getRelativePath($noForwardSlash = false) {
		$wp_root = str_replace('\\', '/', ABSPATH);
		return ($noForwardSlash ? '' : '/') . str_replace($wp_root, '', self::ROOT_DIR);
	}

	/**
	 * Check whether plugin is activated as network one
	 * @return bool
	 */
	public function isNetwork() {
		if ( !is_multisite() )
		return false;

		$plugins = get_site_option('active_sitewide_plugins');
		if (isset($plugins[plugin_basename(self::FILE)]))
			return true;

		return false;
	}

	/**
	 * Check whether permalinks is enabled
	 * @return bool
	 */
	public function isPermalinks() {
		global $wp_rewrite;

		return $wp_rewrite->using_permalinks();
	}

	/**
	 * Return prefix for plugin database tables
	 * @return string
	 */
	public function getTablePrefix() {
		global $wpdb;
		return ($this->isNetwork() ? $wpdb->base_prefix : $wpdb->prefix) . self::PREFIX;
	}

	/**
	 * Return prefix for wordpress database tables
	 * @return string
	 */
	public function getWPPrefix() {
		global $wpdb;
		return ($this->isNetwork() ? $wpdb->base_prefix : $wpdb->prefix);
	}

	/**
	 * Class constructor containing dispatching logic
	 * @param string $rootDir Plugin root dir
	 * @param string $pluginFilePath Plugin main file
	 */
	protected function __construct() {

		// create/update required database tables

		// register autoloading method
		spl_autoload_register(array($this, 'autoload'));

		// register helpers
		if (is_dir(self::ROOT_DIR . '/helpers')) foreach (PMUI_Helper::safe_glob(self::ROOT_DIR . '/helpers/*.php', PMUI_Helper::GLOB_RECURSE | PMUI_Helper::GLOB_PATH) as $filePath) {
			require_once $filePath;
		}

		register_activation_hook(self::FILE, array($this, 'activation'));

		// register action handlers
		if (is_dir(self::ROOT_DIR . '/actions')) if (is_dir(self::ROOT_DIR . '/actions')) foreach (PMUI_Helper::safe_glob(self::ROOT_DIR . '/actions/*.php', PMUI_Helper::GLOB_RECURSE | PMUI_Helper::GLOB_PATH) as $filePath) {
			require_once $filePath;
			$function = $actionName = basename($filePath, '.php');
			if (preg_match('%^(.+?)[_-](\d+)$%', $actionName, $m)) {
				$actionName = $m[1];
				$priority = intval($m[2]);
			} else {
				$priority = 10;
			}
			add_action($actionName, self::PREFIX . str_replace('-', '_', $function), $priority, 99); // since we don't know at this point how many parameters each plugin expects, we make sure they will be provided with all of them (it's unlikely any developer will specify more than 99 parameters in a function)
		}

		// register filter handlers
		if (is_dir(self::ROOT_DIR . '/filters')) foreach (PMUI_Helper::safe_glob(self::ROOT_DIR . '/filters/*.php', PMUI_Helper::GLOB_RECURSE | PMUI_Helper::GLOB_PATH) as $filePath) {
			require_once $filePath;
			$function = $actionName = basename($filePath, '.php');
			if (preg_match('%^(.+?)[_-](\d+)$%', $actionName, $m)) {
				$actionName = $m[1];
				$priority = intval($m[2]);
			} else {
				$priority = 10;
			}
			add_filter($actionName, self::PREFIX . str_replace('-', '_', $function), $priority, 99); // since we don't know at this point how many parameters each plugin expects, we make sure they will be provided with all of them (it's unlikely any developer will specify more than 99 parameters in a function)
		}

		// register shortcodes handlers
		if (is_dir(self::ROOT_DIR . '/shortcodes')) foreach (PMUI_Helper::safe_glob(self::ROOT_DIR . '/shortcodes/*.php', PMUI_Helper::GLOB_RECURSE | PMUI_Helper::GLOB_PATH) as $filePath) {
			$tag = strtolower(str_replace('/', '_', preg_replace('%^' . preg_quote(self::ROOT_DIR . '/shortcodes/', '%') . '|\.php$%', '', $filePath)));
			add_shortcode($tag, array($this, 'shortcodeDispatcher'));
		}

		// register admin page pre-dispatcher
		add_action('admin_init', array($this, 'adminInit'));

	}

	/**
	 * pre-dispatching logic for admin page controllers
	 */
	public function adminInit() {
		if ( ! class_exists('PMXI_Plugin') ) return;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		if (isset($_GET['page'])) {
			$page = sanitize_text_field(wp_unslash($_GET['page']));
		} else {
			$page = isset($_POST['page']) ? sanitize_text_field(wp_unslash($_POST['page'])) : '';
		}
		$page = strtolower($page);
		if (preg_match('%^' . preg_quote(str_replace('_', '-', self::PREFIX), '%') . '([\w-]+)$%', $page)) {
			if (isset($_GET['action'])) {
				$action = sanitize_text_field(wp_unslash($_GET['action']));
			} else {
				$action = isset($_POST['action']) ? sanitize_text_field(wp_unslash($_POST['action'])) : 'index';
			}
			$this->adminDispatcher($page, strtolower($action));
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Dispatch shorttag: create corresponding controller instance and call its index method
	 * @param array $args Shortcode tag attributes
	 * @param string $content Shortcode tag content
	 * @param string $tag Shortcode tag name which is being dispatched
	 * @return string
	 */
	public function shortcodeDispatcher($args, $content, $tag) {

		$controllerName = self::PREFIX . preg_replace_callback('%(^|_).%', array($this, "replace_callback"), $tag);// capitalize first letters of class name parts and add prefix
		$controller = new $controllerName();
		if ( ! $controller instanceof PMUI_Controller) {
			throw new Exception("Shortcode `" . esc_html($tag) . "` matches to a wrong controller type.");
		}
		ob_start();
		$controller->index($args, $content);
		return ob_get_clean();
	}

	public function replace_callback($matches){
		return strtoupper($matches[0]);
	}

	/**
	 * Dispatch admin page: call corresponding controller based on get parameter `page`
	 * The method is called twice: 1st time as handler `parse_header` action and then as admin menu item handler
	 * @param string[optional] $page When $page set to empty string ealier buffered content is outputted, otherwise controller is called based on $page value
	 */
	public function adminDispatcher($page = '', $action = 'index') {
		static $buffer = NULL;
		static $buffer_callback = NULL;
		if ('' === $page) {
			if ( ! is_null($buffer)) {
				echo '<div class="wrap">';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffer holds a fully rendered controller page whose views escape their own output
				echo $buffer;
				do_action('pmui_action_after');
				echo '</div>';
			} elseif ( ! is_null($buffer_callback)) {
				echo '<div class="wrap">';
				call_user_func($buffer_callback);
				do_action('pmui_action_after');
				echo '</div>';
			} else {
				throw new Exception('There is no previousely buffered content to display.');
			}
		} else {
			$controllerName = preg_replace_callback('%(^' . preg_quote(self::PREFIX, '%') . '|_).%', array($this, "replace_callback"),str_replace('-', '_', $page));
			$actionName = str_replace('-', '_', $action);
			if (self::isDispatchableAction($controllerName, $actionName)) {

				if ( ! get_current_user_id() or ! current_user_can(PMXI_Plugin::$capabilities)) {
				    // This nonce is not valid.
				    die( 'Security check' );

				} else {

					$this->_admin_current_screen = (object)array(
						'id' => $controllerName,
						'base' => $controllerName,
						'action' => $actionName,
						'is_ajax' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REQUESTED_WITH']))) === 'xmlhttprequest',
						'is_network' => is_network_admin(),
						'is_user' => is_user_admin(),
					);
					add_filter('current_screen', array($this, 'getAdminCurrentScreen'));
					add_filter('admin_body_class', function(){return PMUI_Plugin::PREFIX . "plugin";});

					$controller = new $controllerName();
					if ( ! $controller instanceof PMUI_Controller_Admin) {
						throw new Exception("Administration page `" . esc_html($page) . "` matches to a wrong controller type.");
					}

					if ($this->_admin_current_screen->is_ajax) { // ajax request
						$controller->$actionName();
						do_action('pmui_action_after');
						die(); // stop processing since we want to output only what controller is randered, nothing in addition
					} elseif ( ! $controller->isInline) {
						ob_start();
						$controller->$actionName();
						$buffer = ob_get_clean();
					} else {
						$buffer_callback = array($controller, $actionName);
					}
				}

			} else { // redirect to dashboard if requested page and/or action don't exist
				wp_safe_redirect(admin_url()); die();
			}
		}
	}

	protected static function isDispatchableAction($controllerName, $actionName) {
		// Underscore-prefixed names are rejected so `action=__construct` cannot re-run a constructor.
		if ('' === $actionName or 0 === strpos($actionName, '_')) {
			return false;
		}
		if ( ! class_exists($controllerName) or ! method_exists($controllerName, $actionName)) {
			return false;
		}
		$method = new ReflectionMethod($controllerName, $actionName);

		return $method->isPublic() && ! $method->isStatic() && ! $method->isAbstract();
	}

	protected $_admin_current_screen = NULL;
	public function getAdminCurrentScreen()
	{
		return $this->_admin_current_screen;
	}

	/**
	 * Autoloader
	 * It's assumed class name consists of prefix followed by its name which in turn corresponds to location of source file
	 * if `_` symbols replaced by directory path separator. File name consists of prefix folloed by last part in class name (i.e.
	 * symbols after last `_` in class name)
	 * When class has prefix it's source is looked in `models`, `controllers`, `shortcodes` folders, otherwise it looked in `core` or `library` folder
	 *
	 * @param string $className
	 * @return bool
	 */
	public function autoload($className) {

            if ( ! preg_match('/PMUI/m', $className) ) {
                return false;
            }

		$is_prefix = false;
		$filePath = str_replace('_', '/', preg_replace('%^' . preg_quote(self::PREFIX, '%') . '%', '', strtolower($className), 1, $is_prefix)) . '.php';
		if ( ! $is_prefix) { // also check file with original letter case
			$filePathAlt = $className . '.php';
		}
		foreach ($is_prefix ? array('models', 'controllers', 'shortcodes', 'classes') : array() as $subdir) {
			$path = self::ROOT_DIR . '/' . $subdir . '/' . $filePath;
			if (is_file($path)) {
				require $path;
				return TRUE;
			}
			if ( ! $is_prefix) {
				$pathAlt = self::ROOT_DIR . '/' . $subdir . '/' . $filePathAlt;
				if (is_file($pathAlt)) {
					require $pathAlt;
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Plugin activation logic
	 */
	public function activation() {
		// Uncaught exception doesn't prevent plugin from being activated, therefore replace it with fatal error so it does.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- fatal error is raised on purpose so activation fails visibly
		set_exception_handler(function($e){trigger_error(esc_html($e->getMessage()), E_USER_ERROR);});
	}

	/**
	 * Method returns default import options, main utility of the method is to avoid warnings when new
	 * option is introduced but already registered imports don't have it
	 */
	public static function get_default_import_options() {
		return apply_filters( 'pmui_default_import_options', array(
			'pmui' => array(
				'import_users' => 0,
				'first_name' => '',
				'last_name' => '',
				'role' => '',
				'nickname' => '',
				'description' => '',
				'login' => '',
				'pass' => '',
				'nicename' => '',
				'email' => '',
				'registered' => '',
				'display_name' => '',
				'url' => ''
			),
			'is_update_first_name' => 1,
			'is_update_last_name' => 1,
			'is_update_role' => 1,
			'is_update_nickname' => 1,
			'is_update_description' => 1,
			'is_update_login' => 1,
			'is_update_password' => 1,
			'is_update_nicename' => 1,
			'is_update_email' => 1,
			'is_update_registered' => 1,
			'is_update_display_name' => 1,
			'is_update_url' => 1,
			'is_hashed_wordpress_password' => 0
		) );
	}
}
