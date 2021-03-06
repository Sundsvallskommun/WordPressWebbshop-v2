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
	 * Singleton instance of class.
	 * @var null|SK_DeDU
	 */
	private static $instance = null;

	/**
	 * Sets up the plugin correctly regarding hooks etc.
	 */
	public function __construct() {
		// Get all values from options.
		$this->url      = get_option( 'dedu_url' );
		$this->username = get_option( 'dedu_username' );
		$this->password = get_option( 'dedu_password' );

		spl_autoload_register( array( $this, 'register_autoload' ) );

		// Init classes.
		$this->init_classes();

		// Add our product owner type.
		add_filter( 'skios_product_owner_types', array( $this, 'add_dedu_as_product_owner_type' ) );

		// Hook in to the order notification action.
		add_filter( 'skios_order_notification', array( $this, 'handle_dedu_order_notification' ), 10, 5 );
	}

	/**
	 * Returns Singleton instance of class.
	 * @return SK_DeDU
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
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
	 * Helper function for checking if DeDU credentials
	 * are set.
	 * @return boolean
	 */
	private function has_credentials() {
		return ! empty( $this->url ) && ! empty( $this->username ) && ! empty( $this->password );
	}

	/**
	 * Initiates classes.
	 * @return void
	 */
	private function init_classes() {
		$this->productfields = SK_DeDU_Product_Fields::get_instance();

		if ( is_admin() ) {
			require_once __DIR__ . '/../admin/class-sk-dedu-settings.php';
			$this->admin_settings = new SK_DeDU_Settings();
		}
	}

	/**
	 * Adds DeDU as a product owner type.
	 * @param  array $types
	 * @return array
	 */
	public function add_dedu_as_product_owner_type( $types ) {
		if ( empty( $types[ 'dedu' ] ) ) {
			$types[ 'dedu' ] = 'DeDU';
		}
		return $types;
	}

	/**
	 * Creates and sends an order to DeDU.
	 * @param  booelan $result
	 * @param  string  $type  Type of product owner
	 * @param  array   $owner The product owner
	 * @param  array   $order WC_Order
	 * @param  array   $items The products associated with this product owner
	 * @return void
	 */
	public function handle_dedu_order_notification( $result, $type, $owner, $order, $items ) {
		// Only handle DeDU orders.
		// Also make sure we have credentials.
		if ( 'dedu' === $type && $this->has_credentials() ) {
			try {
				// Init WS class.
				$dedu_ws = new SK_DeDU_WS( $this->url, $this->username, $this->password );

				// Send order.
				return $dedu_ws->send_order( $order, $items );
			} catch ( Exception $e ) {
				// Couldn't connect to DeDU. Return a WP_Error.
				return new WP_Error( 'dedu_connection_failed', 'N??got gick fel vid best??llningen.' );
			}
		}

		return $result;
	}

}
