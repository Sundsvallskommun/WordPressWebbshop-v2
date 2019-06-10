<?php
/**
* SK_Password_Resetter
* ====================
*
* Main plugin file.
*
* Register hooks and filters.
*
* @since   1.0
* @package SK_Password_Resetter
*/

class SK_Password_Resetter {

	private $saml_auth_key;

	public function __construct() {

		if ( ! defined( 'AUTH_KEY' ) ) {
			wp_die( 'No WP Auth-key defined' );
		}

		$this->saml_auth_key = constant( 'AUTH_KEY' );

		$this->init_hooks();
	}

	/**
	* Initiates all action and filter hooks.
	* @return void
	*/
	private function init_hooks() {
		add_action( 'parse_request', array( $this, 'listener' ) );
	}

	/**
	* Our listener that runs certain actions based on GET-parameter.
	*
	* @return void
	*/
	public function listener() {
		if ( isset( $_GET['reset_passwords'] ) ) {
			switch ( $_GET['reset_passwords'] ) {
				case 'run':
					$this->reset_passwords();
					break;
			}
		}
	}

	/**
	 * Regenerate all users passwords.
	 *
	 * @return void
	 */
	private function reset_passwords() {

		$args = array(
			'fields'   => array( 'ID', 'user_login' ),
		);

		$users = get_users( $args );

		$num_updated = 0;

		foreach ( $users as $user ) {
			$user_id  = $user->ID;
			$username = $user->user_login;

			// We don't want to update password for the wpadmin-user.
			if ( 'wpadmin' === $username ) {
				continue;
			}

			$new_pass = $this->user_password( $username, $this->saml_auth_key );

			wp_set_password( $new_pass, $user_id );

			$num_updated += 1;
		}

		printf( 'Updated password for %s out of %s users.', $num_updated, count( $users ) );

		die();
	}

	/**
	 * Generates a SHA-256 HMAC hash using the username and secret key
	 *
	 * @param string $value the user's username
	 * @param string $key a secret key
	 * @return string
	 */
	private function user_password( $value, $key ) {
		$value = strtolower( $value );
		$hash  = hash_hmac( 'sha256', $value, $key );
		return $hash;
	}
}
