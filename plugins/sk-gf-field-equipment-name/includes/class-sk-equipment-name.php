<?php
/**
 * SK_Equipment_Name
 * ==========
 *
 * Main plugin file.
 *
 * @since   20191111
 * @package SK_Equipment_Name
 */

class SK_Equipment_Name {

	/**
	 * Adds our actions and filters.
	 */
	public function __construct() {
		// Enqueues our custom scripts along with gravity forms.
		add_action( 'gform_enqueue_scripts', [ $this, 'enqueue_equipment_name_script' ], 10, 2 );

		// The AJAX action handler for search an enduser.
		add_action( 'wp_ajax_search_equipment_name', [ $this, 'search_equipment_name' ] );
		add_action( 'wp_ajax_nopriv_search_equipment_name', [ $this, 'search_equipment_name' ] );
	}

	/**
	 * Enqueues select2 and localizes AJAX URL.
	 * @param  array   $form
	 * @param  boolean $is_ajax
	 * @return void
	 */
	public function enqueue_equipment_name_script( $form, $is_ajax ) {
		wp_enqueue_script( 'select2_sk', SK_GF_FIELD_EQUIPMENT_NAME_PLUGIN_URL . 'assets/vendor/select2.min.js' );
		wp_enqueue_script( 'select2_sk_sv', SK_GF_FIELD_EQUIPMENT_NAME_PLUGIN_URL . 'assets/vendor/select2.sv.js', array( 'select2_sk' ) );
		wp_enqueue_style( 'select2_sk', SK_GF_FIELD_EQUIPMENT_NAME_PLUGIN_URL . 'assets/vendor/select2.min.css' );

		wp_enqueue_script( 'gf_field_equipment_name', SK_GF_FIELD_EQUIPMENT_NAME_PLUGIN_URL . 'assets/gf-field-equipment-name.js', array( 'select2_sk' ) );
		wp_localize_script( 'gf_field_equipment_name', 'ajax', array(
			'url' => admin_url( 'admin-ajax.php' ),
		) );

		wp_enqueue_script( 'equipment_name', SK_GF_FIELD_EQUIPMENT_NAME_PLUGIN_URL . 'assets/equipment-name.js', array( 'gf_field_equipment_name', 'jquery' ) );
	}

	/**
	 * Returns an array of endusers based on search parameters
	 * from SMEX.
	 * @return array
	 */
	public function search_equipment_name() {
		$search = ( ! empty( $_POST['s'] ) ? wc_clean( $_POST['s'] ) : '' );
		$api    = SK_POB::get_instance();
		$result = $api->get_equipment_name( $search );
		if ( ! is_wp_error( $result ) ) {
			foreach ( $result as $row) {
				$output[] = [
					'id' => $row->Data->Id,
					'text' => $row->Data->OptionalNumber
				];
			}
			wp_send_json( $output );
		} else {
			wp_send_json_error( $output );
		}
	}

}
