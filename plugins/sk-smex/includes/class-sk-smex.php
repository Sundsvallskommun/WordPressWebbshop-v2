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
	 * Inits hooks and class.
	 */
	public function __construct() {
		$this->includes();
		
		// Check if we can successfully connect to SMEX.
		$this->is_smex_active = $this->init_smex();
		if ( $this->is_smex_active ) {
			$this->init_hooks();
		}

		// Add action to make sure that SMEX is accessible before allowing users to checkout.
		// Note: since we want to disable checkout if SMEX isn't active this hook is added
		// outside of init_hooks() where only hooks related to SMEX is added.
		add_action( 'wp', array( $this, 'check_smex_before_checkout' ) );
	}

	/**
	 * Includes all necessary files.
	 * @return void
	 */
	private function includes() {
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
		add_filter( 'woocommerce_checkout_fields', array( $this, 'change_checkout_fields' ), 10 );
		add_filter( 'woocommerce_form_field_args', array( $this, 'set_readonly_checkout_fields' ), 10, 3 );
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'populate_checkout_fields' ), 10, 2 );

		// Change fields on my account.
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'change_my_account_formatted_address' ), 10, 3 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_values_to_my_account_formatted_address' ), 10, 2 );
		add_filter( 'woocommerce_billing_fields', array( $this, 'change_my_account_billing_fields' ) );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'change_my_account_shipping_fields' ) );

		// Filters that will make sure that some fields aren't altered.
		add_filter( 'woocommerce_process_checkout_field_billing_first_name', array( $this, 'check_billing_first_name' ) );
		add_filter( 'woocommerce_process_checkout_field_billing_last_name', array( $this, 'check_billing_last_name' ) );
	}

	/**
	 * Changes checkout fields based on what organization
	 * the user belongs to.
	 *
	 * TODO: Add the proper fields belonging to all organizations
	 * when we have the info.
	 * 
	 * @param  array $fields
	 * @return array
	 */
	public function change_checkout_fields( $fields ) {

		// Change labels.
		$fields[ 'billing' ][ 'billing_company' ][ 'label' ] = __( 'Organisation', 'sk-smex' );
		$fields[ 'shipping' ][ 'shipping_company' ][ 'label' ] = __( 'Organisation', 'sk-smex' );

		/**
		 * Add / remove fields depending on which organization the
		 * user belongs to.
		 *
		 * case 1: Sundsvalls Kommun
		 * case 2: Servicecenter IT
		 */

		switch ( (int) $this->smex_api->get_user_data( 'CompanyId' ) ) {
			// Sundsvalls Kommun.
			case 2:
				$fields[ 'billing' ][ 'billing_reference_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Referensnummer', 'sk-smex' ),
					'class'			=> '',
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> $this->smex_api->get_user_data( 'ReferenceNumber' ),
				);
			break;

			// Servicecenter IT.
			case 1:
				$fields[ 'billing' ][ 'billing_responsibility_number' ] = array(
					'type'				=> 'text',
					'label'				=> __( 'Ansvarsnummer', 'sk-smex' ),
					'class'				=> array(),
					'required'			=> true,
					'clear'				=> true,
					'label_class'		=> '',
					'default'			=> '',
				);
				$fields[ 'billing' ][ 'billing_occupation_number' ] =  array(
					'type'				=> 'text',
					'label'				=> __( 'Verksamhetsnummer', 'sk-smex' ),
					'class'				=> array(),
					'required'			=> true,
					'clear'				=> true,
					'label_class'		=> '',
					'default'			=> '',
				);
				$fields[ 'billing' ][ 'billing_activity_number' ] = array(
					'type'				=> 'text',
					'label'				=> __( 'Aktivitetsnummer', 'sk-smex' ),
					'class'				=> array(),
					'required'			=> false,
					'clear'				=> true,
					'label_class'		=> '',
					'default'			=> '',
				);
				$fields[ 'billing' ][ 'billing_project_number' ] = array(
					'type'						=> 'text',
					'label'						=> __( 'Projektnummer', 'sk-smex' ),
					'class'						=> array(),
					'required'					=> false,
					'clear'						=> true,
					'label_class'				=> '',
					'default'					=> '',
				);
				$fields[ 'billing' ][ 'billing_object_number' ] = array(
					'type'						=> 'text',
					'label'						=> __( 'Objektnummer', 'sk-smex' ),
					'class'						=> array(),
					'required'				=> false,
					'clear'				=> true,
					'label_class'		=> '',
					'default'			=> '',
				);
			break;
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
		if ( $key === 'billing_first_name' || $key === 'billing_last_name' || $key === 'billing_company' ) {
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
			switch ( $input ) {
				case 'billing_first_name':
				case 'shipping_first_name':
					$value = $this->smex_api->get_user_data( 'Givenname' );
				break;
				
				case 'billing_last_name':
				case 'shipping_last_name':
					$value = $this->smex_api->get_user_data( 'Lastname' );
				break;

				case 'billing_company':
				case 'shipping_company':
					$value = $this->smex_api->get_user_data( 'Company' );
				break;

				case 'billing_email':
					$value = $this->smex_api->get_user_data( 'Email' );
				break;

				case 'billing_phone':
					$value = $this->smex_api->get_user_data( 'WorkPhone' );
				break;

				case 'billing_reference_number':
					$value = $this->smex_api->get_user_data( 'ReferenceNumber' );
				break;
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
		$values = array(
			'first_name'		=> get_user_meta( $customer_id, $name . '_first_name', true ),
			'last_name'			=> get_user_meta( $customer_id, $name . '_last_name', true ),
			'company'			=> get_user_meta( $customer_id, $name . '_company', true ),
			'reference_number'	=> $this->smex_api->get_user_data( 'ReferenceNumber' ),
		);

		/**
		 * We'll add additional fields depending on what company
		 * the user belongs to. About the same thing as in checkout.
		 *
		 * case 1: Sundsvalls Kommun
		 * case 2: Servicecenter IT
		 */
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

		return $values;
	}

	/**
	 * Adds our custom values to the formatted address.
	 * @param  array $values
	 * @param  array $args
	 * @return array
	 */
	public function add_values_to_my_account_formatted_address( $values, $args ) {
		$values[ '{reference_number}' ] = $args[ 'reference_number' ];
		$values[ '{reference_number_upper}' ] = $args[ 'reference_number' ];
		$values[ '{responsibility_number}' ] = $args[ 'responsibility_number' ];
		$values[ '{responsibility_number_upper}' ] = $args[ 'responsibility_number' ];
		$values[ '{occupation_number}' ] = $args[ 'occupation_number' ];
		$values[ '{occupation_number_upper}' ] = $args[ 'occupation_number' ];
		$values[ '{activity_number}' ] = $args[ 'activity_number' ];
		$values[ '{activity_number_upper}' ] = $args[ 'activity_number' ];
		$values[ '{project_number}' ] = $args[ 'project_number' ];
		$values[ '{project_number_upper}' ] = $args[ 'project_number' ];
		$values[ '{object_number}' ] = $args[ 'object_number' ];
		$values[ '{object_number_upper}' ] = $args[ 'object_number' ];
		var_dump( $values );
		return $values;
	}

	/**
	 * Changes the fields on my account.
	 * @param  array $fields
	 * @return array
	 */
	public function change_my_account_billing_fields( $fields ) {
		// Change labels.
		$fields[ 'billing_company' ][ 'label' ] = __( 'Organisation', 'sk-smex' );

		/**
		 * Add / remove fields depending on which organization the
		 * user belongs to.
		 *
		 * case 1: Sundsvalls Kommun
		 * case 2: Servicecenter IT
		 */

		switch ( (int) $this->smex_api->get_user_data( 'CompanyId' ) ) {
			// Sundsvalls Kommun.
			case 2:
				$fields[ 'billing_reference_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Referensnummer', 'sk-smex' ),
					'class'			=> '',
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> $this->smex_api->get_user_data( 'ReferenceNumber' ),
				);
			break;

			// Servicecenter IT.
			case 1:
				$fields[ 'billing_responsibility_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Ansvarsnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
				$fields[ 'billing_occupation_number' ] =  array(
					'type'			=> 'text',
					'label'			=> __( 'Verksamhetsnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
				$fields[ 'billing_activity_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Aktivitetsnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> false,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
				$fields[ 'billing_project_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Projektnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> false,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
				$fields[ 'billing_object_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Objektnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> false,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
			break;
		}

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

		/**
		 * Add / remove fields depending on which organization the
		 * user belongs to.
		 *
		 * case 1: Sundsvalls Kommun
		 * case 2: Servicecenter IT
		 */

		switch ( (int) $this->smex_api->get_user_data( 'CompanyId' ) ) {
			// Sundsvalls Kommun.
			case 2:
				$fields[ 'shipping_reference_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Referensnummer', 'sk-smex' ),
					'class'			=> '',
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> $this->smex_api->get_user_data( 'ReferenceNumber' ),
				);
			break;

			// Servicecenter IT.
			case 1:
				$fields[ 'shipping_responsibility_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Ansvarsnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
				$fields[ 'shipping_occupation_number' ] =  array(
					'type'			=> 'text',
					'label'			=> __( 'Verksamhetsnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
				$fields[ 'shipping_activity_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Aktivitetsnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> false,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
				$fields[ 'shipping_project_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Projektnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> false,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
				$fields[ 'shipping_object_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Objektnummer', 'sk-smex' ),
					'class'			=> array(),
					'required'		=> false,
					'clear'			=> true,
					'label_class'	=> '',
					'default'		=> '',
				);
			break;
		}

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

}