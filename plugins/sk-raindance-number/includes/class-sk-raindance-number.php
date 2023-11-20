<?php
/**
 * SK_Raindance_Number
 * ==========
 *
 * Main plugin file.
 *
 * @since   20191111
 * @package SK_Raindance_Number
 */

class SK_Raindance_Number {

	/**
	 * Adds our actions and filters.
	 */
	public function __construct() {
		// Enqueues our custom scripts along with gravity forms.
		add_action( 'gform_enqueue_scripts', [ $this, 'enqueue_raindance_number_script' ], 10, 2 );

		// The AJAX action handler for search an enduser.
		add_action( 'wp_ajax_search_raindance_number', [ $this, 'search_raindance_number' ] );
		add_action( 'wp_ajax_nopriv_search_raindance_number', [ $this, 'search_raindance_number' ] );
	}

	/**
	 * Enqueues select2 and localizes AJAX URL.
	 * @param  array   $form
	 * @param  boolean $is_ajax
	 * @return void
	 */
	public function enqueue_raindance_number_script( $form, $is_ajax ) {

		wp_enqueue_script( 'gf_field_raindance_number', SK_GF_FIELD_RAINDANCE_NUMBER_PLUGIN_URL . 'assets/gf-field-raindance_number.js', array( 'select2_sk' ) );
		wp_localize_script( 'gf_field_raindance_number', 'ajax', array(
			'url' => admin_url( 'admin-ajax.php' ),
		) );

		wp_enqueue_script( 'raindance_number', SK_GF_FIELD_RAINDANCE_NUMBER_PLUGIN_URL . 'assets/raindance_number.js', array( 'gf_field_raindance_number', 'jquery' ) );
	}

}