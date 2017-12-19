<?php
/**
 * SK_DeDU_Settings
 * ================
 *
 * Adds the tab and fields to the back-end admin UI for
 * products where the user can enter all necessary
 * DeDU fields.
 *
 * @since   0.1
 * @package SK_DeDU
 */

class SK_DeDU_Settings {

	/**
	 * Registers our hooks.
	 */
	function __construct() {
		// Add a link to our settings page.
		add_action( 'admin_menu', array( $this, 'my_plugin_menu' ) );

		// Add our settings.
		add_action( 'admin_init', array( $this, 'register_smex_settings' ) );

		// Add an admin notice if any of DeDU settings are missing.
		add_action( 'admin_notices', array( $this, 'smex_missing_url_notice' ) );
	}

	/**
	 * Registers our options page.
	 * @return void
	 */
	public function my_plugin_menu() {
		add_options_page( 'DeDU-Inställningar', 'DeDU', 'manage_options', 'sk-dedu-options', array( $this, 'dedu_options_page' ) );
	}

	/**
	 * Outputs our options page.
	 * @return void
	 */
	public function dedu_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>

		<div class="wrap">
			<h1>DeDU-inställningar</h1>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'dedu-options-group' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row">Användarnamn</th>
						<td>
							<input type="text" name="dedu_username" value="<?php echo esc_attr( get_option( 'dedu_username' ) ); ?>" />
							<p class="description">Användarnamn för DeDU.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">Lösenord</th>
						<td>
							<input type="text" name="dedu_password" value="<?php echo esc_attr( get_option( 'dedu_password' ) ); ?>" />
							<p class="description">Lösenord för DeDU.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">URL</th>
						<td>
							<input type="text" name="dedu_url" value="<?php echo esc_attr( get_option( 'dedu_url' ) ); ?>" />
							<p class="description">Adress till DeDU.</p>
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
	public function register_smex_settings() {
		register_setting( 'dedu-options-group', 'dedu_username' );
		register_setting( 'dedu-options-group', 'dedu_password' );
		register_setting( 'dedu-options-group', 'dedu_url' );
	}

	function smex_missing_url_notice() {
		// Only show error for users who can edit the option.
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only show error if setting has not been set.
		if ( ! empty( get_option( 'dedu_username' ) ) && ! empty( get_option( 'dedu_password' ) ) && ! empty( get_option( 'dedu_url' ) ) ) {
			return;
		}

		$class = 'notice notice-error';
		$message = __( 'För att DeDU-integrationen ska fungera som tänkt måste korrekt URL till DeDU anges under inställningar > DeDU.', 'sk-dedu' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

}
