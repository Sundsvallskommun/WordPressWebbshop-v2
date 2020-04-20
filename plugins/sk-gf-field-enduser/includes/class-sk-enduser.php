<?php
/**
 * SK_Enduser
 * ==========
 *
 * Main plugin file.
 *
 * @since   20191111
 * @package SK_Enduser
 */

class SK_Enduser {

	/**
	 * Adds our actions and filters.
	 */
	public function __construct() {
		// Enqueues our custom scripts along with gravity forms.
		add_action( 'gform_enqueue_scripts', [ $this, 'enqueue_enduser_script' ], 10, 2 );

		// The AJAX action handler for search an enduser.
		add_action( 'wp_ajax_search_enduser', [ $this, 'search_enduser' ] );
		add_action( 'wp_ajax_nopriv_search_enduser', [ $this, 'search_enduser' ] );
	}

	/**
	 * Enqueues select2 and localizes AJAX URL.
	 * @param  array   $form
	 * @param  boolean $is_ajax
	 * @return void
	 */
	public function enqueue_enduser_script( $form, $is_ajax ) {
		wp_enqueue_script( 'select2_sk', SK_GF_FIELD_ENDUSER_PLUGIN_URL . 'assets/vendor/select2.min.js' );
		wp_enqueue_script( 'select2_sk_sv', SK_GF_FIELD_ENDUSER_PLUGIN_URL . 'assets/vendor/select2.sv.js', array( 'select2_sk' ) );
		wp_enqueue_style( 'select2_sk', SK_GF_FIELD_ENDUSER_PLUGIN_URL . 'assets/vendor/select2.min.css' );

		wp_enqueue_script( 'gf_field_enduser', SK_GF_FIELD_ENDUSER_PLUGIN_URL . 'assets/gf-field-enduser.js', array( 'select2_sk' ) );
		wp_localize_script( 'gf_field_enduser', 'ajax', array(
			'url' => admin_url( 'admin-ajax.php' ),
		) );

		wp_enqueue_script( 'enduser', SK_GF_FIELD_ENDUSER_PLUGIN_URL . 'assets/enduser.js', array( 'gf_field_enduser', 'jquery' ) );
	}

	/**
	 * Returns an array of endusers based on search parameters
	 * from SMEX.
	 * @return array
	 */
	public function search_enduser() {
		$search = ( ! empty( $_POST['s'] ) ? wc_clean( $_POST['s'] ) : '' );
		$api    = SK_SMEX_API::get_instance();
		$result = $api->get_enduser_autocomplete( $search );
		if ( ! is_wp_error( $result ) ) {
			wp_send_json( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

}
