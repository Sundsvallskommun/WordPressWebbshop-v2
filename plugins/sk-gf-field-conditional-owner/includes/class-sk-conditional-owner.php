<?php
/**
 * SK_Conditional_Owner
 * ====================
 *
 * Main plugin file.
 *
 * @since   20191111
 * @package SK_Conditional_Owner
 */

class SK_Conditional_Owner {

	/**
	 * Adds our actions and filters.
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_order_item_meta' ], 10, 4 );

		add_filter( 'sk_order_item_meta_key', [ $this, 'conditional_owner_order_item_meta_key' ], 10, 3 );
		add_filter( 'sk_order_item_meta_value', [ $this, 'conditional_owner_order_item_meta_value' ], 10, 3 );
		add_filter( 'skios_order_notification', [ $this, 'handle_order_notification' ], 10, 5 );
	}

	/**
	 * Save extra data we need from the field to the order item.
	 */
	public function add_order_item_meta( $item, $cart_item_key, $values, $order ) {

		$cart = WC()->cart->get_cart_contents();

		foreach ( $cart as $key => $cart_item ) {
			if ( $key === $cart_item_key ) {
				$item_data = apply_filters( 'woocommerce_get_item_data', [], $cart_item );
			}
			$form_id = rgars( $cart_item, '_gravity_form_data/id' );
			$form = GFFormsModel::get_form_meta( $form_id );

			foreach ( $form['fields'] as $field ) {

				if ( 'sk_conditional_owner' !== $field['type'] ) {
					continue;
				}

				$field_label = $field['label'];
				$invert_answer = $field['emailInvertField'];
				$email_label = isset( $field['emailFieldLabel'] ) ? $field['emailFieldLabel'] : null;

				foreach ( $item_data as $data ) {
					if ( $data['name'] !== $field_label ) {
						continue;
					}

					$email_answer = $data['value'];
					if ( $invert_answer ) {
						$email_answer = $email_answer === 'ja' ? 'nej' : 'ja';
					}
				}

				$extra_email = $email_answer === 'ja' ? $field['ownerEmailYes'] : $field['ownerEmailNo'];

				$conditional_owner_field = [
					'original_label'  => $field_label,
					'new_label'       => $email_label,
					'new_answer'      => $email_answer,
					'email_cc'        => $extra_email,
				];

				$item->add_meta_data( '_sk_conditional_owner', $conditional_owner_field );

			}

		}

	}

	/**
	 * Display the correct meta key we want to show in the owner email for conditional owner field.
	 */
	public function conditional_owner_order_item_meta_key( $key, $value, $item ) {

		// Get conditional owner field which will override the key and value in the email.
		$conditional_owner_data = $item->get_meta( '_sk_conditional_owner', false );

		foreach ( $conditional_owner_data as $data ) {
			$meta = $data->get_data();
			$meta = $meta['value'];
			if ( isset( $meta['original_label'] ) && $meta['original_label'] === $key) {
				return $meta['new_label'];
			}
		}

		return $key;
	}

	/**
	 * Display the correct meta value we want to show in the owner email for conditional owner field.
	 */
	public function conditional_owner_order_item_meta_value( $value, $key, $item ) {
		// Get conditional owner field which will override the key and value in the email.
		$conditional_owner_data = $item->get_meta( '_sk_conditional_owner', false );

		foreach ( $conditional_owner_data as $data ) {
			$meta = $data->get_data();
			$meta = $meta['value'];
			if ( isset( $meta['original_label'] ) && $meta['original_label'] === $key) {
				return $meta['new_answer'];
			}
		}

		return $value;
	}

	/**
	 * Handles the email type order notification.
	 * @param  string   $type  Type of product owner
	 * @param  array    $owner The product owner
	 * @param  WC_Order $order WC_Order
	 * @param  array    $items The order items
	 * @return void
	 */
	function handle_order_notification( $result, $type, $owner, $order, $items ) {
		try {
			$extra_emails = $this->maybe_get_extra_email_addresses( $items );
			foreach ( $extra_emails as $email ) {
				skios_owner_order_email( $email, $order, $items );
			}
		} catch ( Exception $e ) {
			// Couldn't connect to DeDU. Return a WP_Error.
			return new WP_Error( 'dedu_connection_failed', 'Något gick fel vid beställningen.' );
		}

		return $result;
	}

	/**
	 * Get list of extra email addreses based on conditional owner fields on the products.
	 * @param array $items
	 * 
	 * @return array
	 */
	public function maybe_get_extra_email_addresses( $items ) {
		$extra_emails = [];
		foreach ( $items as $item ) {
			$conditional_owner_data = $item->get_meta( '_sk_conditional_owner', false );

			foreach ( $conditional_owner_data as $data ) {
				$meta = $data->get_data();
				if ( isset( $meta['value'] ) && isset( $meta['value']['email_cc'] ) ) {
					$extra_emails[] = $meta['value']['email_cc'];
				}
			}

		}

		return $extra_emails;
	}

}
