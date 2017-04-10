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
		add_action( 'woocommerce_order_status_changed', array( $this, 'remove_privacy_data' ), 100, 3 );

		add_action( 'woocommerce_single_product_summary', array( $this, 'display_privacy_info'), 15 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'display_privacy_info'), 15 );

		add_filter( 'woocommerce_add_to_cart_validation', array($this, 'add_to_cart_validation'), 10, 5 );
	}

	function add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = '', $variations= '' ) {

		if( !$passed ) return false;

		global $woocommerce;

		$items = $woocommerce->cart->get_cart();

		$privacy_in_cart = false;

		if ( !empty( $items ) ) {

			foreach( $items as $item ) {
				// Check if any product has been set to privacy
				if( 'yes' == get_post_meta( $item['product_id'], '_privacy_enabled', true ) ) {
					$privacy_in_cart = true;
				}
			}

		}

		$is_privacy = ( 'yes' == get_post_meta( $product_id, '_privacy_enabled', true ) );

		if ( count($items) < 1 ) {
			// Cart empty, product can be added
			$passed = true;
		}

		if ( true == $is_privacy && true == $privacy_in_cart ) {
			// Privacy product can be added to cart with privacy items.
			$passed = true;
		}

		if ( false == $is_privacy && true == $privacy_in_cart ) {
			// Non privacy product can not be added to cart with privacy items.
			$passed = false;
			wc_add_notice('Varukorgen innehåller sekretessvaror, slutför ordern med dessa innan du köper denna vara.', 'error');
		}

		if ( true == $is_privacy && (false == $privacy_in_cart && count( $items ) > 0 ) ) {
			// Privacy product can not be added to cart with non privacy products.
			$passed = false;
			wc_add_notice('Varukorgen innehåller varor som inte är sekretessmarkerade, slutför ordern med dessa innan du köper denna sekretessvara.', 'error');
		}

		return $passed;

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
			?>
			<style media="screen">
				.privacy-info {
					position: relative;
				}
				.privacy-info .privacy-tooltip {
					text-align: left;
					color: white;
					font-weight: 400;
					font-size: 0.875em;
					line-height: 1.2;
					padding: 1em;
					display: none;
					position: absolute;
					top: -10px;
					background: #e2401c;
					left: 50%;
					width: 200px;
					transform: translate( -50%, -100%);
				}

				.privacy-tooltip:after {
					content: '';
					display: inline-block;
					position: absolute;
					width: 0em;
					height: 0em;
					background: transparent;
					border: .3em solid #e2401c;
					border-left-color: transparent;
					border-bottom-color: transparent;
					border-right-color: transparent;
					bottom: 0;
					left: 50%;
					transform: translate( -50%, 100%);

				}

				.privacy-info strong:hover + .privacy-tooltip {
					display: inline-block;
				}
			</style>
			<?php
			echo '<p class="privacy-info">
				<strong style="background-color:#e2401c; color: white; padding: .5em;">Sekretess</strong>
				<span class="privacy-tooltip">Efter lagd order som innehåller sekretessprodukter rensas all produkt- och kundinformation från ordern.</span>
				</p>';
		}

	}

	private function order_contains_privacy_products( $order_id ) {

		if ( !$order_id ) return false;

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

		return $privacy;
	}

	public function remove_privacy_data( $order_id, $old_status, $new_status ) {

		// Only check for privacy products on internal orders.
		if ( $new_status !== 'internal-order' ) return;

		// If no products has been set to privacy, do nothing.
		if( !$this->order_contains_privacy_products($order_id) ) {
			return;
		}

		$order = new WC_Order( $order_id );

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
