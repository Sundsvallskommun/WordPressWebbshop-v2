<?php
/**
 * SK_pob_Settings
 * ================
 *
 * Adds the tab and fields to the back-end admin UI for
 * products where the user can enter all necessary
 * pob fields.
 *
 * @since   0.1
 * @package SK_pob
 */

class SK_POB_Settings {

	/**
	 * Registers our hooks.
	 */
	function __construct() {
		// Add a link to our settings page.
		add_action( 'admin_menu', array( $this, 'my_plugin_menu' ) );

		// Add our settings.
		add_action( 'admin_init', array( $this, 'register_pob_settings' ) );

		// Add an admin notice if any of pob settings are missing.
		add_action( 'admin_notices', array( $this, 'pob_missing_url_notice' ) );
	}

	/**
	 * Registers our options page.
	 * @return void
	 */
	public function my_plugin_menu() {
		add_options_page( 'POB-Inställningar', 'POB', 'manage_options', 'sk-pob-options', array( $this, 'pob_options_page' ) );
	}

	/**
	 * Outputs our options page.
	 * @return void
	 */
	public function pob_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>

		<div class="wrap">
			<h1>POB-inställningar</h1>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'pob-options-group' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row">Användarnamn</th>
						<td>
							<input type="text" name="pob_username" value="<?php echo esc_attr( get_option( 'pob_username' ) ); ?>" />
							<p class="description">Användarnamn för POB.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">Lösenord</th>
						<td>
							<input type="text" name="pob_password" value="<?php echo esc_attr( get_option( 'pob_password' ) ); ?>" />
							<p class="description">Lösenord för POB.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">URL</th>
						<td>
							<input type="text" name="pob_url" value="<?php echo esc_attr( get_option( 'pob_url' ) ); ?>" />
							<p class="description">Adress till pob.</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers our settings.
	 * @return void
	 */
	public function register_pob_settings() {
		register_setting( 'pob-options-group', 'pob_username' );
		register_setting( 'pob-options-group', 'pob_password' );
		register_setting( 'pob-options-group', 'pob_url' );
	}

	function pob_missing_url_notice() {
		// Only show error for users who can edit the option.
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only show error if setting has not been set.
		if ( ! empty( get_option( 'pob_username' ) ) && ! empty( get_option( 'pob_password' ) ) && ! empty( get_option( 'pob_url' ) ) ) {
			return;
		}

		$class = 'notice notice-error';
		$message = __( 'För att POB-integrationen ska fungera som tänkt måste korrekt URL till pob anges under inställningar > POB.', 'sk-pob' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

}
