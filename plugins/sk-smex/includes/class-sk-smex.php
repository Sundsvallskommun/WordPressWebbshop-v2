<?php
/**
 * SK_SMEX
 * =======
 *
 * Main plugin file.
 *
 * Register hooks and filters.
 *
 * @since   0.1
 * @package SK_SMEX
 */

class SK_SMEX {

	/**
	 * Instance of SMEX_API.
	 * @var SMEX_API
	 */
	private $smex_api;

	/**
	 * Checks if SMEX is active or not.
	 * @var boolean
	 */
	private $is_smex_active = false;

	/**
	 * All billing fields additional fields.
	 * @var array
	 */
	private $ADDITIONAL_BILLING_FIELDS = array();

	/**
	 * Product owners that require additional fields.
	 * @var array
	 */
	private $PRODUCT_OWNER_W_ADDITIONAL_FIELDS = array(
		'Servicecenter IT'
	);

	/**
	 * Field names pairing between gravity forms dynamic field population and
	 * SMEX api field.
	 *
	 * Key = Gravity Forms parameter value
	 * Value = SMEX field name
	 *
	 * @var array
	 */
	private $GF_DYNAMIC_FIELDS = array(
		'smex_firstname' => 'Givenname',
		'smex_lastname' => 'Lastname',
		'smex_company' => 'Company',
		'smex_email' => 'Email',
		'smex_phone' => 'WorkPhone',
		'smex_reference_number' => 'ReferenceNumber'
	);

	/**
	 * Inits hooks and class.
	 */
	public function __construct() {
		// Setup billing fields.
		$this->setup_billing_fields();

		// Include all files.
		$this->includes();
		
		// Check if we can successfully connect to SMEX.
		$this->is_smex_active = $this->init_smex();
		if ( $this->is_smex_active && is_user_logged_in() ) {
			$this->init_hooks();
		}

		// Add action to make sure that SMEX is accessible before allowing users to checkout.
		// Note: since we want to disable checkout if SMEX isn't active this hook is added
		// outside of init_hooks() where only hooks related to SMEX is added.
		add_action( 'wp', array( $this, 'check_smex_before_checkout' ) );
	}

	/**
	 * Since PHP doesn't allow evalution when setting class
	 * properties we'll do it here.
	 * @return void
	 */
	private function setup_billing_fields() {
		$this->ADDITIONAL_BILLING_FIELDS = array(	
			'billing_reference_number'		=> __( 'Referensnummer', 'sk-smex' ),
			'billing_responsibility_number'	=> __( 'Ansvarsnummer', 'sk-smex' ),
			'billing_occupation_number'		=> __( 'Verksamhetsnummer', 'sk-smex' ),
			'billing_activity_number'		=> __( 'Aktivitetsnummer', 'sk-smex' ),
			'billing_object_number'			=> __( 'Objektnummer', 'sk-smex' ),
		);
	}

	/**
	 * Includes all necessary files.
	 * @return void
	 */
	private function includes() {
		// Make sure pluggable is loaded.
		include_once ABSPATH . 'wp-includes/pluggable.php';

		// Include SMEX_API class file.
		include __DIR__ . '/class-sk-smex-api.php';
	}

	/**
	 * Inits smex.
	 * @return boolean
	 */
	private function init_smex() {
		try {
			$this->smex_api = SK_SMEX_API::get_instance();
			
			return true;
		} catch ( Exception $e ) {
			error_log( __( 'Couldn\'t connect to SMEX.', 'sk-smex' ) );
			return false;
		}
	}

