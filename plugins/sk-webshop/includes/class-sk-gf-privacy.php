<?php
class SK_GF_Privacy {

	public function __construct() {
		add_filter( 'gform_form_settings_menu', array( $this, 'gf_privacy_menu_item' ) );
		add_action( 'gform_form_settings_page_privacy_settings_page', array( $this, 'gf_privacy_settings_page' ) );
		add_action( 'gform_after_submission', array( $this, 'delete_post_content' ), 10, 2 );
	}

	function gf_privacy_menu_item( $menu_items ) {

		$menu_items[] = array(
			'name' => 'privacy_settings_page',
			'label' => __( 'Sekretess' )
		);

		return $menu_items;
	}


	function gf_privacy_settings_page() {

		GFFormSettings::page_header();

		$form_id       = rgget( 'id' );
		$form          = RGFormsModel::get_form_meta( $form_id );
		$update_result = array();

		if ( rgpost( 'save' ) ) {

			// Die if not posted from correct page
			check_admin_referer( "gform_save_form_settings_{$form_id}", 'gform_privacy_settings' );

			$is_privacy = rgpost('form_is_privacy') ? true : false;

			update_post_meta( $form_id, 'is_privacy', $is_privacy );

		}

		echo '<form action="" method="post" id="gform_privacy_settings">';

		echo '<h2>Sekretess</h2>';

		echo '<div>';

		$selected = get_post_meta( $form_id, 'is_privacy', true ) == true ? 'checked="checked"' : '';

		echo '<input type="checkbox" id="form_is_privacy" name="form_is_privacy" ' . $selected . ' />';

		echo '<label for="form_is_privacy"> Rensa data när mejl har skickats</label>';

		echo '</div>';

		?>
		 <p class="submit">
				<input type="submit" name="save" value="<?php _e( 'Save', 'gravityforms' ); ?>" class="button-primary">
			</p>

			<?php wp_nonce_field( "gform_save_form_settings_{$form_id}", 'gform_privacy_settings' ); ?>

			<input type="hidden" id="gform_meta" name="gform_meta" />

		<?php

		echo '</form>';

		GFFormSettings::page_footer();

	}

	function delete_post_content( $entry, $form ) {

		$post_id = $entry['id'];

		$is_privacy = get_post_meta( $form['id'], 'is_privacy', true );

		if ( $is_privacy !== '1' ) return;

    // Uncomment the following two lines to delete the entry instead of modifying it.
    // GFAPI::delete_entry( $entry['id'] );
    // return;

    $updated_entry = $entry;

    $allowed_values = array(
      'id',
      'form_id',
      'date_created',
      'is_read',
      'source_url',
      'post_id',
      'currency',
      'payment_status',
      'payment_date',
      'transaction_id',
      'payment_amount',
      'payment_method',
      'is_fulfilled',
      'transaction_type',
      'status'
    );

    foreach ( $updated_entry as $key => $value ) {
      if (!in_array( $key, $allowed_values )) {
        $updated_entry[$key] = 'Sekretess';
      }

      // Set user ID to a high integer probably not used by any user.
      if ( $key == 'created_by') {
        $updated_entry[$key] = '2000000000';
      }
    }

		GFAPI::update_entry( $updated_entry, $entry['id'] );

	}


}
