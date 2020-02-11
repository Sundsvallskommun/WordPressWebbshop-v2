<?php
/**
 * Hide page title based on the option "_hide_page_title".
 */

add_action( 'wp', function() { //phpcs:ignore

	$page_id = get_the_ID();

	if ( ! isset( $page_id ) ) {
		return;
	}

	if ( '1' === get_post_meta( $page_id, '_hide_page_title', true) ) {
		remove_action( 'storefront_page', 'storefront_page_header', 10 );

		?>
		<style>
			.storefront-breadcrumb {
				margin-bottom: 0 !important;
			}
		</style>
		<?php
	}

}, 10 ); //phpcs:ignore


/**
 * Register the meta box
 */
function global_notice_meta_box() {

	add_meta_box(
		'hide-title',
		'Sidrubrik',
		'hide_title_meta_box_callback',
		'page',
		'side'
	);
}

/**
 * Output the meta box
 */
function hide_title_meta_box_callback( $post ) {
	wp_nonce_field( 'hide_page_title_nonce', 'hide_page_title_nonce' );
	$value = get_post_meta( $post->ID, '_hide_page_title', true );
	echo '<input value="0" type="hidden" name="hide_page_title">';
	echo '<input '. checked($value, '1', false) .' value="1" type="checkbox" id="hide_page_title" name="hide_page_title">';
	echo '<label for="hide_page_title">'. 'Dölj sidrubriken' .'</label>';
}

add_action( 'add_meta_boxes', 'global_notice_meta_box' );

/**
 * Save the meta box
 */
function save_hide_page_title_field( $post_id ) {

	if ( ! isset( $_POST['hide_page_title_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['hide_page_title_nonce'], 'hide_page_title_nonce' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	if ( ! isset( $_POST['hide_page_title'] ) ) {
		return;
	}

	$data = sanitize_text_field( $_POST['hide_page_title'] );

	update_post_meta( $post_id, '_hide_page_title', $data );

}

add_action( 'save_post', 'save_hide_page_title_field' );