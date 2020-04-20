<?php
/**
 * FMCA_Listener
 * =============
 *
 * Purpose of class is to set up a URL that can act
 * as a listener. The listener will then be able to
 * perform actions and get responses.
 *
 * @since   1.0.0
 * @package FMCA
 */

namespace FMCA;

class Listener {

	/**
	 * The GET variable that tells us to listen.
	 * @since 1.0.0
	 */
	const LISTENER_ACTION = 'listener';

	/**
	 * The hash that is used to authenticate.
	 * @since 1.0.0
	 */
	const HASH_VARIABLE = 'hash';

	/**
	 * Constructs the class.
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'add_listener' ] );
	}

	/**
	 * Adds our listener which will perform
	 * the appropiate action.
	 * @since  1.0.0
	 * @return void
	 */
	public function add_listener() {
		$listen = ( isset( $_GET[ self::LISTENER_ACTION ] ) && $this->is_valid_hash() );
		if ( $listen ) {
			// Get the action that has been asked to perform.
			$requested_action = $_GET[ self::LISTENER_ACTION ];

			// Result of action goes here.
			// The data type may vary.
			// integer|string|array
			$result = null;

			// Now, loop through all the available
			// actions and if one of them matches the
			// requested one, we'll perform it.
			foreach ( $this->get_actions() as $action_name => $callable ) {
				if ( $requested_action === $action_name && is_callable( $callable['callable'] ) ) {
					$result = call_user_func_array( $callable['callable'], $callable['args'] );
				}
			}

			// Output result as JSON.
			wp_send_json( $result );
		}
	}

	/**
	 * Verifies that the hash is correct.
	 * @since  1.0.0
	 * @return boolean
	 */
	private function is_valid_hash() {
		if ( isset( $_GET[ self::HASH_VARIABLE ] ) ) {
			$valid_hash = Options::get_instance()->get_option( 'fmca_listener_hash' );
			return ( $_GET[ self::HASH_VARIABLE ] === $valid_hash );
		}

		return false;
	}

	/**
	 * Returns an array of actions available.
	 * @since  1.0.0
	 * @return array
	 */
	private function get_actions() {
		return apply_filters( 'fmca_listener_actions', [] );
	}

}
