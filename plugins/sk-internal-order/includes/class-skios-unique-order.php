<?php
/**
 * SKIOS_Product_Owner
 * ===================
 *
 * Handles all product owner related actions.
 *
 * @since   20170105
 * @package SKIOS
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SKIOS_Unique_Order {

	/**
	 * @var string
	 */
	private static $METAKEY_NAME = '_unique_order_email';

	/**
	 * Registers hooks and filters.
	 */
	public function __construct() {

		// Display owner option on product admin screen.
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_unique_order_field' ) );

		// Save product owner option on product admin screen.
		add_action( 'save_post', array( $this, 'save_product_owner_field' ) );
	}

	/**
	 * Display informatio non product view about who
	 * is the product owner.
	 * @return void
	 */
	public function display_product_owner() {
		try {

			global $product;

			// Only show on simple and variable products, as those are the only ones
			// with owner option. (But owner can be set i type is changed after saving)
			$type = $product->get_type();
			if ( !in_array( $type, array( 'simple', 'variable' ) ) ) {
				return false;
			}

			$owner_id = get_post_meta( $product->get_id(), self::$METAKEY_NAME, true );
			$owner = skios_get_product_owner_by_id( $owner_id );

			if ( isset( $owner[ 'label' ] ) ) {
				printf('<span class="product-owner">Levereras av %s</span>', $owner[ 'label' ] );
			}

		} catch (Exception $e) {}
	}

	/**
	 * Add select to product where product owner are selected.
	 * @return void
	 */
	public function add_unique_order_field() {
		echo '<div class="options_group">';

		// Select
		woocommerce_wp_checkbox(
			array(
				'id'      => self::$METAKEY_NAME,
				'wrapper_class' => 'show_if_simple show_if_variable',
				'label'   => __( 'Skicka som separat ärende', 'woocommerce' ),
				'description'   => __( 'Skicka varan i ett eget mejl även om det finns fler varor med samma ägare.', 'woocommerce' ),
			)
		);

		echo '</div>';
	}

	/**
	 * Save product owner field.
	 * @param  integer $post_id
	 * @return void
	 */
	public function save_product_owner_field( $post_id ) {
		// Don't save unless it's a product.
		if ( get_post_type( $post_id ) !== 'product' ) {
			return false;
		}

		// Don't save revisions or autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Always save a product owner on the post.
		// If one was provided we'll save that, otherwise
		// we'll default to saving a 0 (which means none is selected).
		$product_owner = ( ! empty( $_REQUEST[ self::$METAKEY_NAME ] ) ) ? $_REQUEST[ self::$METAKEY_NAME ] : '';
		if( strlen( $product_owner ) > 0 ) {
			// If the $product_owner is set to 'nc' that means it hasn't
			// been changed and we should just return.
			// This is a quick and dirty fix for the bulk edit error.
			if ( $product_owner === 'nc' ) {
				return;
			}

			update_post_meta( $post_id, self::$METAKEY_NAME, esc_attr( $product_owner ) );
		} else {
			update_post_meta( $post_id, self::$METAKEY_NAME, 0 );
		}
	}

}
