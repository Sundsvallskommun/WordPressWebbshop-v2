<?php
/**
 * Get an array of available product owners (name and email).
 * @return array
 */
function skios_get_product_owners() {

	$options = get_option( 'woocommerce_skios_settings', false );

	$product_owners = isset( $options[ 'product_owners' ] ) ? $options[ 'product_owners' ] : array();

	return apply_filters( 'skios_product_owners', $product_owners );

}

/**
 * Get default (first) product owner.
 * @return array Product owner
 */
function skios_get_default_product_owner() {
	$owners = skios_get_product_owners();

	return isset($owners[0]) ? $owners[0] : false;
}

/**
 * Get product owner by id.
 * @param  integer|null $id
 * @return array            Product owner
 */
function skios_get_product_owner_by_id($id = null) {

	if(!$id || 0 === $id) return false;

	$owners = skios_get_product_owners();

	foreach( $owners as $owner ) {
		if ( $owner['id'] == $id ) {
			return $owner;
		}
	}

	return false;

}

/**
 * Get product owner by label.
 * @param  string $label
 * @return array         Product owner
 */
function skios_get_product_owner_by_label( $label ) {
	$owners = skios_get_product_owners();

	foreach ( $owners as $owner ) {
		if ( $owner[ 'label' ] === $label ) {
			return $owner;
		}
	}

	return false;
}

/**
 * Get product owner by identifier.
 * @param  string $identifier
 * @return array              Product owner
 */
function skios_get_product_owner_by_identifier( $identifier ) {
	$owners = skios_get_product_owners();

	foreach ( $owners as $owner ) {
		if ( $owner[ 'identifier' ] === $identifier ) {
			return $owner;
		}
	}

	return false;
}

/**
 * Inserts a new product owner.
 * @param  array  $args
 * @return array|WP_Error
 */
function skios_insert_product_owner( $args = array() ) {
	$defaults = array(
		'label'			=> '',
		'type'			=> 'email',
		'identifier'	=> '',
	);
	$args = wp_parse_args( $args, $defaults );

	if ( '' === trim( $args[ 'label' ] ) ) {
		return new WP_Error( 'empty_product_owner_name', __( 'Produktägare får inte ha en tom benämning.', 'skios' ) );
	}

	if ( '' === trim( $args[ 'type' ] ) ) {
		return new WP_Error( 'empty_product_owner_type', __( 'Produktägare får inte ha en tom typ.', 'skios' ) );
	}

	if ( '' === trim( $args[ 'identifier' ] ) ) {
		return new WP_Error( 'empty_product_owner_identifier', __( 'Produktägare får inte ha en tom identifierare.', 'skios' ) );
	}

	// Get the current id from options.
	$options = get_option( 'woocommerce_skios_settings' );
	$id = (int) $options[ 'product_owner_count' ] + 1;

	// Get all product_owners.
	$all_product_owners = skios_get_product_owners();

	// Insert the new one.
	$all_product_owners[] = ( $new_product_owner = array(
		'id'			=> $id,
		'type'			=> $args[ 'type' ],
		'label'			=> $args[ 'label' ],
		'identifier'	=> $args[ 'identifier' ],
	) );

	// Save in options.
	$options[ 'product_owner_count' ] = $id;
	$options[ 'product_owners' ] = $all_product_owners;
	update_option( 'woocommerce_skios_settings', $options );

	// Return new product owner.
	return $new_product_owner;
}

/**
 * Updates an existing product owner.
 * @param  integer $id
 * @param  array  $args
 * @return array|WP_Error
 */
function skios_update_product_owner( $id, $args = array() ) {
	$defaults = array(
		'label'			=> '',
		'type'			=> 'email',
		'identifier'	=> '',
	);
	$args = wp_parse_args( $args, $defaults );

	if ( '' === trim( $args[ 'label' ] ) ) {
		return new WP_Error( 'empty_product_owner_name', __( 'Produktägare får inte ha en tom benämning.', 'skios' ) );
	}

	if ( '' === trim( $args[ 'type' ] ) ) {
		return new WP_Error( 'empty_product_owner_type', __( 'Produktägare får inte ha en tom typ.', 'skios' ) );
	}

	if ( '' === trim( $args[ 'identifier' ] ) ) {
		return new WP_Error( 'empty_product_owner_identifier', __( 'Produktägare får inte ha en tom identifierare.', 'skios' ) );
	}

	// Get all product owners.
	$options = get_option( 'woocommerce_skios_settings' );
	$all_product_owners = skios_get_product_owners();

	// Loop through and find the correct one.
	foreach ( $all_product_owners as $key => $product_owner ) {
		if ( $product_owner['id'] === $id ) {
			// Update properties.
			$all_product_owners[ $key ][ 'label' ] = $args[ 'label' ];
			$all_product_owners[ $key ][ 'type' ] = $args[ 'type' ];
			$all_product_owners[ $key ][ 'identifier' ] = $args[ 'identifier' ];

			// Save in options.
			$options[ 'product_owners' ] = $all_product_owners;
			update_option( 'woocommerce_skios_settings', $options );

			// Return updated product owner.
			return (array) $product_owner;
		}
	}

	// Return WP_Error since we didn't find a product owner
	// with that particular id.
	return new WP_Error( 'none_existing_product_owner', __( 'En produktägare med det ID:et existerar inte.', 'skios' ) );
}

