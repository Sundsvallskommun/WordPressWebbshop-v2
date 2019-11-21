<?php
/**
 * SK_RD_API
 * =========
 *
 * Wrapper class for the Raindance REST API.
 *
 * @since   0.1
 * @package SK_Raindance
 */

use \GuzzleHttp\Client;
use \GuzzleHttp\Middleware;
use \GuzzleHttp\Exception\BadResponseException;

class SK_RD_API {

	/**
	 * Singleton instance of class.
	 * @var SK_RD_API|null
	 */
	private static $instance = null;

	/**
	 * Guzzle instance.
	 * @var Guzzle|null
	 */
	private $http = null;

	/**
	 * Check if URL to Raindance is defined.
	 */
	private function __construct() {
		$rd_url = get_option( 'raindance_api_url' );
		if ( ! empty( $rd_url ) && ! defined( 'RAINDANCE_URL' ) ) {
			define( 'RAINDANCE_URL', $rd_url );
		}

		if ( ! defined( 'RAINDANCE_URL' ) ) {
			throw new Exception( __( 'RAINDANCE_URL is not defined!', 'sk-raindance' ) );
		} else {
			$http_client = $this->get_http_client();
			if ( is_wp_error( $http_client ) ) {
				throw new Exception( $http_client->get_error_message() );
			}
		}
	}

	/**
	 * Returns the singleton instance of the
	 * class.
	 * @return SK_RD_API
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Returns an array of entities.
	 * @param  string $field
	 * @return array
	 */
	public function get_remote_entities( $field ) {
		$valid_fields = array(
			'responsibility_number',
			'occupation_number',
		);

		// False if the field doesn't exist.
		if ( ! in_array( $field, $valid_fields ) ) {
			return new WP_Error( 'invalid_field', sprintf( __( 'Fältet %s är inte giltigt för validering via SMEX.', 'skw' ), $field ) );
		}

		$remote_type = '';
		switch ( $field ) {
			case 'responsibility_number':
				$remote_type = 'RD_ANSVAR';
				break;
			case 'occupation_number':
				$remote_type = 'RD_VERKSAMHET';
				break;
		}

		try {
			$response = $this->http->get( $remote_type );
			$data     = json_decode( (string) $response->getBody() );

			// Process the data. We're only interested in the
			// actual number.
			switch ( $field ) {
				case 'responsibility_number':
					$data = wp_list_pluck( $data, 'AnsvarId' );
					break;
				case 'occupation_number':
					$data = wp_list_pluck( $data, 'VHTId' );
					break;
			}

			// Return the process data.
			return $data;
		} catch ( BadResponseException $e ) {
			SKW()->log( $e->getMessage(), E_ERROR );
			return [];
		} catch ( Exception $e ) {
			SKW()->log( $e->getMessage(), E_ERROR );
			return [];
		}
	}

	/**
	 * Returns the current Guzzle instance.
	 * @return Guzzle
	 */
	private function get_http_client() {
		if ( is_null( $this->http ) ) {
			try {
				$this->http = new Client( array(
					'base_uri' => RAINDANCE_URL,
					'timeout'  => 0,
					'stream'   => false,
					'verify'   => false,
				) );
			} catch ( Exception $e ) {
				return new WP_Error( 'rd_unreachable', __( 'Couldn\'t connect to Raindance.', 'sk-raindance' ) );
			}
		}
		return $this->http;
	}

}
