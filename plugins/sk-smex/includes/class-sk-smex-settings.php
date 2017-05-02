<?php

class SK_SMEX_Settings {

  function __construct() {
    add_action( 'admin_menu', array($this, 'my_plugin_menu') );
    add_action( 'admin_init', array($this, 'register_smex_settings') );

    add_action( 'admin_notices', array( $this, 'smex_missing_url_notice' ) );
  }

  function my_plugin_menu() {
  	add_options_page( 'SMEX-Inställningar', 'SMEX', 'manage_options', 'sk-smex-options', array($this, 'smex_options_page') );
  }

  /** Step 3. */
  function smex_options_page() {
  	if ( !current_user_can( 'manage_options' ) )  {
  		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
  	}
    ?>
  	<div class="wrap">
    	<h1>SMEX-inställningar</h1>
      <form method="post" action="options.php">
        <?php settings_fields( 'smex-options-group' ); ?>

        <table class="form-table">
          <tr valign="top">
          <th scope="row">URL</th>
          <td>
            <input type="text" name="smex_api_url" value="<?php echo esc_attr( get_option('smex_api_url') ); ?>" />
            <p class="description">Adress till SMEX-katalogen.</p>
          </td>
          </tr>
        </table>

        <?php submit_button(); ?>
      </form>
  	</div>
    <?php
  }

  function register_smex_settings() {
    register_setting( 'smex-options-group', 'smex_api_url' );
  }

  function smex_missing_url_notice() {

    // Only show error for users who can edit the option.
  	if ( !current_user_can( 'manage_options' ) ) return;

    // Only show error if setting has not been set.
    if ( !empty( get_option('smex_api_url') ) || defined( 'SMEX_URL' ) ) return;

    $class = 'notice notice-error';
  	$message = __( 'För att SMEX-integrationen ska fungera som tänkt måste korrekt URL till metakatalogen anges under inställningar > SMEX.', 'sk-smex' );

  	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
  }

}
