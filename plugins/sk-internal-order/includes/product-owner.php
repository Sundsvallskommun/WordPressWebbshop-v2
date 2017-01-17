<?php
/**
 * Get an array of available product owners (name and email).
 * @return array
 */
function skios_get_product_owners() {

	$options = get_option( 'woocommerce_skios_settings', false );

	return isset( $options['product_owners'] ) ? $options['product_owners'] : array();

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

	if(!$id) return false;

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
		'label'	=> '',
		'email'	=> '',
	);
	$args = wp_parse_args( $args, $defaults );

	if ( '' === trim( $args[ 'label' ] ) ) {
		return new WP_Error( 'empty_product_owner_name', __( 'Produktägare får inte ha en tom benämning.', 'skios' ) );
	}

	if ( '' === trim( $args[ 'email' ] ) ) {
		return new WP_Error( 'empty_product_owner_email', __( 'Produktägare får inte ha en tom e-postadress.', 'skios' ) );
	}

	// Get the current id from options.
	$options = get_option( 'woocommerce_skios_settings' );
	$id = $options[ 'product_owner_count' ];

	// Get all product_owners.
	$all_product_owners = skios_get_product_owners();

	// Insert the new one.
	$all_product_owners[] = ( $new_product_owner = array(
		'id'	=> $id,
		'label'	=> $args[ 'label' ],
		'email'	=> $args[ 'email' ],
	) );

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
		'label'	=> '',
		'email'	=> '',
	);
	$args = wp_parse_args( $args, $defaults );

	if ( '' === trim( $args[ 'label' ] ) ) {
		return new WP_Error( 'empty_product_owner_name', __( 'Produktägare får inte ha en tom benämning.', 'skios' ) );
	}

	if ( '' === trim( $args[ 'email' ] ) ) {
		return new WP_Error( 'empty_product_owner_email', __( 'Produktägare får inte ha en tom e-postadress.', 'skios' ) );
	}

	// Get all product owners.
	$all_product_owners = skios_get_product_owners();

	// Loop through and find the correct one.
	foreach ( $all_product_owners as (object) $product_owner ) {
		if ( $product_owner->id === $id ) {
			// Update properties.
			$product_owner->label = $args[ 'label' ];
			$product_owner->email = $args[ 'email' ];

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

			$owner = skios_get_product_owner_by_id( $owner_id );

			if ( !$owner ) continue;

			$owner_email = $owner['email'];


			skios_admin_new_order_email( $owner_email, $order, $items );

		}

	}
}

function skios_admin_new_order_email( $email_address, $order, $items ) {

	ob_start();

	$email = '';

	$email .= "\n\n";
	$email .= $email_address;
	$email .= "\n\n";

	$email .= "Ny order \n\n";

	$email .= "Faktureringsinformation\n-----------------------\n";

	$email .= "$order->billing_first_name $order->billing_last_name \n";
	$email .= "$order->billing_email \n";
	$email .= "$order->billing_phone \n";

	$email .= "\n";

	$email .= "Leveransinformation\n-----------------------\n";

	$email .= "$order->shipping_first_name $order->shipping_last_name \n";
	$email .= "$order->shipping_email \n";
	$email .= "$order->shipping_phone \n";

	$email .= "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	$email .= "Produkter: \n\n";

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

	$order_items = ob_get_clean();

	$email .= $order_items;

	$email .= "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	// Send email here
	error_log( var_export( $email, true ) );
}

