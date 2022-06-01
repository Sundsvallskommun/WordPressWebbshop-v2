<?php
/**
 * SK_pob
 * ======
 *
 * Main plugin file.
 *
 * Register hooks and filters.
 *
 * @since   0.1
 * @package SK_pob
 */

class SK_POB {

	/**
	 * Singleton instance of class.
	 * @var null|SK_POB
	 */
	private static $instance = null;

	/**
	 * Sets up the plugin correctly regarding hooks etc.
	 */
	public function __construct() {
		// Get all values from options.
		$this->url      = get_option( 'pob_url' );
		$this->username = get_option( 'pob_username' );
		$this->password = get_option( 'pob_password' );

		spl_autoload_register( array( $this, 'register_autoload' ) );

		// Init classes.
		$this->init_classes();

		// Add our product owner type.
		add_filter( 'skios_product_owner_types', array( $this, 'add_pob_as_product_owner_type' ) );

		// Hook in to the order notification action.
		add_filter( 'skios_order_notification', array( $this, 'handle_pob_order_notification' ), 10, 5 );
	}

	/**
	 * Returns Singleton instance of class.
	 * @return SK_POB
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
		if ( false !== strpos( $class, 'SK_POB' ) ) {
			// Change all _ to - and to lowercase.
			$class = strtolower( str_replace( '_', '-', $class ) );

			// Include file.
			include __DIR__ . '/class-' . $class . '.php';
		}
	}

	/**
	 * Helper function for checking if pob credentials
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

		if ( is_admin() ) {
			require_once __DIR__ . '/../admin/class-sk-pob-settings.php';
			$this->admin_settings = new SK_POB_Settings();
		}
	}

	/**
	 * Adds pob as a product owner type.
	 * @param  array $types
	 * @return array
	 */
	public function add_pob_as_product_owner_type( $types ) {
		if ( empty( $types[ 'pob' ] ) ) {
			$types[ 'pob' ] = 'POB';
		}
		return $types;
	}

	/**
	 * Creates and sends an order to pob.
	 * @param  booelan $result
	 * @param  string  $type  Type of product owner
	 * @param  array   $owner The product owner
	 * @param  array   $order WC_Order
	 * @param  array   $items The products associated with this product owner
	 * @return void
	 */
	public function handle_pob_order_notification( $result, $type, $owner, $order, $items ) {
		// Only handle pob orders.
		// Also make sure we have credentials.
		if ( ('pob' === $type && $this->has_credentials())) {
			try {
				// Init WS class.
				$pob_ws = new SK_POB_WS( $this->url, $this->username, $this->password, $type );

				// Send order.
				return $pob_ws->send_order( $order, $items );
			} catch ( Exception $e ) {
				// Couldn't connect to pob. Return a WP_Error.
				return new WP_Error( 'pob_connection_failed', 'Något gick fel vid beställningen.' );
			}
		}
		
		return $result;
	}

	public function create_pob_case($data, $memo) {
		$pob_ws = new SK_POB_WS( $this->url, $this->username, $this->password );
		return $pob_ws->create_pob_case($data, $memo);
	}
}
