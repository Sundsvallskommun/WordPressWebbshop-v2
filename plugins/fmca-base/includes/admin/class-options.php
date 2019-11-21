<?php
/**
 * FMCA_Options
 * ============
 *
 * Registers our options.
 *
 * @since   1.0.0
 * @package FMCA
 */

namespace FMCA;

class Options {

	/**
	 * Singleton instance.
	 * @var FMCA_Options|null
	 */
	private static $instance = null;

	/**
	 * Registers hooks and filters.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_options_page' ] );
		add_action( 'admin_init', [ $this, 'register_fmca_settings' ] );
	}

	/**
	 * Returns Singleton instance.
	 * @since  1.0.0
	 * @return FMCA_Options
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the options page.
	 * @since  1.0.0
	 * @return void
	 */
	public function register_options_page() {
		add_options_page( __( 'FMCA Options', 'fmca' ), 'FMCA', 'manage_options', 'fmca-options', array( $this, 'fmca_options_page' ) );
	}

	/**
	 * Outputs the options page.
	 * @since  1.0.0
	 * @return void
	 */
	public function fmca_options_page() {
		if ( ! current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ), 'fmca' );
		}

		\Timber::render( __DIR__ . '/../views/settings-page.twig', [
			'fmca_listener_hash' => esc_attr( get_option( 'fmca_listener_hash' ) ),
		] );
	}

	/**
	 * Registers our options.
	 * @since  1.0.0
	 * @return void
	 */
	public function register_fmca_settings() {
		register_setting( 'fmca-options-group', 'fmca_listener_hash' );
		register_setting( 'fmca-options-group', 'fmca_notification_email' );

		/**
		 * Fires after all default settings have been registered.
		 *
		 * @since 1.3.2
		 */
		do_action( 'after_fmca_register_settings' );
	}

	/**
	 * Returns option value.
	 * @param  string $option_name
	 * @since  1.0.0
	 * @return mixed
	 */
	public function get_option( $option_name ) {
		return get_option( 'fmca_listener_hash' );
	}

}
