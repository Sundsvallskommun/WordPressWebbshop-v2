<?php
/**
 * SK_SMEX_API
 * ===========
 *
 * Wrapper class for the SMEX webservice API.
 *
 * @since   0.1
 * @package SK_SMEX
 */

class SK_SMEX_API {

	/**
	 * Singleton instance of class.
	 * @var SK_SMEX_API|null
	 */
	private static $instance = null;

	/**
	 * SoapClient instance.
	 * @var SoapClient|null
	 */
	private $soap = null;

	/**
	 * User data from SMEX.
	 * @var StdClass|null
	 */
	private $user_data = null;

	/**
	 * Check if URL to SMEX is defined.
	 */
	private function __construct() {

    $smex_url = get_option('smex_api_url');
		if( !empty($smex_url) ) {
			define('SMEX_URL', $smex_url);
		}

		if ( ! defined( 'SMEX_URL' ) ) {
			throw new Exception( __( 'SMEX_URL is not defined!', 'sk-smex' ) );
		} else {
			$soap_client = $this->get_soap_client();
			if ( is_wp_error( $soap_client ) ) {
				throw new Exception( $soap_client->get_error_message() );
			}
		}
	}

	/**
	 * Returns the singleton instance of the
	 * class.
	 * @return SK_SMEX_API
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Returns the value from a given property.
	 * @param  string $property
	 * @return string
	 */
	public function get_user_data( $property ) {
		// Make sure we have retrieved user data.
		if ( is_null( $this->user_data ) ) {
			if ( wp_get_current_user() !== 0 && ! empty( $username = wp_get_current_user()->user_login ) ) {
				$soap = $this->get_soap_client();
				$result = $soap->GetPortalPersonData( array (
					'loginname'	=> $username,
				) );
				if ( empty( $result->GetPortalPersonDataResult ) ) {
					return new WP_Error( 'user_not_found_in_smex', __( 'Couldn\'t retrieve info about logged in user from SMEX.', 'sk-smex' ) );
				} else {
					$this->user_data = $result->GetPortalPersonDataResult->PortalPersonData;
				}
			} else {
				return new WP_Error( 'user_not_logged_in', __( 'User isn\'t logged in.', 'sk-smex' ) );
			}
		}

		// Return the property by looping through the data.
		foreach ( $this->user_data as $data ) {
			if ( strtolower( $data->Name ) === strtolower( $property ) ) {
				return $data->Value;
			}
		}

		// Return WP_Error if property doesn't exist.
		return new WP_Error( 'property_not_existing', __( "{$property} doesn't exist.", 'sk-smex' ) );
	}

	/**
	 * Returns the current SoapClient instance.
	 * @return SoapClient
	 */
	private function get_soap_client() {
		if ( is_null( $this->soap ) ) {
			try {
				$this->soap = new SoapClient( SMEX_URL . '?singleWsdl', array(
					'trace'			=> true,
					'cache_wsdl'	=> WSDL_CACHE_NONE,
					'soap_version'	=> SOAP_1_1,
				) );
				$this->soap->__setLocation( SMEX_URL . '/basic' );
			} catch ( Exception $e ) {
				return new WP_Error( 'smex_unreachable', __( 'Couldn\'t connect to SMEX.', 'sk-smex' ) );
			}
		}
		return $this->soap;
	}

}
