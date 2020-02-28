<?php
/**
 * SK_Related_Products
 * ===================
 *
 * @since   20200131
 * @package SK_Webshop
 */

class SK_Related_Products {

	/**
	 * Add our hooks.
	 */
	public function __construct() {
		$this->maybe_load_related_products();

		// Add the metabox for the 'show modal' option.
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_show_modal_field' ) );
		add_action( 'save_post', array( $this, 'save_show_modal_field' ) );

		// Register the customizer option.
		add_action( 'customize_register', array( $this, 'register_customizer_options' ) );
	}

	/**
	 * Based on the current request, we'll figure out
	 * if we should initialize the related products
	 * modal for the front-end.
	 * @return boolean
	 */
	private function should_init() {
		$is_add_to_cart = ( ! empty( $_REQUEST['add-to-cart'] ) );
		if ( $is_add_to_cart ) {
			$product_id = $_REQUEST['add-to-cart'];
			return wp_validate_boolean( get_post_meta( $product_id, '_show_related_modal', true ) );
		}

		return false;
	}

	/**
	 * Only add the actions for loading the
	 * related products on certain conditions.
	 * @return void
	 */
	public function maybe_load_related_products() {
		if ( $this->should_init() ) {
			add_action( 'get_footer', array( $this, 'output_modal' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_modal_styles' ) );
		}
	}

	/**
	 * Enqueues the stylesheet for the modal.
	 * @return void
	 */
	public function enqueue_modal_styles() {
		wp_enqueue_style( 'related-products', plugin_dir_url( __FILE__ ) . '../assets/related_products.css' );
	}

	/**
	 * Outputs a field for setting show modal.
	 * @return void
	 */
	public function add_show_modal_field() {
		// Check if this product already has one selected.
		global $post;

		Timber::render( __DIR__ . '/views/show-modal-field.twig', [
			'checkbox_args' => [
				'id'            => '_show_related_modal',
				'selected'      => wp_validate_boolean( get_post_meta( $post->ID, '_show_related_modal', true ) ),
				'wrapper_class' => 'show_if_simple show_if_variable',
				'label'         => __( 'Relaterade produkter.', 'skw' ),
				'description'   => __( 'Visa påminnelse om att lägga till tillbehör när produkten läggs i varukorg.', 'skw' ),
			],
		] );
	}

	/**
	 * Saves the value of show related modal field.
	 * @param  integer $post_id
	 * @return void
	 */
	public function save_show_modal_field( $post_id ) {
		// Don't save unless it's a product.
		if ( get_post_type( $post_id ) !== 'product' ) {
			return false;
		}

		// Don't save revisions or autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Save values or default value
		$show_modal = ( ! empty( $_REQUEST[ '_show_related_modal' ] ) ) ? $_REQUEST[ '_show_related_modal' ] : '';
		if ( ! empty( $show_modal ) ) {
			update_post_meta( $post_id, '_show_related_modal', $show_modal );
		} else {
			update_post_meta( $post_id, '_show_related_modal', 'false' );
		}
	}

	/**
	 * Outputs the modal for the related products.
	 * @return void
	 */
	public function output_modal() {
		$heading = get_theme_mod( 'related_products_modal_heading' );
		$text    = get_theme_mod( 'related_products_modal_text' );
		Timber::render( __DIR__ . '/views/related-products-modal.twig',
			[
				'heading' => $heading,
				'text'    => $text,
			]
		);
	}

	/**
	 * Register our modal settings with the
	 * WordPress customizer.
	 * @param  WP_Customizer $customizer
	 * @return void
	 */
	public function register_customizer_options( $customizer ) {
		$customizer->add_panel( 'related_products', array(
			'priority'       => 500,
			'theme_supports' => '',
			'title'          => __( 'Relaterade produkter', 'skw' ),
			'description'    => __( 'Ställ in inställningar relaterade till modalen för relaterade produkter.', 'skw' ),
		) );
		$customizer->add_section( 'related_products_section', array(
			'title'    => __( 'Modal', 'skw' ),
			'panel'    => 'related_products',
			'priority' => 10,
		) );
		$customizer->add_setting( 'related_products_modal_heading', array(
			'default'           => 'Relaterade tillbehör',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$customizer->add_control( new WP_Customize_Control(
			$customizer,
			'related_products_modal_heading', array(
				'label'   => __( 'Rubrik', 'skw' ),
				'setting' => 'related_products_modal_heading',
				'type'    => 'text',
				'section' => 'related_products_section',
			)
		) );
		$customizer->add_setting( 'related_products_modal_text', array(
			'default'           => 'Tillbehör som tangentbord, mus, skärm osv ingår ej vid beställning av dator, du hittar några rekommenderade tillbehör längre ner på sidan under "Du kanske behöver".',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$customizer->add_control( new WP_Customize_Control(
			$customizer,
			'related_products_modal_text', array(
				'label'   => __( 'Text', 'skw' ),
				'setting' => 'related_products_modal_text',
				'type'    => 'textarea',
				'section' => 'related_products_section',
			)
		) );
	}

}
