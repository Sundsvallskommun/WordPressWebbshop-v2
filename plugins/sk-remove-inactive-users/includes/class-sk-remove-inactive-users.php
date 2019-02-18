<?php
/**
 * SK_Remove_Inactive_Users
 * =====
 *
 * The main plugin class file.
 *
 * @since   20181210
 * @package 
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SK_Remove_Inactive_Users {

	private $num_queued_users = 0;
	private $num_deleted_users = 0;

	/**
	 * Constructor.
	 */
	function __construct() {
		add_action( 'parse_request', [ $this, 'listener' ], 5 );
	}

	/**
	 * Our listener that runs certain actions based on GET-parameter.
	 *
	 * @return void
	 */
	public function listener() {
		if ( isset( $_GET['remove_inactive_users'] ) ) {
			switch ( $_GET['remove_inactive_users'] ) {
				case 'setup':
					$this->queue_inactive_users();
					break;
				case 'delete':
					$this->delete_queued_users();
					break;
			}
		}
	}

	/**
	 * Get all WP-Users
	 *
	 * @return array
	 */
	private function get_all_subscribers() {
		$args = array(
			'role' => 'subscriber',
		);
		return get_users( $args );
	}

	/**
	 * Get all WP-Users that was queued for deletion 2 weeks ago.
	 *
	 * @return array
	 */
	private function get_users_for_deletion() {
		$args = array(
			'meta_key' => '_user_deletion_queue',
			'meta_value' => strtotime('-2 weeks'),
			'meta_compare' => '<'
		);

		return get_users( $args );
	}

	/**
	 * Setup users for deletion if not present in smex.
	 * 
	 * @return void
	 */
	private function queue_inactive_users() {
		$users = $this->get_all_subscribers();
		foreach ( $users as $user ) {
			$this->check_user_against_smex( $user );
		}

		printf( "%s users queued for deletion.", $this->num_queued_users );
		die();
	}

	/**
	 * Check if user exists in smex
	 * 
	 * @param  WP_User $user
	 * @return void
	 */
	private function check_user_against_smex( $user ) {

		$username = $user->user_login;

		$smex_user_exists = SK_SMEX_API::get_instance()->user_exists( $username );

		if ( !is_wp_error( $smex_user_exists ) && false === $smex_user_exists ) {
			$this->maybe_set_deletion_meta( $user );
			$this->num_queued_users += 1;
		} else {
			delete_user_meta( $user->ID, '_user_deletion_queue');
		}
	}

	/**
	 * Set deletion meta (current timestamp) if not present on the user.
	 * 
	 * @param  WP_User $user
	 * @return void
	 */
	private function maybe_set_deletion_meta( $user ) {
		if ( !$user ) {
			return false;
		}

		$deletion_meta = $user->get( '_user_deletion_queue' );
		if ( empty( $deletion_meta ) ) {
			$user->get( '_user_deletion_queue' );
			add_user_meta( $user->ID, '_user_deletion_queue', time(), true);
		}
	}

	/**
	 * Delete users that should be deleted.
	 * 
	 * @return void
	 */
	private function delete_queued_users() {

		$users = $this->get_users_for_deletion();

		foreach ( $users as $user ) {
			$this->delete_user( $user ) ? $this->num_deleted_users += 1 : false;
		}

		printf( "%s users deleted.", $this->num_deleted_users );
		die();
	}

	/**
	 * Delete a WP_User
	 * 
	 * @param  WP_User $user
	 * @return bool
	 */
	private function delete_user( $user ) {
		require_once(ABSPATH.'wp-admin/includes/user.php');

		// Reassign content to wpadmin
		$reasign = null;
		$wpadmin = get_user_by( 'login', 'wpadmin' );
		if ( $wpadmin ) {
			$reassign = $wpadmin->ID;
		}

		return wp_delete_user( $user->ID, $reassign );
	}
}
