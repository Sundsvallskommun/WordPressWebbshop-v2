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
	private $ws_username;

	/**
	 * Given password.
	 * @var string
	 */
	private $ws_password;

	/**
	 * The URL for the login endpoint of WebService.
	 * @var string
	 */
	private static $ws_login_url = 'https://dedu.se/DeDUservicetest/Login?%s';

	/**
	 * The URL for the endpoint for creating tasks.
	 * @var string
	 */
	private static $ws_create_task_url = 'https://dedu.se/DeDUservicetest/TemplatedXML?TemplateName=Sundsvall_CreateWebShopTaskFromList&SessionKey=%s';

	/**
	 * Authenticates with the WebService on construct.
	 */
	public function __construct( $username, $password ) {
		$this->ws_username = $username;
		$this->ws_password = $password;

		// Try to authenticate.
		if ( $session_key = $this->authenticate() ) {
			$this->ws_session_key = $session_key;
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
		// Get the logger.
		$logger  = wc_get_logger();
		$context = array(
			'source' => 'sk-dedu',
		);

		// Translators: the order ID.
		$logger->info( sprintf( __( 'Starting to send WC_Order #%s to DeDU..', 'sk-dedu' ), $order->get_id() ), $context );

		// Init cURL.
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, sprintf( self::$ws_create_task_url, $this->ws_session_key ) );
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
				$logger->info( __( 'DeDU replied with status code: 201. Order successfully exported.', 'sk-dedu' ), $context );
				return true;
			} else {
				$log_entry = str_replace( "\r", '', str_replace( "\n", '', $xml ) );
				// Translators: the order XML.
				$logger->debug( sprintf( __( 'WC_Order #%1$s XML: %2$s', 'sk-dedu' ), $order->get_id(), $log_entry ), $context );

				$log_entry = str_replace( "\r", '', str_replace( "\n", '', $data ) );
				// Otherwise, log the incident and the request.
				// Translators: the cURL response.
				$logger->emergency( sprintf( __( 'Could not create order in DeDU. DeDU replied:%s', 'sk-dedu' ), $log_entry ), $context );

				// Return a generic error message.
				return new WP_Error( 'dedu_error', __( 'Något gick fel vid beställningen.', 'sk-dedu' ) );
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
		curl_setopt( $ch, CURLOPT_URL, sprintf( self::$ws_login_url, $this->generate_login_params() ) );

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
		$data = $this->ws_username . $timestamp;

		// Return signature as a Bas64 encoded string.
		return http_build_query( array(
			'username'  => $this->ws_username,
			'timestamp' => $timestamp,
			'hash'      => str_replace( '/', '~', str_replace( '=', '_', str_replace( '+', '-', base64_encode( hash_hmac( 'sha1', $data, $this->ws_password, true ) ) ) ) ),
		) );
	}

}
