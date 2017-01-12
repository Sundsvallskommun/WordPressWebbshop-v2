<?php
/**
 * Get an array of available product owners (name and email).
 * @return array
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