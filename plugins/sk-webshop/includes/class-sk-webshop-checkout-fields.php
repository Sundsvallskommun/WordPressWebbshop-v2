<?php
/**
 * SK_Webshop_Checkout_Fields
 * ==========================
 *
 * Manipulates the checkout and checkout fields.
 *
 * Register hooks and filters.
 *
 * @since   0.1
 * @package SK_Webshop
 */

class SK_Webshop_Checkout_Fields {

	/**
	 * Filters and hooks.
	 */
	public function __construct() {
		// Change fields.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'change_order_of_fields' ), 15 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'change_required' ) );
		add_filter( 'woocommerce_billing_fields', array( $this, 'change_billing_fields' ), 90 );

		// Add some css for the footer.
		add_action( 'wp_footer', array( $this, 'inject_styles' ) );

		// Add some script for the footer.
		add_action( 'wp_footer', array( $this, 'inject_scripts' ) );
	}

	/**
	 * Changes the order.
	 * @param  array $fields
	 * @return array
	 */
	public function change_order_of_fields( $fields ) {
		$fields[ 'billing' ][ 'billing_first_name' ][ 'priority' ] = 5;
		$fields[ 'billing' ][ 'billing_last_name' ][ 'priority' ] = 5;
		$fields[ 'billing' ][ 'billing_phone' ][ 'priority' ] = 15;
    $fields[ 'billing' ][ 'billing_email' ][ 'priority' ] = 16;

		$fields[ 'billing' ][ 'billing_organization' ][ 'priority' ] = 45;
		$fields[ 'billing' ][ 'billing_department' ][ 'priority' ] = 46;

		return $fields;
	}

	/**
	 * Removes requires from certain fields.
	 * @param  array $fields
	 * @return array
	 */
	public function change_required( $fields ) {
		// Remove required from postcode and city.
		$fields[ 'postcode' ][ 'required' ]	= false;
		$fields[ 'city' ][ 'required' ]		= false;

		// Return new fields.
		return $fields;
	}

	/**
	 * Changes the address fields to match the checkout fields.
	 * For more info, see method add_fields().
	 *
	 * Note: this is mostly used for the my-account/addresses.
	 * @param  array $fields
	 * @return array
	 */
	public function change_billing_fields( $fields ) {
		// Add organization.
		$fields['billing_organization'] = $fields['billing_address_2'];
		$fields['billing_organization'][ 'label' ] = __( 'Förvaltning/bolag', 'sk-smex' );
		$fields['billing_organization'][ 'autocomplete' ] = '';
		$fields['billing_organization'][ 'placeholder' ] = '';
		$fields['billing_organization'][ 'required' ] = true;

		// Add department.
		$fields['billing_department'] = $fields[ 'billing_address_2' ];
		$fields['billing_department']['label'] = __( 'Arbetsplats, avdelning, rum' );
		$fields['billing_department']['autocomplete'] = '';
		$fields['billing_department']['placeholder'] = '';
		$fields['billing_department']['required'] = true;

		// Change priority of address_1.
		$fields['billing_address_1']['priority'] = 55;

		// Remove address_2.
		unset( $fields['billing_address_2'] );

		// Return new fields.
		return $fields;
	}

	/**
	 * Injects our styles.
	 * @return void
	 */
	public function inject_styles() { ?>
		<style>
			#billing_country_field {
				display: none;
			}
		</style>
	<?php
	}

	/**
	 * Injects our scripts.
	 * @return void
	 */
	public function inject_scripts() { ?>
		<script type="text/javascript">
			var timer = null;

			jQuery( document.body ).on( 'country_to_state_changing', function() {
				var $ = jQuery;
				var numOfExpectedTitles = 0;

				var $shipping_title = $( '<h3 data-added="true">Leveransadress</h3>' );
				var shipping_fields = [
					'billing_organization',
					'billing_department',
					'billing_address_1',
					'billing_postcode',
					'billing_city',
				];
				var $the_shipping_field = '';

				var $billing_title = $( '<h3 data-added="true">Faktureringsuppgifter</h3>' );
				var billing_fields = [
					'billing_reference_number',
					'billing_responsibility_number',
					'billing_occupation_number',
					'billing_activity_number',
					'billing_project_number',
					'billing_object_number',
				];
				var $the_billing_field = '';

				for (var i = 0; i <= shipping_fields.length; i++) {
					if ( $( '#' + shipping_fields[i] ).length > 0 ) {
						numOfExpectedTitles++;
						$the_shipping_field = $( '#' + shipping_fields[i] );
						break;
					}
				}

				for (var i = 0; i <= billing_fields.length; i++) {
					if ( $( '#' + billing_fields[i] ).length > 0 ) {
						console.log( 'dsadsds' );
						numOfExpectedTitles++;
						$the_billing_field = $( '#' + billing_fields[i] );
						break;
					}
				}

				console.log( $( 'h3[data-added=true]' ).length <= numOfExpectedTitles );

				// Check if we have already added the header.
				if ( $( 'h3[data-added=true]' ).length <= numOfExpectedTitles ) {
					clearTimeout( timer );
				}

				timer = setTimeout( function() {
					if ( $the_shipping_field.length > 0 ) {
						$the_shipping_field.parent().before( $shipping_title );
					}

					if ( $the_billing_field.length > 0 ) {
						$the_billing_field.parent().before( $billing_title );
					}
				}, 100 );
			} );
		</script>
	<?php
	}

}
