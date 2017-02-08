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
		$this->init_hooks();
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
		$this->smex_api = SK_SMEX_API::get_instance();
	}

	/**
	 * Initiates all action and filter hooks.
	 * @return void
	 */
	private function init_hooks() {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'change_checkout_fields' ) );
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
		switch ( (int) $this->smex_api->get_user_data( 'CompanyId' ) ) {
			case 1:
				$fields[ 'billing' ][ 'billing_reference_number' ] = array(
					'type'			=> 'text',
					'label'			=> __( 'Referensnummer', 'sk-smex' ),
					'class'			=> '',
					'required'		=> true,
					'clear'			=> true,
					'label_class'	=> '',
				);
			break;
		}
		return $fields;
	}

}