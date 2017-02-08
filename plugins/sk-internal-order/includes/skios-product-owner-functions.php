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
				 * @param string   $owner[ 'type' ] The type of product owner.
				 * @param array    $owner           The product owner.
				 * @param WC_Order $order         The WC_Order object.
				 * @param items    $items           The order items that belongs to this product owner.
				 */
				do_action( 'skios_order_notification', $owner[ 'type' ], $owner, $order, $items );
			}

		}

	}
}

/**
 * No owner found for these items, send email to default/shop owner.
 */
function skios_no_owner_email( $email_address, $order, $items ) {

	$message = '';

	$message .= "Följande produkter saknar produktägare. \n\n";

	$message .= skios_email_customer_details($order);

	$message .= "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	$message .= "Produkter";

	$message .= skios_email_items($order, $items);

	$message .= "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	$subject = __( 'Ny order på produkter som saknar ägare', 'skios' );

	wp_mail( $to = $email_address, $subject, $message );

}

function skios_owner_order_email( $email_address, $order, $items ) {

	$message = '';

	$message .= "Ny order på produkter du äger \n\n";

	$message .= skios_email_customer_details($order);

	$message .= "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	$message .= "Produkter";

	$message .= skios_email_items($order, $items);

	$message .= "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	$subject = __( 'Ny order på era produkter', 'skios' );

	wp_mail( $to = $email_address, $subject, $message );
}

function skios_email_customer_details($order) {

	$text  = "Faktureringsinformation\n-----------------------\n";

	$text .= "$order->billing_first_name $order->billing_last_name \n";
	$text .= "$order->billing_email \n";
	$text .= "$order->billing_phone \n";

	$text .= "\n";

	$text .= "Leveransinformation\n-----------------------\n";

	$text .= "$order->shipping_first_name $order->shipping_last_name \n";
	$text .= "$order->shipping_email \n";
	$text .= "$order->shipping_phone \n";

	return $text;

}

function skios_email_items($order, $items) {

	ob_start();

	$args = array(
			'show_sku'      => false,
			'show_image'    => false,
			'image_size'    => array( 32, 32 ),
			'plain_text'    => true,
			'sent_to_admin' => false,
		);

	$template = $args['plain_text'] ? 'emails/plain/email-order-items.php' : 'emails/email-order-items.php';

	wc_get_template( $template, array(
			'order'               => $order,
			'items'               => $items,
			'show_download_links' => $order->is_download_permitted(),
			'show_sku'            => $args['show_sku'],
			'show_purchase_note'  => $order->is_paid(),
			'show_image'          => $args['show_image'],
			'image_size'          => $args['image_size'],
			'plain_text'          => $args['plain_text'],
			'sent_to_admin'       => $args['sent_to_admin'],
		) );

	return $order_items = ob_get_clean();

}