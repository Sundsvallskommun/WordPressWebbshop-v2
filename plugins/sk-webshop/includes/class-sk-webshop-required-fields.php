<?php
/**
 * SK_Webshop_Required_Fields
 * ==========================
 *
 * Set required fields based on the products in cart.
 */

class SK_Webshop_Required_Fields {

	/**
	 * The post meta key name.
	 * @var string
	 */
	private static $fields_meta_key = 'sk_checkout_tab_fields';

	/**
	 * The fields that we are interested in.
	 * @var array
	 */
	private $fields = array(
		'billing_organization' => array(
			'label' => 'Förvaltning/bolag',
		),
		'billing_department' => array(
			'label' => 'Arbetsplats, avdelning, rum',
		),
		'billing_address_1' => array(
			'label' => 'Gatuadress',
		),
		'billing_postcode' => array(
			'label' => 'Postnummer',
		),
		'billing_city' => array(
			'label' => 'Ort',
		),
		'billing_reference_number' => array(
			'label' => 'Referensnummer',
		),
		'billing_responsibility_number' => array(
			'label' => 'Ansvarsnummer',
		),
		'billing_occupation_number' => array(
			'label' => 'Verksamhetsnummer',
		),
		'billing_activity_number' => array(
			'label' => 'Aktivitetsnummer',
		),
		'billing_project_number' => array(
			'label' => 'Projektnummer',
		),
		'billing_object_number' => array(
			'label' => 'Objektsnummer',
		),
	);

	/**
	 * The fields unchecked values, this is mostly so we easily can save a
	 * correct array when all checkboxes are unticked.
	 * @var array
	 */
	private $fields_unchecked = array(
		'billing_address_1'  => 'no',
		'billing_department' => 'no',
	);

	/**
	 * Filters and hooks.
	 */
	public function __construct() {
		// Change fields.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'change_required' ), 9999999, 1 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'change_required' ), 9999999, 1 );

		// Add the checkout tab to product data tabs.
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_checkout_tab' ), 99, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_checkout_tab_fields' ) );
		add_action( 'save_post', array( $this, 'save_checkout_tab_fields' ) );
	}

	/**
	 * Adds our custom tab to the data tabs in the admin
	 * section for products.
	 * @param  array $tabs
	 * @return array
	 */
	public function add_checkout_tab( $tabs ) {
		$tabs['sk-checkout-tab'] = array(
			'label'  => __( 'Kassavy', 'sk-shop' ),
			'target' => 'sk_checkout_product_data_tab',
			'class'  => '', // index 'class' must be defined. Docs don't mention why.
		);

		return $tabs;
	}

	/**
	 * The content for the custom tab.
	 * @return void
	 */
	public function add_checkout_tab_fields() {
		global $woocommerce, $post;
		?>
		<!-- id below must match target registered in above add_my_custom_product_data_tab function -->
		<div id="sk_checkout_product_data_tab" class="panel woocommerce_options_panel">

			<h3 style="padding: 5px 20px 5px 10px;">Frivilliga fält i kassan</h3>

			<p style="padding: 5px 20px 5px 10px;">Markera de fält som inte ska krävas av beställaren i kassan.</p>

			<?php
			// Get the saved values.
			$values = get_post_meta( $post->ID, self::$fields_meta_key, true );

			// Create text inputs for each fields.
			foreach ( $this->fields as $field => $field_data ) {
				// Check if we have a saved value.
				$value = ( $values && ! empty( $values[ $field ] ) ) ? $values[ $field ] : 'no';

				// Create field.
				woocommerce_wp_checkbox( array(
					'id'            => self::$fields_meta_key . '[' . $field . ']',
					'wrapper_class' => 'show_if_simple',
					'label'         => $field_data['label'],
					'description'   => 'Inaktivera fältet ' . $field_data['label'],
					'default'       => 'no',
					'value'         => $value,
					'desc_tip'      => false,
				) );
			}
			?>
		</div>
		<?php
	}


	public function save_checkout_tab_fields( $post_id ) {
		// Don't save unless it's a product.
		if ( get_post_type( $post_id ) !== 'product' ) {
			return false;
		}

		// Don't save revisions or autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Save values or default value
		$fields = ( ! empty( $_REQUEST[ self::$fields_meta_key ] ) ) ? $_REQUEST[ self::$fields_meta_key ] : '';
		if ( ! empty( $fields ) ) {
			update_post_meta( $post_id, self::$fields_meta_key, $fields );
		} else {
			update_post_meta( $post_id, self::$fields_meta_key, $this->fields_unchecked );
		}
	}

	/**
	 * Remove required for fields where all products in cart has disabled them.
	 * @param  array $fields
	 * @return array
	 */
	public function change_required( $fields ) {
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();

		if ( empty( $items ) ) {
			return $fields;
		}

		$_fields = $this->fields;

		foreach ( $items as $item => $values ) {
			$product_id = $values['product_id'];
			$opt_fields = (array) get_post_meta( $product_id, self::$fields_meta_key, true );

			$opt_fields = array_filter( $opt_fields, function( $v ) {
				return 'yes' === $v;
			});

			foreach ( $_fields as $field => $field_data ) {
				$shouldbe_required = array_key_exists( $field, $opt_fields ) ? false : true;

				$_fields[ $field ]['required'] = $shouldbe_required;
			}
		}

		if ( isset( $fields['billing'] ) ) {
			foreach ( $fields['billing'] as $field => $value ) {
				if ( isset( $_fields[ $field ] ) ) {
					$required = $_fields[ $field ]['required'];
					$fields['billing'][ $field ]['required'] = $required;

					if ( ! $required ) {
						unset( $fields['billing'][ $field ] );
					}
				}
			}
		} else {
			foreach ( $fields as $field => $value ) {
				$_field = substr( $field, 0, 8 ) === 'billing_' ? $field : 'billing_' . $field;

				if ( isset( $_fields[ $_field ] ) ) {
					$required = $_fields[ $_field ]['required'];

					if ( ! $required ) {
						$fields[ $field ]['required'] = $required;

						if ( ! $required ) {
							unset( $fields[ $field ] );
						}
					}
				}
			}
		}

		// Return new fields.
		return $fields;
	}

}
