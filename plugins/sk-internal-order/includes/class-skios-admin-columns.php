<?php
/**
 * SKIOS_AdminColumns
 * ==================
 *
 * Adds the admin column for product owner.
 *
 * @since   20170105
 * @package SKIOS
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SKIOS_Admin_Columns {

	/**
	 * @var string
	 */
	private static $COLUMN_NAME = 'product_owner';

	/**
	 * @var string
	 */
	private static $ORDERBY_NAME = 'product_owner';

	/**
	 * Adds all necessary filters for the admin columns.
	 */
	public function __construct() {
		// Add column.
		add_filter( 'manage_edit-product_columns', array( $this, 'column_header_func' ) );

		// Add column content.
		add_action( 'manage_product_posts_custom_column', array( $this, 'column_content_func' ), 10, 2 );

		// Make it sortable.
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'sortable_column_func' ) );

		// Add action for our 'orderby'.
		add_action( 'pre_get_posts', array( $this, 'orderby_func' ) );

		// Bulk edit box.
		add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit' ), 10, 2 );

		// Quick edit box.
		add_action( 'quick_edit_custom_box',  array( $this, 'quick_edit' ), 10, 2 );
	}

	/**
	 * Adds the column.
	 * @param  array $defaults Default columns
	 * @return array
	 */
	public function column_header_func( $defaults ) {
		$pos = array_search( 'is_in_stock', array_keys( $defaults ) );
		$defaults = array_merge(
			array_slice( $defaults, 0, $pos ),
			array(
				self::$COLUMN_NAME => __( 'Produktägare', 'skios' )
			),
			array_slice( $defaults, $pos )
		);
		return $defaults;
	}


	/**
	 * Adds the content for the custom column.
	 * @param  string  $column_name
	 * @param  integer $post_id
	 * @return string
	 */
	public function column_content_func( $column_name, $post_id ) {
		if ( $column_name === self::$COLUMN_NAME ) {
			// Get the ID from postmeta.
			$product_owner_id = get_post_meta( $post_id, '_product_owner', true );

			// Check if it's set.
			if ( empty( $product_owner_id ) ) {
				$product_owner = '';
			} else {
				$product_owner = skios_get_product_owner_by_id( $product_owner_id )[ 'label' ];
			}

			printf( '<br><abbr class="product-owner" data-id="%d" title="%2$s">%2$s</abbr>', $product_owner_id, $product_owner );
		}
	}

	/**
	 * Makes our custom column sortable.
	 * @param  array $columns
	 * @return array
	 */
	public function sortable_column_func( $columns ) {
		$columns[ self::$COLUMN_NAME ] = self::$ORDERBY_NAME;
		return $columns;
	}

	/**
	 * Adds our orderby.
	 * @param  WP_Query $q
	 * @return void
	 */
	public function orderby_func( $q ) {
		if ( $q->is_main_query() && ( $orderby = $q->get( 'orderby' ) ) ) {
			if ( $orderby === self::$ORDERBY_NAME ) {
				// Set our query's meta_key to the product owner.
				$q->set( 'meta_key', '_product_owner' );

				// Set the query to order by our custom field.
				$q->set( 'orderby', 'meta_value' );
			}
		}
	}

	/**
	 * Adds the produt owner to the bulk edit box.
	 * @param  string $column_name
	 * @param  string $post_type
	 * @return string
	 */
	public function bulk_edit( $column_name, $post_type ) {
		if ( $column_name !== self::$COLUMN_NAME || $post_type !== 'product' ) {
			return;
		}

		// Include the template file.
		include SKIOS_PLUGIN_PATH . '/templates/bulk-edit.phtml';
	}

	/**
	 * Adds the product owner to the quick edit boxes.
	 * @param  string $column_name
	 * @param  string $post_type
	 * @return string
	 */
	public function quick_edit( $column_name, $post_type ) {
		if ( $column_name !== self::$COLUMN_NAME || $post_type !== 'product' ) {
			return;
		}

		// Include the template file.
		include SKIOS_PLUGIN_PATH . '/templates/quick-edit.phtml';
	}

}