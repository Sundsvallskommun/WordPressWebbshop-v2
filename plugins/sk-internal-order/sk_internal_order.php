<?php
/*
Plugin Name: SK Internal Order System
Plugin URI:  utveckling.sundsvall.se
Description: Description
Version:     20170105
Author:      SK/FMCA
Author URI:  utveckling.sundsvall.se
License:     ??
License URI: ??
Text Domain: skios
Domain Path:
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SKIOS {

	function __construct() {
		// Add custom gateway
		add_action( 'plugins_loaded', array($this, 'init_skios_gateway_class' ) );

		// Add custom order status to be used in custom gateway.
		add_action( 'init', array($this, 'register_internal_order_status' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_internal_to_order_statuses' ) );

		// Display and save product owner option on product admin screen.
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'woo_add_custom_general_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'woo_add_custom_general_fields_save' ) );

	}

	function init_skios_gateway_class() {
		include_once __DIR__ . '/gateway-class.php';

		add_filter( 'woocommerce_payment_gateways', array($this, 'add_your_gateway_class' ) );
	}

	function add_your_gateway_class( $methods ) {
		$methods[] = 'SKIOS_Gateway';
		return $methods;
	}

	/**
	 * Add custom order status to be used by the gateway class after sending an
	 * order to the product owners.
	 */
	function register_internal_order_status() {
		register_post_status( 'wc-internal-order', array(
				'label'                     => 'Intern beställning',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Intern beställning <span class="count">(%s)</span>', 'Intern beställning <span class="count">(%s)</span>' )
		) );
	}

	// Add to list of WC Order statuses
	function add_internal_to_order_statuses( $order_statuses ) {

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
	 * Add select to product where product owner are selected.
	 */
	function woo_add_custom_general_fields() {

		global $woocommerce, $post;

		$owners = skios_get_product_owners();

		$options = array();

		foreach ( $owners as $owner ) {

			$id    = $owner['id'];
			$label = $owner['label'];
			$email = $owner['email'];

			$options[$id] = $label;
		}


		echo '<div class="options_group">';

		// Select
		woocommerce_wp_select(
			array(
				'id'      => '_product_owner',
				'label'   => __( 'Produktägare', 'woocommerce' ),
				'options' => $options
			)
		);

		echo '</div>';
	}

	/**
	 * Save product owner field
	 */
	function woo_add_custom_general_fields_save( $post_id ){

		$product_owner = $_POST['_product_owner'];
		if( !empty( $product_owner ) )
			update_post_meta( $post_id, '_product_owner', esc_attr( $product_owner ) );

	}

}

new SKIOS;

/**
 * Send email with order data to product owners. Only include the products that
 * belong to the respective owner in each email.
 */
function skios_handle_order_notifications( $order, $sorted_items ) {
	return false;
}

/**
 * Get an array of availanle product owners (name and email).
 */
function skios_get_product_owners() {

	$options = get_option( 'woocommerce_skios_settings', false );

	$mock_owners = array(
		array(
			'id'    => '1',
			'label' => 'Rickard Karlsson',
			'email' => 'rickard@fmca.se',
		),
		array(
			'id'    => '3',
			'label' => 'Johan Linder',
			'email' => 'johan@fmca.se',
		)
	);

	// Return mock data or empty array (for testing purposes only)
	return rand(0, 10) > 2.5 ? $mock_owners : array();

}

/**
 * Get default (first) product owner.
 */
function skios_get_default_product_owner() {
	$owners = skios_get_product_owners();

	return isset($owners[0]) ? $owners[0] : false;
}
