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

class SKIOS_Product_Owner {

	/**
	 * @var string
	 */
	private static $METAKEY_NAME = '_product_owner';

	/**
	 * Registers hooks and filters.
	 */
	public function __construct() {

		add_action( 'woocommerce_product_meta_start', array( $this, 'display_product_owner' ), 30 );

		// Display owner option on product admin screen.
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_owner_field' ) );

		// Save product owner option on product admin screen.
		add_action( 'save_post', array( $this, 'save_product_owner_field' ) );
	}

	/**
	 * Display information on product view about who is the product owner
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

			$owner_id = get_post_meta( $product->id, self::$METAKEY_NAME, true );
			$owner = skios_get_product_owner_by_id($owner_id);

			if ( isset( $owner['label'] ) ) {
				printf('<span class="product-owner">Levereras av %s</span>', $owner['label'] );
			}

		} catch (Exception $e) { }

	}

	/**
	 * Add select to product where product owner are selected.
	 * @return void
	 */
	public function add_product_owner_field() {
		// Get all owners.
		$owners = skios_get_product_owners();

		$options = array();
		// Add an empty default option.
		$options[ 0 ] = __( '--Ingen vald--', 'skios' );

		// Add all available product owners as options.
		foreach ( $owners as $owner ) {

			$id    = $owner['id'];
			$label = sprintf( '%s (%s)', $owner['label'], $owner['identifier'] );

			$options[$id] = $label;
		}


		echo '<div class="options_group">';

		// Select
		woocommerce_wp_select(
			array(
				'id'      => '_product_owner',
				'wrapper_class' => array( 'show_if_simple', 'show_if_variable' ),
				'label'   => __( 'Produktägare', 'woocommerce' ),
				'options' => $options
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