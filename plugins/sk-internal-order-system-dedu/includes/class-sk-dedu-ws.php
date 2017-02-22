<?php
/**
 * SK_DeDU_WS
 * ==========
 *
 * Wrapper class for sending order data to the DeDU WebService.
 *
 * @since   0.1
 * @package SK_DeDU
 */

class Sk_DeDU_WS {

	/**
	 * Given username.
	 * @var string
	 */
	private $WS_USERNAME;

	/**
	 * Given password.
	 * @var string
	 */
	private $WS_PASSWORD;

	/**
	 * The URL for the login endpoint of WebService.
	 * @var string
	 */
	private static $WS_LOGIN_URL = 'https://dedu.se/DeDUservicetest/Login?%s';

	/**
	 * The URL for the endpoint for creating tasks.
	 * @var string
	 */
	private static $WS_CREATE_TASK_URL = 'https://dedu.se/DeDUservicetest/TemplatedXML?TemplateName=Sundsvall_CreateWebShopTaskFromList&SessionKey=%s';

	/**
	 * Authenticates with the WebService on construct.
	 */
	public function __construct( $username, $password ) {
		$this->WS_USERNAME = $username;
		$this->WS_PASSWORD = $password;

		// Try to authenticate.
		if ( $session_key = $this->authenticate() ) {
			$this->WS_SESSION_KEY = $session_key;
		} else {
			// Throw an exception for invalid credentials.
			throw new Exception( 'The credentials you provided seems to be wrong.' );
		}
	}

	/**
	 * Sends an order to the DeDU WebService.
	 * @param  WC_Order $order
	 * @param  array    $order_items Array of products
	 * @return
	 */
	public function send_order( WC_Order $order, $order_items ) {
		// Init cURL.
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, sprintf( self::$WS_CREATE_TASK_URL, $this->WS_SESSION_KEY ) );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, true );

		// Get the XML.
		$dedu_xml = new SK_DeDU_XML( $order, $order_items );
		$xml = $dedu_xml->generate_xml();

		// Make sure all is fine.
		if ( ! is_wp_error( $xml ) ) {
			// Set data.
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $xml );

			// Execute request.
			$data = curl_exec( $ch );

			// Check if we had any errors and if the HTTP status code was 201.
			if ( ! curl_errno( $ch ) && curl_getinfo( $ch, CURLINFO_HTTP_CODE ) === 201 ) {
				return true;
			} else {
				// Otherwise return a WP_Error with the ErrorDescription header
				// as the message.
				
				// Get headers.
				$headers = SKW()->get_headers_from_curl( $data );

				// Make sure 'ErrorDescription' is set.
				if ( ! empty( $error_description = $headers[ 'ErrorDescription' ] ) ) {
					return new WP_Error( 'webservice_error', $error_description );
				} else {
					return new WP_Error( 'unknown_error', __( 'Something unexpected went wrong when trying to send order to DeDU.', 'sk-dedu' ) );
				}
			}
		} else {
			return $xml;
		}
	}

	/**
	 * Authenticates with WebService.
	 * @return string|boolean Session key on success and false on failure
	 */
	private function authenticate() {
		// Use cURL.
		$ch = curl_init();

		// Set URL.
		curl_setopt( $ch, CURLOPT_URL, sprintf( self::$WS_LOGIN_URL, $this->generate_login_params() ) );

		// Return as string instead of echo.
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		// Execute request.
		$data = curl_exec( $ch );

		// Check if all is good.
		if ( ! curl_errno( $ch ) && curl_getinfo( $ch, CURLINFO_HTTP_CODE ) === 200 ) {
			$xml = simplexml_load_string( $data );
			return (string) $xml->Value;
		} else {
			// Return false if we failed to authenticate.
			return false;
		}
	}

	/**
	 * Generates the hash needed for authentication.
	 * @return string
	 */
	private function generate_login_params() {
		// Get the current timestamp.
		$timestamp = gmdate( 'Y-m-d\TH:i:s\Z' );

		// Data is $timestamp and username.
		$data = $this->WS_USERNAME . $timestamp;

		// Return signature as a Bas64 encoded string.
		return http_build_query( array(
			'username'	=> $this->WS_USERNAME,
			'timestamp'	=> $timestamp,
			'hash'		=> str_replace( '/', '~', str_replace( '=', '_', str_replace( '+', '-', base64_encode( hash_hmac( 'sha1', $data, $this->WS_PASSWORD, true ) ) ) ) ),
		) );
	}

}