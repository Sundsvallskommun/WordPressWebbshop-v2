<?php
/**
 * SK_POB_Product_Fields
 * ======================
 *
 * Adds the tab and fields to the back-end admin UI for
 * products where the user can enter all necessary
 * POB fields.
 *
 * @since   0.1
 * @package SKIOS
 */

class SK_POB_Product_Fields {

	/**
	 * Singleton instance of class.
	 * @var null|SK_POB
	 */
	private static $instance = null;

	/**
	 * The post meta key name.
	 * @var string
	 */
	private static $FIELDS_META_KEY = 'sk_pob_fields';

	/**
	 * The fields that we are interested in.
	 * @var array
	 */
	private $FIELDS = array(
		'Underkonto' => null,
		'Motpart'    => 115,
	);

	/**
	 * Registers filters and actions.
	 */
	public function __construct() {
		// Add the POB tab to product data tabs.
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_pob_tab' ), 99, 1 );

		// Add the target action hook.
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_pob_fields' ) );

		// Save the POB data.
		add_action( 'save_post', array( $this, 'save_pob_data' ) );
	}

	/**
	 * Returns Singleton instance of class.
	 * @return SK_POB
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns the default fields.
	 * @return array
	 */
	public function get_default_fields() {
		return $this->FIELDS;
	}

	/**
	 * Adds our custom tab to the data tabs in the admin
	 * section for products.
	 * @param  array $tabs
	 * @return array
	 */
	public function add_pob_tab( $tabs ) {
		$tabs[ 'pob-tab' ] = array(
			'label'		=> __( 'POB', 'skios' ),
			'target'	=> 'sk_pob_product_data_tab',
			'class'		=> '', // index 'class' must be defined. Docs don't mention why.
		);

		return $tabs;
	}

	/**
	 * The content for the custom tab.
	 * @return void
	 */
	public function add_pob_fields() {
		global $woocommerce, $post;
		?>
		<!-- id below must match target registered in above add_my_custom_product_data_tab function -->
		<div id="sk_pob_product_data_tab" class="panel woocommerce_options_panel">
			<?php
			// Get the saved values.
			$values = get_post_meta( $post->ID, self::$FIELDS_META_KEY, true );

			// Create text inputs for each fields.
			foreach ( $this->FIELDS as $field => $default_value ) {
				// Check if we have a saved value.
				$value = ( $values && ! empty( $values[ $field ] ) ) ? $values[ $field ] : $default_value;

				// Create field.
				woocommerce_wp_text_input( array(
					'id'			=> self::$FIELDS_META_KEY . '[' . $field . ']',
					'wrapper_class'	=> '',
					'label'			=> $field,
					'description'	=> sprintf( __( 'Fyll i %s.', 'skios' ), $field ),
					'default'		=> '0',
					'value'			=> $value,
					'desc_tip'		=> false,
				) );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Saves the DeDU data.
	 * @param  integer $post_id
	 * @return void
	 */
	public function save_pob_data( $post_id ) {
		// Don't save unless it's a product.
		if ( get_post_type( $post_id ) !== 'product' ) {
			return false;
		}

		// Don't save revisions or autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Always save POB fields on the post.
		// If one is provided, we'll save that,
		// otherwise we'll save the default values.
		$pob_fields = ( ! empty( $_REQUEST[ self::$FIELDS_META_KEY ] ) ) ? $_REQUEST[ self::$FIELDS_META_KEY ] : '';
		if( ! empty( $pob_fields ) ) {
			update_post_meta( $post_id, self::$FIELDS_META_KEY, $pob_fields );
		} else {
			update_post_meta( $post_id, self::$FIELDS_META_KEY, $this->FIELDS );
		}
	}

}