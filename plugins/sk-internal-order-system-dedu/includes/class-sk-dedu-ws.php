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
	private $ws_login_url = '/Login?%s';

	/**
	 * The URL for the endpoint for creating tasks.
	 * @var string
	 */
	private $ws_create_task_url = '/TemplatedXML?TemplateName=Sundsvall_CreateWebShopTaskFromList&SessionKey=%s';

	/**
	 * Authenticates with the WebService on construct.
	 * @param string $base_url
	 * @param string $username
	 * @param string $password
	 */
	public function __construct( $base_url, $username, $password ) {
		// Set URLs.
		$this->ws_login_url = untrailingslashit( $base_url ) . $this->ws_login_url;
		$this->ws_create_task_url = untrailingslashit( $base_url ) . $this->ws_create_task_url;

		// Set credentials as class properties.
		$this->ws_username = $username;
		$this->ws_password = $password;

		// Try to authenticate.
		$session_key = $this->authenticate();

		// Try to authenticate.
		if ( ! is_wp_error( $session_key ) ) {
			$this->ws_session_key = $session_key;
		} else {
			// Log it.
			SKW()->log(
				sprintf( 'PHP Error: Unable to connect to %s in %s. Message from DeDU: %s',
					$base_url,
					__FILE__,
					$session_key->get_error_message()
				),
				E_WARNING
			);

			// Throw a general exception since this might
			// end up in the front-end.
			throw new Exception( __( 'Något gick fel vid beställningen.', 'sk-dedu' ) );
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
		curl_setopt( $ch, CURLOPT_URL, sprintf( $this->ws_create_task_url, $this->ws_session_key ) );
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
				// Try to get the error from headers.
				$error = ( isset( SKW()->get_headers_from_curl( $data )['ErrorDescription'] ) ) ?
					SKW()->get_headers_from_curl( $data )['ErrorDescription'] : '';

				// Translators: WC_Order::ID.
				SKW()->log( sprintf(
					'PHP Notice: Failed to export WC_Order #%1$s to DeDU in %2$s. Message from DeDU: %3$s',
					$order->get_id(),
					__FILE__,
					$error
				), E_WARNING );

				$log_entry = str_replace( "\r", ' ', str_replace( "\n", ' ', $data ) );
				// Otherwise, log the incident and the request.
				// Translators: the cURL response.
				SKW()->log( sprintf( __( 'PHP Debug: WC_Order #%1$s cURL response: %2$s', 'sk-dedu' ), $order->get_id(), $log_entry ), E_WARNING );

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
		curl_setopt( $ch, CURLOPT_URL, sprintf( $this->ws_login_url, $this->generate_login_params() ) );

		// Return as string instead of echo.
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, true );

		// Execute request.
		$data = curl_exec( $ch );

		// Check if all is good.
		if ( ! curl_errno( $ch ) && curl_getinfo( $ch, CURLINFO_HTTP_CODE ) === 200 ) {
			$xml = simplexml_load_string( $data );
			return (string) $xml->Value;
		} else {
			// Try to get the error from headers.
			$error = ( isset( SKW()->get_headers_from_curl( $data )['ErrorDescription'] ) ) ?
				SKW()->get_headers_from_curl( $data )['ErrorDescription'] : '';

			// Return false if we failed to authenticate.
			return new WP_Error( 'dedu_connection_failed', $error );
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
