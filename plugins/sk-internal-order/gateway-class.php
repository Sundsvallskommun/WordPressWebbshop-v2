<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include_once __DIR__ . '/includes/product-owner.php';

/**
 * SKIOS Gateway class
 */
class SKIOS_Gateway extends WC_Payment_Gateway {

	function __construct() {
		$this->id                 = 'skios';
		$this->icon               = '';
		$this->has_fields         = false;
		$this->title              = 'TITLE';
		$this->method_title       = 'SK Internal Order System';
		$this->method_description = 'Varje produkt tillhör en ägare och när ordern läggs går ett mejl iväg till respektive ägare om beställningen.';

		$this->init_form_fields();
		$this->init_settings();

		$this->title              = $this->get_option( 'title' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	function init_form_fields() {

		$admin_email = get_bloginfo( 'admin_email');

		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Internal Order', 'skios' ),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default' => __( 'Internbeställning', 'woocommerce' ),
				'desc_tip'      => true,
			),
			'product_owners' => array(
				'title' => __( 'Produktägare (en ägare per rad i formatet: "Benämning, Epost"). Första raden används som standard på produkter som inte blivit tilldelad en ägare.', 'skios' ),
				'type' => 'textarea',
				'default' => 'Standard, '.$admin_email
			)
		);

	}

	function process_payment( $order_id ) {

		global $woocommerce;

		$order = new WC_Order( $order_id );

		$owners = skios_get_product_owners();

		$items = $order->get_items();

		$sorted_items = array();

		foreach ($items as $item) {

			$product_id = $item['product_id'];

			$owner_id = get_post_meta( $product_id, '_product_owner', true);

			if (!$owner_id) {
				$owner = skios_get_default_product_owner();
			} else {
				$owner = skios_get_product_owner_by_id($owner_id);
			}

			$owner_id = $owner['id'];

			$sorted_items[$owner_id][] = $item;
		}

		skios_handle_order_notifications( $order, $sorted_items );

		return false;

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('wc-internal-order', __( 'Orderinfo skickat till produkternas ägare.', 'skios' ));

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}

}
