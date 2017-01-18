<?php
/**
 * SK_DeDU_Product_Fields
 * ======================
 *
 * Adds the tab and fields to the back-end admin UI for
 * products where the user can enter all necessary
 * DeDU fields.
 *
 * @since   0.1
 * @package SK_DeDU
 */

class SK_DeDU_Product_Fields {

	/**
	 * The post meta key name.
	 * @var string
	 */
	private $FIELDS_META_KEY = 'sk_dedu_fields';

	/**
	 * The fields that we are interested in.
	 * @var array
	 */
	private $FIELDS = array(
		'YrkeId',
		'ArendetypId',
		'KategoriId',
		'UnderkategoriId',
		'PrioritetId'
	);

	/**
	 * Registers filters and actions.
	 */
	public function __construct() {
		// Add the DeDU tab to product data tabs.
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_dedu_tab' ), 99, 1 );

		// Add the target action hook.
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_dedu_fields' ) );

		// Save the DeDU data.
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_dedu_data' ) );
	}

	/**
	 * Adds our custom tab to the data tabs in the admin
	 * section for products.
	 * @param  array $tabs
	 * @return array
	 */
	public function add_dedu_tab( $tabs ) {
		$tabs[ 'dedu-tab' ] = array(
			'label'		=> __( 'DeDU', 'sk-dedu' ),
			'target'	=> 'sk_dedu_product_data_tab',
			'class'		=> '', // index 'class' must be defined. Docs don't mention why.
		);

		return $tabs;
	}

	/**
	 * The content for the custom tab.
	 * @return void
	 */
	public function add_dedu_fields() {
		global $woocommerce, $post;
		?>
		<!-- id below must match target registered in above add_my_custom_product_data_tab function -->
		<div id="sk_dedu_product_data_tab" class="panel woocommerce_options_panel">
			<?php
			// Get the saved values.
			$values = get_post_meta( $post->ID, $this->FIELDS_META_KEY, true );

			// Create text inputs for each fields.
			foreach ( $this->FIELDS as $field ) {
				// Check if we have a saved value.
				$value = ( $values && isset( $values[ $field ] ) ) ? $values[ $field ] : '';

				// Create field.
				woocommerce_wp_text_input( array(
					'id'			=> 'sk_dedu[' . $field . ']',
					'wrapper_class'	=> 'show_if_simple',
					'label'			=> $field,
					'description'	=> sprintf( __( 'Fyll i %s.', 'sk-dedu' ), $field ),
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
	public function save_dedu_data( $post_id ) {
		if ( ! empty( $_POST[ 'sk_dedu' ] ) && is_array( $_POST[ 'sk_dedu' ] ) ) {
			$fields = $_POST[ 'sk_dedu' ];

			// Save as postmeta.
			update_post_meta( $post_id, $this->FIELDS_META_KEY, $fields );
		}
	}

}