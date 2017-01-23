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

	/**
	 * Adds DeDU as a product owner in the database.
	 * @return void
	 */
	public function add_dedu_as_product_owner() {
		// Check if DeDU already exists.
		$existing_product_owners = skios_get_product_owners();
		$found = false;
		foreach ( $existing_product_owners as $product_owner ) {
			if ( $product_owner[ 'type' ] === 'dedu' ) {
				$found = true;
			}
		}

		// If not found, add it.
		if ( ! $found ) {
			skios_insert_product_owner( array(
				'label'			=> 'DeDU',
				'type'			=> 'dedu',
				'identifier'	=> 'dedu-product-owner',
			) );
		}
	}

}