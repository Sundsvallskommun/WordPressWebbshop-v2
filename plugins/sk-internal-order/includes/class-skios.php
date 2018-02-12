<?php
/**
 * SKIOS
 * =====
 *
 * The main plugin class file.
 *
 * Registers most of the important hooks and filters aswell
 * as includes and loads all other plugin classes.
 *
 * @since   20170105
 * @package SKIOS
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SKIOS {

	function __construct() {
		// Includes.
		$this->includes();

		// Init classes.
		$this->init_classes();

		// Register scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 10 );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' )	 );

		// Add init the gateway class when all plugins are loaded.
		add_action( 'plugins_loaded', array($this, 'init_skios_gateway_class' ) );

		// Add custom order status to be used in custom gateway.
		add_action( 'init', array($this, 'register_internal_order_status' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_internal_to_order_statuses' ) );

		// Hook in to the order notification action for the default email type.
		add_filter( 'skios_order_notification', array( $this, 'handle_order_notification' ), 10, 5 );

		// Fix for free orders that otherwise never go through the gateway.
		add_filter( 'woocommerce_cart_needs_payment', '__return_true', 747 );
	}

	/**
	 * Include necessary files.
	 * @return void
	 */
	public function includes() {
		include __DIR__ . '/define.php';
		include __DIR__ . '/skios-product-owner-functions.php';
		include __DIR__ . '/class-skios-admin-columns.php';
		include __DIR__ . '/class-skios-product-owner.php';
		include __DIR__ . '/class-skios-unique-order.php';
	}

	/**
	 * @return void
	 */
	public function init_classes() {
		$skios_admin_columns = new SKIOS_Admin_Columns();
		$skios_product_owner = new SKIOS_Product_Owner();
		$skios_unique_order = new SKIOS_Unique_Order();
	}

	/**
	 * Registers all plugin related scripts.
	 * @return void
	 */
	public function register_scripts() {
		wp_register_script( 'skios-admin-gateway-js', SKIOS_PLUGIN_URL . '/assets/js/gateway-settings.js', array( 'jquery' ), SKIOS_VERSION, true );
		wp_register_script( 'skios-quick-edit-js', SKIOS_PLUGIN_URL . '/assets/js/quick-edit.js', array( 'jquery', 'inline-edit-post' ), SKIOS_VERSION, true );
	}

	/**
	 * Enqueues scripts.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( $hook_suffix === 'edit.php' && $_REQUEST[ 'post_type' ] === 'product' ) {
			wp_enqueue_script( 'skios-quick-edit-js' );
		}
	}

	/**
	 * Adds a filter to the WooCommerce payment gateways.
	 * @return void
	 */
	public function init_skios_gateway_class() {

		include_once __DIR__ . '/class-skios-gateway.php';
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_skios_gateway_class' ) );
	}

	/**
	 * Adds our payment gateway to WooCommerce.
	 * @param  array $methods
	 * @return array
	 */
	public function add_skios_gateway_class( $methods ) {
		$methods[] = 'SKIOS_Gateway';
		return $methods;
	}

	/**
	 * Adds custom order status to be used by the gateway class after sending an
	 * order to the product owners.
	 * @return void
	 */
	public function register_internal_order_status() {
		register_post_status( 'wc-internal-order', array(
				'label'                     => 'Intern beställning',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Intern beställning <span class="count">(%s)</span>', 'Intern beställning <span class="count">(%s)</span>' )
		) );
	}

	/**
	 * Add to list of WC Order statuses.
	 * @param array $order_statuses
	 */
	public function add_internal_to_order_statuses( $order_statuses ) {

			$new_order_statuses = array();

			// add new order status after processing
			foreach ( $order_statuses as $key => $status ) {

					$new_order_statuses[ $key ] = $status;

					if ( 'wc-processing' === $key ) {
							$new_order_statuses['wc-internal-order'] = 'Intern beställning';
					}
			}

			return $new_order_statuses;
	}

	/**
	 * Handles the email type order notification.
	 * @param  string   $type  Type of product owner
	 * @param  array    $owner The product owner
	 * @param  WC_Order $order WC_Order
	 * @param  array    $items The order items
	 * @return void
	 */
	public function handle_order_notification( $result, $type, $owner, $order, $items ) {
		if ( $type === 'email' ) {
			try {
				$email = $owner[ 'identifier' ];
				skios_owner_order_email( $email, $order, $items );
			} catch ( Exception $e ) {
				// Couldn't connect to DeDU. Return a WP_Error.
				return new WP_Error( 'dedu_connection_failed', 'Något gick fel vid beställningen.' );
			}
		}

		return $result;
	}

}
