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
 */
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
 * Send email with order data to product owners. Only include the products that
 * belong to the respective owner in each email.
 */
function skios_handle_order_notifications( $order, $sorted_items ) {
	return false;
}
