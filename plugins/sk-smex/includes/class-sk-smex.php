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
	 * Inits hooks and class.
	 */
	public function __construct() {
		$this->includes();
		$this->init_classes();
	}

	/**
	 * Includes all necessary files.
	 * @return void
	 */
	private function includes() {
		include __DIR__ . '/class-sk-smex-api.php';
	}

	/**
	 * Instanciates all necessary classes.
	 * @return void
	 */
	private function init_classes() {
		try {
			$this->smex_api = SK_SMEX_API::get_instance();
			$this->init_hooks();
		} catch ( Exception $e ) {
			error_log( $e );
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
		// Only modify on checkout.
		if ( is_checkout() ) {
			if ( $key === 'billing_first_name' || $key === 'billing_last_name' ) {
				$args[ 'custom_attributes' ][ 'readonly' ] = 'readonly';
			}
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

}