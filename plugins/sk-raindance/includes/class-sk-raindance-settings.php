<?php
/**
 * SK_Raindance_Settings
 * =====================
 *
 * Settings for the Raindance integration.
 *
 * @since   20191111
 * @package SK_Raindance
 */

class SK_Raindance_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_options_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'raindance_missing_url_notice' ) );
	}

	/**
	 * Registers the options page.
	 * @return void
	 */
	public function register_options_page() {
		add_options_page( 'Raindance-Inställningar', 'Raindance', 'manage_options', 'sk-raindance-options', array( $this, 'output_options_page' ) );
	}

	/**
	 * Outputs the options page.
	 * @return void
	 */
	public function output_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'sk-raindance' ) );
		}
		?>

		<div class="wrap">
			<h1>Raindance-inställningar</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'raindance-options-group' ); ?>

				<table class="form-table">
					<tr valign="top">
					<th scope="row">URL</th>
					<td>
						<input type="text" name="raindance_api_url" value="<?php echo esc_attr( get_option( 'raindance_api_url' ) ); ?>" />
						<p class="description">Adress till Raindance.</p>
					</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers our Raindance settings.
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'raindance-options-group', 'raindance_api_url' );
	}

	/**
	 * Outputs an admin notice when the URL is missing.
	 * @return void
	 */
	public function raindance_missing_url_notice() {

		// Only show error for users who can edit the option.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only show error if setting has not been set.
		if ( ! empty( get_option( 'raindance_api_url' ) ) || defined( 'RAINDANCE_URL' ) ) {
			return;
		}

		$class = 'notice notice-error';
		$message = __( 'För att Raindance-integrationen ska fungera som tänkt måste korrekt URL till Raindance anges under inställningar > Raindance.', 'sk-raindance' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

}
