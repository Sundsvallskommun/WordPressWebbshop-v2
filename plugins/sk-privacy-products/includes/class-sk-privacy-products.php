<?php
/**
 * SK_Privacy_Products
 * ===================
 *
 * Main plugin file.
 *
 * Register hooks and filters.
 *
 * @since   0.1
 * @package SK_Privacy_Products
 */

class SK_Privacy_Products {

	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initiates all action and filter hooks.
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_custom_product_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'add_custom_product_field_save' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'remove_privacy_data' ), 10, 3 );

		add_action( 'woocommerce_single_product_summary', array( $this, 'display_privacy_info'), 15 );
	}

	/**
	 * Add a custom checkbox to product to enable/disable privacy of it.
	 * */
	public function add_custom_product_field() {
		global $woocommerce, $post;

		echo '<div class="options_group">';

		woocommerce_wp_checkbox( 
			array( 
				'id'            => '_privacy_enabled',
				'wrapper_class' => 'show_if_simple',
				'label'         => __('Sekretess', 'skpp' ), 
				'description'   => __( 'Efter lagt order som innehåller denna produkt rensas all produkt- och kundinformation från ordern. E-postnotiser med information går iväg som vanligt innan detta sker.', 'skpp' ) 
			)
		);

		echo '</div>';

	}

	/**
	 * Save privacy option value
	 */
	public function add_custom_product_field_save($post_id) {
		$woocommerce_checkbox = isset( $_POST['_privacy_enabled'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_privacy_enabled', $woocommerce_checkbox );
	}

	public function display_privacy_info() {
		global $post;

		if( 'yes' == get_post_meta( $post->ID, '_privacy_enabled', true ) ) {
			echo '<p class="privacy-info"><strong style="background-color:#e2401c; color: white; padding: .5em;">Sekretessprodukt</strong></p>';
		}

	}

	public function remove_privacy_data( $order_id, $old_status, $new_status ) {

		// Only check for privacy products on internal orders.
		if ( $new_status !== 'internal-order' ) return;

		$privacy = false;

		$order = new WC_Order( $order_id );

		$items = $order->get_items();

		if ( !empty( $items ) ) {

			foreach( $items as $item ) {
				// Check if any product has been set to privacy
				if( 'yes' == get_post_meta( $item['product_id'], '_privacy_enabled', true ) ) {
					$privacy = true;
				}
			}

		}

		// If no products has been set to privacy, do nothing.
		if (!$privacy) {
			return;
		}


		/**
		 * Remove address info from order.
		 */
		$address = array(
			'first_name' => 'Sekretess',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
			'email'      => '',
			'phone'      => '',
		);

		$order->set_address( $address, 'billing' );
		$order->set_address( $address, 'shipping' );

		// Remove all item data from order
		$order->remove_order_items();
	}


}
