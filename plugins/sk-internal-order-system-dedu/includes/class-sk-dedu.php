<?php
/**
 * SK_DeDU
 * ======
 *
 * Main plugin file.
 *
 * Register hooks and filters.
 *
 * @since   0.1
 * @package SK_DeDU
 */

class SK_DeDU {

	/**
	 * Sets up the plugin correctly regarding hooks etc.
	 */
	public function __construct() {
		spl_autoload_register( array( $this, 'register_autoload' ) );

		// Init classes.
		$this->init_classes();
	}

	/**
	 * Registers our autoloader.
	 * @param  string $class Class name
	 * @return void
	 */
	public function register_autoload( $class ) {
		// Only load plugin class files.
		if ( false !== strpos( $class, 'SK_DeDU' ) ) {
			// Change all _ to - and to lowercase.
			$class = strtolower( str_replace( '_', '-', $class ) );

			// Include file.
			include __DIR__ . '/class-' . $class . '.php';
		}
	}

	/**
	 * Initiates classes.
	 * @return void
	 */
	private function init_classes() {
		$productfields = new SK_DeDU_Product_Fields();
	}

}