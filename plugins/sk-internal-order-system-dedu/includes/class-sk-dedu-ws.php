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
	private static $WS_LOGIN_URL = 'http://www.dedu.se/DeDUService/Login?%s';

	/**
	 * The URL for the endpoint for creating tasks.
	 * @var string
	 */
	private static $WS_CREATE_TASK_URL = 'http://www.dedu.se/DeDUService/TemplatedXML?TemplateName=Sundsvall_CreateWebShopTaskFromList&SessionKey=%s';

	/**
	 * Authenticates with the WebService on construct.
	 */
	public function __construct( $username, $password ) {
		$this->WS_USERNAME = $username;
		$this->WS_PASSWORD = $password;
	}

	/**
	 * Sends an order to the DeDU WebService.
	 * @param  WC_Order $order
	 * @param  array    $order_items Array of products
	 * @return
	 */
	public function send_order( WC_Order $order, $order_items ) {
		if ( ! ( $session_key = $this->authenticate() ) ) {
			// Init cURL.
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, sprintf( self::$WS_CREATE_TASK_URL, $session_key ) );

			// Opening XML.
			$xml = <<<XYZ
<?xml version="1.0" encoding="utf-8" ?>
<Sundsvall_ListOfWebShopTasks xmlns:xsi="&quot;"http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
XYZ;

			// Create XML for each product.
			foreach ( $order_items as $item ) {
				
			}
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
			return $data;
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
		$data = self::$WS_USERNAME . $timestamp;

		// Return signature as a Bas64 encoded string.
		return http_build_query( array(
			'username'	=> self::$WS_USERNAME,
			'timestamp'	=> $timestamp,
			'hash'		=> base64_encode( hash_hmac( 'sha1', $data, self::$WS_PASSWORD, true ) ),
		) );
	}

}