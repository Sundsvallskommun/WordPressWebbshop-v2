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
	}

	/**
	 * Function that returns a singleton instance
	 * of the class.
	 * @return SK_Webshop
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Generates a SKU.
	 * @param  WC_Product|post_id $product
	 * @return void
	 */
	public function generate_sku( $product ) {
		if ( is_int( $product ) ) {
			$post_title = get_the_title( $product );
		} else if ( is_a( $product, 'WC_Product' ) ) {
			$post_title = $product->post->post_title;
		} else {
			throw new WP_Error( __( 'Parameter must be instance of WC_Product or integer.', 'sk-webshop' ) );
		}
		return sanitize_title( $post_title );
	}

}