/**
 * Handles order notification to all different product owners.
 * @param  WC_Order $order
 * @param  array    $sorted_items Products
 * @return mixed
 */
function skios_handle_order_notifications( $order, $sorted_items ) {

	if ( is_array($sorted_items) ) {

		$successful_filters = array();

		foreach( $sorted_items as $owner_id => $items ) {

			if ( 0 == $owner_id ) {
				// No owner associated with the id, send to admin.
				skios_no_owner_email( get_bloginfo('admin_email'), $order, $items );
			} else {

				$owner = skios_get_product_owner_by_id( $owner_id );

				/**
				 * Lets other plugin / themes hook in to order notification.
				 *
				 * @since 20170105
				 *
				 * @param boolean  true
				 * @param string   $owner[ 'type' ] The type of product owner.
				 * @param array    $owner           The product owner.
				 * @param WC_Order $order         The WC_Order object.
				 * @param array    $items           The order items that belongs to this product owner.
				 */
				if ( ! is_wp_error( $result = apply_filters( 'skios_order_notification', true, $owner[ 'type' ], $owner, $order, $items ) ) ) {
					$successful_filters[ $owner_id ] = $items;
				} else {
					return $result;
				}
			}

		}

		return ( count( $successful_filters ) === count( $sorted_items ) );

	}

	return false;

}

/**
 * No owner found for these items, send email to default/shop owner.
 */
function skios_no_owner_email( $email_address, $order, $items ) {

	$message = '';

	$message .= "Följande produkter saknar produktägare. \n\n";

	$message .= skios_email_customer_details($order);

	$message .= "Produkter\n-----------------------\n";

	$message .= skios_email_items($order, $items);

	$subject = __( 'Ny order på produkter som saknar ägare', 'skios' );

	wp_mail( $to = $email_address, $subject, $message );

}

function skios_owner_order_email( $email_address, $order, $items ) {

	$message = '';

	$message .= "<h1 style='font-size: 1.5em;'>Beställning</h1>";

	$message .= skios_email_customer_details($order);

	$message .= "<h2 style='font-size: 1.2em;'>Produkter</h2>";

	$message .= skios_email_items($order, $items);

	$subject = __( 'Ny order på era produkter', 'skios' );

  wp_mail( $to = $email_address, $subject, $message, array('Content-Type: text/html; charset=UTF-8') );
}

function skios_email_customer_details($order) {

	$text  = "<h2 style='font-size: 1.2em;'>Beställningsuppgifter</h2>";

	$text .= $order->get_formatted_billing_address();

	$text .= "<h2 style='font-size: 1.2em;'>Leveransadress</h2>";

	$text .= $order->get_formatted_shipping_address();

	return $text;

}

/**
 * Returns a string containing order item information.
 *
 * This includes all metadata such as variation data or gravityforms.
 * @param  WC_Order $order
 * @param  array    $items
 * @return string
 */
function skios_email_items( $order, $items ) {
	$string = '<table>';
		// Add the table headers
		$string .= '<thead>';
			$string .= '<th style="text-align: left;">Antal</th>';
			$string .= '<th style="text-align: left;">Artikelnr</th>';
			$string .= '<th style="text-align: left;">Artikel</th>';
		$string .= '</thead>';

		// Add the products.
		$string .= '<tbody>';
			// Loop through all items.
			foreach ( $items as $item ) {
				$string .= '<tr>';
					$string .= '<td style="min-width: 50px; vertical-align: top;">' . $item[ 'qty' ] . '</td>';
					$string .= '<td style="min-width: 100px; vertical-align: top;">' . get_post_meta( $item[ 'product_id' ], '_sku', true ) . '</td>';

					// Add the name.
					$string .= '<td style="vertical-align: top; padding-bottom: 0.5em;">';
						$string .= $item[ 'name' ];
						$string .= '<br<>';
						$string .= $item->get_product()->get_price_html();
						$string .= '<br>';
							// Add all meta data at the end of the line.
							foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
								$string .= sprintf( '<br><strong>%s</strong>: %s', $meta->key, $meta->value );
							}
							$string .= '<br>';
					$string .= '</td>';
				$string .= '</tr>';
			}
		$string .= '</tbody>';
	$string .= '</table>';

	return $string;
}
