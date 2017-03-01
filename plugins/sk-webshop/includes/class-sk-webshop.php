<?php
/**
 * SK_Webshop
 * ==========
 *
 * Main plugin file.
 *
 * Register hooks and filters.
 *
 * @since   0.1
 * @package SK_Webshop
 */

class SK_Webshop {

	/**
	 * The class instance.
	 * @var SK_Webshop|null
	 */
	private static $instance = null;

	/**
	 * Inits the class.
	 */
	public function __construct() {
		// Register unit type taxonomy.
		add_action( 'init', array( $this, 'add_unit_type_taxonomy' ) );

		// Add SKU to products if it's missing when saving.
		add_action( 'save_post', array( $this, 'add_sku_if_missing' ), 10, 3 );

		// Display unit type options on product admin screen.
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_unit_type_field' ) );

		// Save unit type option on product admin screen.
		add_action( 'save_post', array( $this, 'save_unit_type_field' ) );
	}

	/**
	 * Function that returns a singleton instance
	 * of the class.
	 * @return SK_Webshop
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers a custom taxonomy for the price per
	 * product.
	 * @return void
	 */
	public function add_unit_type_taxonomy() {
		$labels = array(
			'name'							=> __( 'Enhetstyper', 'sk-webshop' ),
			'singular_name'					=> __( 'Enhetstyp', 'sk-webshop' ),
			'menu_name'						=> __( 'Enhetstyper', 'sk-webshop' ),
			'all_items'						=> __( 'Alla enhetstyper', 'sk-webshop' ),
			'parent_item'					=> __( 'Förälderenhetstyp', 'sk-webshop' ),
			'parent_item_colon'				=> __( 'Förälderenhetstyp:', 'sk-webshop' ),
			'new_item_name'					=> __( 'Ny enhetstyp', 'sk-webshop' ),
			'add_new_item'					=> __( 'Lägg till ny enhetstyp', 'sk-webshop' ),
			'edit_item'						=> __( 'Redigera enhetstyp', 'sk-webshop' ),
			'update_item'					=> __( 'Uppdatera enhetstyp', 'sk-webshop' ),
			'separate_items_with_commas'	=> __( 'Separera enhetstyper med kommatecken', 'sk-webshop' ),
			'search_items'					=> __( 'Sök enhetstyper', 'sk-webshop' ),
			'add_or_remove_items'			=> __( 'Lägg till eller ta bort Enhetstyper', 'sk-webshop' ),
			'choose_from_most_used'			=> __( 'Välja from de mest använda Enhetstyperna', 'sk-webshop' ),
		);
		$args = array(
			'labels'						=> $labels,
			'hierarchical'					=> true,
			'public'						=> false,
			'show_ui'						=> true,
			'show_admin_column'				=> true,
			'show_in_nav_menus'				=> true,
			'show_tagcloud'					=> true,
		);
		register_taxonomy( 'product_unit_type', 'product', $args );
		register_taxonomy_for_object_type( 'product_unit_type', 'product' );
	}

	/**
	 * Checks if post has a SKU and generates one if
	 * it doesn't.
	 * @param  integer $post_id
	 * @return void
	 */
	public function add_sku_if_missing( $post_id, $post, $update ) {
		// Don't save unless it's a product.
		if ( get_post_type( $post_id ) !== 'product' ) {
			return false;
		}

		// Don't save revisions or autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Save an auto-generated SKU if one doesn't exist.
		if ( empty( $_REQUEST[ '_sku' ] ) ) {
			update_post_meta( $post_id, '_sku', $this->generate_sku( $post ) );
		}
	}

	/**
	 * Adds a select element with all unit types as options.
	 * @return void
	 */
	public function add_unit_type_field() {
		$unit_types = get_terms( array(
			'taxonomy'		=> 'product_unit_type',
			'hide_empty'	=> false,
		) );

		// Check if this product already has one selected.
		global $post;
		if ( $selected_unit_type = get_the_terms( $post, 'product_unit_type' ) ) {
			$selected_unit_type = reset( $selected_unit_type );
		}

		echo '<div class="options_group">';

			echo '<p class="form-field _unit_type_field"><label for="_unit_type">' . __( 'Enhetstyp', 'sk-webshop' ) . '</label><select id="_unit_type" name="_unit_type" class="short select">';

				echo '<option value="0">' . __( '---Ingen vald---', 'sk-webshop' ) . '</option>';

				foreach ( $unit_types as $term ) {
					echo '<option value="' . $term->term_id . '" ' . selected( $term->term_id, $selected_unit_type->term_id, false ) . '>' . $term->name . '</option>';
				}

			echo '</select></p>';

		echo '</div>';
	}

	/**
	 * Save the unit type on post_save.
	 * @param  integer $post_id
	 * @return void
	 */
	public function save_unit_type_field( $post_id ) {
		// Don't save unless it's a product.
		if ( get_post_type( $post_id ) !== 'product' ) {
			return false;
		}

		// Don't save revisions or autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Check if a unit type was provided.
		if ( ! empty( $_POST[ '_unit_type' ] ) ) {
			wp_set_object_terms( $post_id, (int) $_POST[ '_unit_type' ], 'product_unit_type' );
		} else {
			wp_delete_object_term_relationships( $post_id, 'product_unit_type' );
		}
	}

	/**
	 * Generates a SKU.
	 * @param  WC_Product|post_id $product
	 * @return void
	 */
	public function generate_sku( $product ) {
		if ( is_int( $product ) ) {
			$post_id = $product;
		} else if ( is_a( $product, 'WC_Product' ) ) {
			$post_id = $product->id;
		} else if ( is_a( $product, 'WP_Post' ) ) {
			$post_id = $product->ID;
		} else {
			throw new WP_Error( __( 'Parameter must be instance of WC_Product, WP_Post or integer.', 'sk-webshop' ) );
		}
		return sprintf( 'SK-ART-%d', $post_id );
	}

	/**
	 * Returns an array of all response headers
	 * from a cURL response.
	 * @param  string $response
	 * @return array
	 */
	public function get_headers_from_curl( $response ) {

		/**
		 * Split the headers text by newline and then process
		 * each part individually and make sure that they are
		 * a valid header and then add them to the array.
		 */

		$headers = array();
		$response = explode( "\n", $response );
		$headers[ 'Status' ] = $response[ 0 ];
		array_shift( $response );
		foreach ( $response as $part ) {
			if ( ! empty( $part ) && strpos( $part, ':' ) !== false ) {
				$middle = explode( ':', $part );
				$headers[ trim( $middle[ 0 ] ) ] = trim( $middle[ 1 ] );
			}
		}

		// Return all headers.
		return $headers;
	}

}