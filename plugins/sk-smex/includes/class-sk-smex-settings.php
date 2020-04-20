<?php
/**
 * SK_SMEX_Settings
 * ================
 *
 * The settings class for SMEX.
 *
 * @since   0.1
 * @package SK_SMEX
 */

class SK_SMEX_Settings {

	/**
	 * Registers our settings.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_smex_settings' ) );
		add_action( 'admin_notices', array( $this, 'smex_missing_url_notice' ) );
	}

	/**
	 * Register our option page with WP.
	 * @return void
	 */
	public function register_settings_page() {
		add_options_page( 'SMEX-Inställningar', 'SMEX', 'manage_options', 'sk-smex-options', array( $this, 'output_settings_page' ) );
	}

	/**
	 * Outputs the settings page.
	 * @return void
	 */
	function output_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		Timber::render( __DIR__ . '/views/settings-page.twig', array(
			'smex_api_url' => esc_attr( get_option( 'smex_api_url' ) ),
		) );
	}

	/**
	 * Register the SMEX settings.
	 * @return void
	 */
	public function register_smex_settings() {
		register_setting( 'smex-options-group', 'smex_api_url' );
	}

	/**
	 * Outputs an admin notice if the SMEX URL
	 * isn't set yet.
	 * @return void
	 */
	public function smex_missing_url_notice() {
		// Only show error for users who can edit the option.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only show error if setting has not been set.
		if ( ! empty( get_option( 'smex_api_url' ) ) || defined( 'SMEX_URL' ) ) {
			return;
		}

		$message = __( 'För att SMEX-integrationen ska fungera som tänkt måste korrekt URL till metakatalogen anges under inställningar > SMEX.', 'sk-smex' );
		Timber::render( __DIR__ . '/views/admin-notice.twig', array(
			'notice' => $message,
		) );
	}

}
