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
		add_action( 'save_post', array( $this, 'add_sku_if_missing' ), 10, 3 );
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