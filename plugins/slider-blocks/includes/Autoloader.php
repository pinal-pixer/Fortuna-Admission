<?php
/**
 * PSR-4 Autoloader for the GutSlider namespace.
 *
 * Maps GutSlider\Foo\Bar to includes/Foo/Bar.php.
 *
 * @package GutSlider
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GutSlider_Autoloader
 *
 * Handles PSR-4 autoloading for all classes within the GutSlider namespace.
 * This class is intentionally not namespaced because it must be loaded via
 * require_once before the autoloader is registered.
 *
 * @since 3.0.0
 */
final class GutSlider_Autoloader {

	/**
	 * The namespace prefix for GutSlider classes.
	 *
	 * @var string
	 */
	private const NAMESPACE_PREFIX = 'GutSlider\\';

	/**
	 * Base directory for the namespace prefix.
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $base_dir The base directory where namespaced classes reside.
	 */
	public function __construct( string $base_dir ) {
		$this->base_dir = rtrim( $base_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Register the autoloader with spl_autoload_register.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Autoload a class file based on its fully-qualified class name.
	 *
	 * @since 3.0.0
	 *
	 * @param string $class The fully-qualified class name.
	 * @return void
	 */
	public function autoload( string $class ): void {
		if ( strpos( $class, self::NAMESPACE_PREFIX ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, strlen( self::NAMESPACE_PREFIX ) );
		$file           = $this->base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
