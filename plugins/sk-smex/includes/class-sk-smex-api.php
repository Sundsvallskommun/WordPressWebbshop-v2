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
		$smex_url = get_option( 'smex_api_url' );
		if( ! empty( $smex_url ) && ! defined( 'SMEX_URL' ) ) {
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
	 * Checks if user is required to enter additional fields.
	 * @return boolean
	 */
	public function user_requires_additional_fields() {
		// First check company id to make sure user
		// belongs to Sundsvalls Kommun.
		if ( (int) $this->get_user_data( 'CompanyId' ) !== 1 ) {
			return false;
		} else {
			/**
			 * User belongs to Sundsvalls Kommun.
			 *
			 * Next up we need to look at the organisation tree.
			 *
			 * In order for the user to have to enter the additional
			 * fields they need to be under parent OrgId 13 and also
			 * to be part of either child OrgId 7 or 32.
			 *
			 * So we'll loop through the organisation tree to
			 * look for them.
			 */
			
			$found_parent = false;
			$found_child  = false;
			
			foreach ( explode( '¤', $this->get_user_data( 'OrgTree' ) ) as $level ) {
				list( $level, $org_id, $name ) = explode( '|', $level );
				if ( (int) $org_id === 13 ) {
					$found_parent = true;
				} else if ( in_array( (int) $org_id, array(
					7, 32
				) ) ) {
					$found_child = true;
				}
			}

			return ! ( $found_parent && $found_child );
		}
	}

	/**
	 * Returns the current SoapClient instance.
	 * @return SoapClient
	 */
	private function get_soap_client() {
		if ( is_null( $this->soap ) ) {
			try {
				@$this->soap = new SoapClient( SMEX_URL . '?singleWsdl', array(
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