	/**
	 * Initiates all action and filter hooks.
	 * @return void
	 */
	private function init_hooks() {
		// Filters needed to change fields on checkout, thank you and my account.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'change_checkout_fields' ), 10 );
		add_filter( 'woocommerce_form_field_args', array( $this, 'set_readonly_checkout_fields' ), 10, 3 );
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'populate_checkout_fields' ), 10, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'change_my_account_formatted_address' ), 10, 3 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'add_additional_fields_to_billing_args' ), 10, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'add_addtional_fields_to_localisation_format' ) );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_values_to_my_account_formatted_address' ), 10, 2 );
		add_filter( 'woocommerce_billing_fields', array( $this, 'change_my_account_billing_fields' ) );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'change_my_account_shipping_fields' ) );

		// Filters that will make sure that some fields aren't altered.
		add_filter( 'woocommerce_process_checkout_field_billing_first_name', array( $this, 'check_billing_first_name' ) );
		add_filter( 'woocommerce_process_checkout_field_billing_last_name', array( $this, 'check_billing_last_name' ) );

		// Add an action that deletes our billing fields for privacy orders.
		add_action( 'sk_privacy_after_data_clear', array( $this, 'delete_data_on_privacy_orders' ) );

		// Add a filter for each dynamic field to populate them.
		foreach ( $this->GF_DYNAMIC_FIELDS as $field => $smexvalue ) {
			add_filter( "gform_field_value_$field", array( $this, 'auto_populate_gravity_forms' ), 10, 3 );
		}
	}

	/**
	 * Changes checkout fields based on what organization
	 * the user belongs to.
	 * @param  array $fields
	 * @return array
	 */
	public function change_checkout_fields( $fields ) {
		// Check if any of the cart items belongs to any of the
		// companies that are required to enter additional details.
		$found = false;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			// Get the product owner id from the product.
			$product_owner_id = get_post_meta( $cart_item[ 'product_id' ], '_product_owner', true );
			if ( $product_owner_id ) {
				// Get the product owner data.
				$product_owner = skios_get_product_owner_by_id( $product_owner_id );

				// Check if product owner is any of the
				// companies we're looking for.
				if ( in_array( $product_owner[ 'label' ], $this->PRODUCT_OWNER_W_ADDITIONAL_FIELDS ) ) {
					$found = true;
				}
			}
		}

		// Add certain fields if the user belongs to some certain faculties
		// and if they have a product in the cart that belongs to some specified
		// product owners.
		if ( $found && $this->smex_api->user_requires_additional_fields() ) {
			$fields[ 'billing' ][ 'billing_responsibility_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Ansvarsnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
					'priority'		=> 140,
				);
				$fields[ 'billing' ][ 'billing_occupation_number' ] =  array(
					'type'			=> 'text',
					'label'			=> __( 'Verksamhetsnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
					'priority'		=> 140,
				);
				$fields[ 'billing' ][ 'billing_activity_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Aktivitetsnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> false,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
					'priority'		=> 140,
				);
				$fields[ 'billing' ][ 'billing_project_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Projektnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> false,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
					'priority'		=> 140,
				);
				$fields[ 'billing' ][ 'billing_object_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Objektnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> false,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
					'priority'		=> 140,
				);
		}

		return $fields;
	}

	/**
	 * Modifies some of the checkout fields to be readonly.
	 * @param  array $args
	 * @param  string $key
	 * @param  string $value
	 * @return array
	 */
	public function set_readonly_checkout_fields( $args, $key, $value ) {
		if ( $key === 'billing_first_name' || $key === 'billing_last_name' || $key === 'billing_email' ) {
			$args[ 'custom_attributes' ][ 'readonly' ] = 'readonly';
		}
		return $args;
	}

	/**
	 * Sets the value of some of the fields in checkout.
	 * @param  string $value
	 * @param  string $input
	 * @return string
	 */
	public function populate_checkout_fields( $value, $input ) {
		// Only modify checkout fields.
		if ( is_checkout() ) {
			$new_value = '';
			switch ( $input ) {
				case 'billing_first_name':
				case 'shipping_first_name':
					$new_value = $this->smex_api->get_user_data( 'Givenname' );
				break;
				
				case 'billing_last_name':
				case 'shipping_last_name':
					$new_value = $this->smex_api->get_user_data( 'Lastname' );
				break;

				case 'billing_company':
				case 'shipping_company':
					$new_value = $this->smex_api->get_user_organisation();
				break;

				case 'billing_email':
					$new_value = $this->smex_api->get_user_data( 'Email' );
				break;

				case 'billing_phone':
					$new_value = $this->smex_api->get_user_data( 'WorkPhone' );
				break;

				case 'billing_reference_number':
					$new_value = $this->smex_api->get_user_data( 'ReferenceNumber' );
				break;
			}

			// Make sure that we have a new value.
			if ( ! is_wp_error( $new_value ) ) {
				$value = $new_value;
			}
		}
		return $value;
	}

	/**
	 * Changes the fields used in the formatted address.
	 * @param  array   $values
	 * @param  integer $customer_id
	 * @param  string  $name
	 * @return array
	 */
	public function change_my_account_formatted_address( $values, $customer_id, $name ) {
		// Try to get the reference number from SMEX.
		$reference_number = ( ! is_wp_error( $this->smex_api->get_user_data( 'ReferenceNumber' ) ) ) ?
			$this->smex_api->get_user_data( 'ReferenceNumber' ) : '';

		$values = array(
			'first_name'		=> get_user_meta( $customer_id, $name . '_first_name', true ),
			'last_name'			=> get_user_meta( $customer_id, $name . '_last_name', true ),
			'company'			=> get_user_meta( $customer_id, $name . '_company', true ),
			'reference_number'	=> $reference_number,
		);

		/**
		 * We'll add additional fields depending on what company
		 * the user belongs to. About the same thing as in checkout.
		 *
		 * case 1: Sundsvalls Kommun
		 * case 2: Servicecenter IT
		 */
		if ( ! is_wp_error( $this->smex_api->get_user_data( 'CompanyId' ) ) ) {
			switch ( (int) $this->smex_api->get_user_data( 'CompanyId' ) ) {
				// Sundsvalls Kommun.
				case 2:
				break;

				// Servicecenter IT.
				case 1:
					$values[ 'responsibility_number' ] = get_user_meta( $customer_id, $name . '_responsibility_number', true );
					$values[ 'occupation_number' ] =  get_user_meta( $customer_id, $name . '_occupation_number', true );
					$values[ 'activity_number' ] = get_user_meta( $customer_id, $name . '_activity_number', true );
					$values[ 'project_number' ] = get_user_meta( $customer_id, $name . '_project_number', true );
					$values[ 'object_number' ] = get_user_meta( $customer_id, $name . '_object_number', true );
				break;
			}
		}

		return $values;
	}

	/**
	 * Adds our additional fields and values to the billing array.
	 * @param  array    $billing
	 * @param  WC_Order $order
	 * @return void
	 */
	public function add_additional_fields_to_billing_args( $billing, $order ) {
		$original_count = count( $billing );

		foreach ( $order->get_meta_data() as $meta ) {
			// All metakeys are saved hidden. Eg. '_meta_key'.
			if ( in_array( substr( $meta->key, 1 ), array_keys( $this->ADDITIONAL_BILLING_FIELDS ) ) ) {
				// Add it to billing. Note: the meta key contains
				// '_billing_' so we'll substr to avoid that.
				$billing[ substr( $meta->key, 9 ) ] = $meta->value;
			}
		}

		// Change the country arg depending on how many
		// we added.
		
		// Check if we added all.
		if ( ( $original_count + count( $this->ADDITIONAL_BILLING_FIELDS ) ) === count( $billing ) ) {
			$billing[ 'country' ] = $billing[ 'country' ] . '_ALL_ADDITIONAL';
		} else if ( $original_count + 1 === count( $billing ) ) {
			$billing[ 'country' ] = $billing[ 'country' ] . '_REF_ONLY';
		}

		return $billing;
	}

	/**
	 * Adds our custom billing fields to all localisation formats.
	 * @param  array $formats
	 * @return array
	 */
	public function add_addtional_fields_to_localisation_format( $formats ) {
		foreach ( $formats as $key => $format ) {
			// Add the format for reference number only.
			$formats[ $key . '_REF_ONLY' ] = $formats[ $key ] . "Referensnummer: {reference_number}\n";

			// Get the string that we'll build on.
			$format_string = $format;

			// Add each field to the format string.
			foreach ( $this->ADDITIONAL_BILLING_FIELDS as $billing_field => $label ) {
				$format_string .= "\n{$label}: {" . substr( $billing_field, 8 ) . "}";
			}

			// Add the string to the array.
			$formats[ $key . '_ALL_ADDITIONAL' ] = $format_string;
		}
		return $formats;
	}

	/**
	 * Adds our custom values to the formatted address.
	 * @param  array $values
	 * @param  array $args
	 * @return array
	 */
	public function add_values_to_my_account_formatted_address( $values, $args ) {
		// We might have modified the country argument for a small hack
		// and we need to change that back here.
		if ( ! empty( $values[ '{country}' ] ) ) {
			// Instanciate WC_Countries to get a list
			// of all countries.
			$wc_countries = new WC_Countries();

			// Get the correct country value.
			$correct_country = substr( $values[ '{country}' ], 0, 2 );
			
			// Get the full country name.
			$full_country = ( isset( $wc_countries->countries[ $correct_country ] ) ) ? $wc_countries->countries[ $correct_country ] : $correct_country;

			// Change the value.
			$values[ '{country}' ] = "{$full_country}\n";
		}

		// Loop through all our custom billing fields.
		foreach ( array_keys( $this->ADDITIONAL_BILLING_FIELDS ) as $billing_field ) {
			// Check if this billing field exists.
			if ( ! empty( $args[ substr( $billing_field, 8 ) ] ) ) {
				// Add it's value.
				$values[ '{' . substr( $billing_field, 8 ) . '}' ] = $args[ substr( $billing_field, 8 ) ]; 

				// Add it's uppercase value (don't know if this is necessary).
				$values[ '{' . substr( $billing_field, 8 ) . '_upper}' ] = $args[ substr( $billing_field, 8 ) ]; 
			}
		}
		return $values;
	}

	/**
	 * Changes the fields on my account.
	 * @param  array $fields
	 * @return array
	 */
	public function change_my_account_billing_fields( $fields ) {
		// Label for company is either "bolag" or "förvaltning".
		// Only change if we have access to SMEX.
		if  ( $this->is_smex_active ) {
			$label = ( $this->smex_api->user_requires_additional_fields() ) ? __( 'Förvaltning', 'sk-smex' ) : __( 'Bolag', 'sk-smex' );

			// Change labels.
			$fields[ 'billing_company' ][ 'label' ] = $label;
		}

		// Try to get the reference number from SMEX.
		$reference_number = ( ! is_wp_error( $this->smex_api->get_user_data( 'ReferenceNumber' ) ) ) ?
			$this->smex_api->get_user_data( 'ReferenceNumber' ) : '';

		// Always add reference number.
		$fields[ 'billing_reference_number' ] = array(
			'type'			=> 'text',
			'label'			=> __( 'Referensnummer', 'sk-smex' ),
			'class'			=> array(),
			'required'		=> true,
			'clear'			=> true,
			'label_class'	=> '',
			'default'		=> $reference_number,
			'priority'		=> 135,
		);

		return $fields;
	}

	/**
	 * Changes the fields on my account.
	 * @param  array $fields
	 * @return array
	 */
	public function change_my_account_shipping_fields( $fields ) {
		// Change labels.
		$fields[ 'shipping_company' ][ 'label' ] = __( 'Organisation', 'sk-smex' );

		// Return.
		return $fields;
	}

	/**
	 * Makes sure that the billing first name is set to
	 * the current users first name.
	 * @param  string $value
	 * @return string
	 */
	public function check_billing_first_name( $value ) {
		return $this->smex_api->get_user_data( 'Givenname' );
	}

	/**
	 * Makes sure that the billing last name is set to
	 * the current users last name.
	 * @param  string $value
	 * @return string
	 */
	public function check_billing_last_name( $value ) {
		return $this->smex_api->get_user_data( 'Lastname' );
	}

	/**
	 * Deletes our custom billing fields on privacy orders.
	 * @param  WC_Order $order
	 * @return void
	 */
	public function delete_data_on_privacy_orders( $order ) {
		foreach ( $this->ADDITIONAL_BILLING_FIELDS as $key => $label ) {
			delete_post_meta( $order->get_id(), substr( $key, 1 ) );
		}
	}

	/**
	 * Redirects users from the checkout with an error message if
	 * they are trying to access the checkout but SMEX is
	 * unavailable.
	 * @return void
	 */
	public function check_smex_before_checkout() {
		if ( is_checkout() && ! $this->is_smex_active ) {
			wc_add_notice( __( 'Vi kan inte slutföra din order nu eftersom det verkar vara något problem med uppkopplingen mot Metakatalogen.', 'sk-smex' ), 'error' );
			wp_redirect( wc_get_cart_url() );
			exit;
		}
	}

	public function auto_populate_gravity_forms( $value, $field, $name ) {
		$smexvalue = $this->GF_DYNAMIC_FIELDS[$name];
		$dyn_value = $this->smex_api->get_user_data( $smexvalue );

		if ( is_wp_error( $dyn_value ) ) {
			return $value;
		}

		return $dyn_value;
	}

}
