<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include_once __DIR__ . '/includes/product-owner.php';

/**
 * SKIOS Gateway class
 */
class SKIOS_Gateway extends WC_Payment_Gateway {

	function __construct() {
		$this->id                 = 'skios';
		$this->icon               = '';
		$this->has_fields         = false;
		$this->title              = 'TITLE';
		$this->method_title       = 'SK Internal Order System';
		$this->method_description = 'Varje produkt tillhör en ägare och när ordern läggs går ett mejl iväg till respektive ägare om beställningen.';

		$this->init_form_fields();
		$this->init_settings();

		$this->title              = $this->get_option( 'title' );

		// Enqueues JS.
		$this->enqueue_scripts();

		// Localize JS.
		$this->localize_scripts();

		// Enqueue CSS.
		$this->enqueue_styles();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	function init_form_fields() {

		$admin_email = get_bloginfo( 'admin_email');

		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Internal Order', 'skios' ),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default' => __( 'Internbeställning', 'woocommerce' ),
				'desc_tip'      => true,
			)
		);

		// Add the product owner title.
		$this->form_fields[ 'product_owners_title' ] = array(
			'title'			=> __( 'Produktägare', 'skios' ),
			'type'			=> 'title',
			'description'	=> '',
		);

		// Add existing product owners.
		$this->form_fields[ 'product_owners_html' ] = array(
			'title'	=> __( 'Ägare', 'skios' ),
			'type'	=> 'product_owners',
		);

		// Add button.
		$this->form_fields[ 'add_product_owner_btn'] = array(
			'title'			=> __( 'Lägg till fler produktägare', 'skios' ),
			'id'			=> 'skios_add_product_owner',
			'class'			=> 'action-button button-secondary',
			'type'			=> 'add_button',
			'label'			=> __( 'Lägg till', 'skios' ),
			'description'	=> __( 'Lägg till fler produktägare.', 'skios' )
		);

	}

	/**
	 * Enqueues our JavaScript on necessary pages.
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_register_script( 'skios-admin-gateway-js', SKIOS_PLUGIN_URL . '/assets/js/gateway_settings.js', array( 'jquery' ), SKIOS_VERSION, true );

		if ( is_admin()
			&& ( ! empty( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] === 'wc-settings' )
			&& ( ! empty( $_REQUEST[ 'tab' ] ) && $_REQUEST[ 'tab' ] === 'checkout' )
			&& ( !empty( $_REQUEST[ 'section' ] ) && $_REQUEST[ 'section' ] === 'skios' ) ) {
			wp_enqueue_script( 'skios-admin-gateway-js' );
		}
	}

	/**
	 * Localizes all necessary variables and strings
	 * to be used in the front-end JavaScript.
	 * @return void
	 */
	public function localize_scripts() {
		$localized = array(
			'i10n'	=> array(
				'new_product_owner_label'				=> __( 'Ny produktägare', 'skios' ),
				'new_product_owner_label_placeholder'	=> __( 'Benämning', 'skios' ),
				'new_product_owner_email_placeholder'	=> __( 'E-postadress', 'skios' ),
				'delete_product_owner_prompt'			=> __( 'Är du säker på att du vill ta bort %% som produktägare?', 'skios' ),
				'empty_product_owners_not_allowed'		=> __( 'Det måste finnas åtminstone en produktägare!', 'skios' )
			),
		);
		wp_localize_script( 'skios-admin-gateway-js', 'skios', $localized );
	}

	/**
	 * Enqueues our css on necessary pages.
	 * @return void
	 */
	public function enqueue_styles() {
		wp_register_style( 'skios-admin-gateway-css', SKIOS_PLUGIN_URL . '/assets/css/gateway_settings.css', SKIOS_VERSION );

		if ( is_admin() ) {
			wp_enqueue_style( 'skios-admin-gateway-css' );
		}
	}

	function process_payment( $order_id ) {

		global $woocommerce;

		$order = new WC_Order( $order_id );

		$owners = skios_get_product_owners();

		$items = $order->get_items();

		$sorted_items = array();

		foreach ($items as $item) {

			$product_id = $item['product_id'];

			$owner_id = get_post_meta( $product_id, '_product_owner', true);

			if ( !$owner_id || empty($owner_id) ||  false == skios_get_product_owner_by_id( $owner_id ) ) { 
				// 0 means no owner id is set for product.
				$sorted_items[0][] = $item;
			} else {
				$sorted_items[$owner_id][] = $item;
			}

		}

		skios_handle_order_notifications( $order, $sorted_items );

		return false;

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('wc-internal-order', __( 'Orderinfo skickat till produkternas ägare.', 'skios' ));

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}

	/**
	 * Generates the HTML for an existing product owner.
	 * @param  mixed $key
	 * @param  mixed $data
	 * @return string
	 */
	public function generate_product_owners_html( $key, $data ) {
		$product_owners = skios_get_product_owners();

		$field    = $this->plugin_id . $this->id . '_' . $key;
		$defaults = array(
			'class'             => 'button-secondary',
			'css'               => '',
			'custom_attributes' => array(),
			'desc_tip'          => false,
			'description'       => '',
			'title'             => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<table>
					<thead>
						<th><?php _e( 'Namn', 'skios' ); ?></th>
						<th><?php _e( 'E-post', 'skios' ); ?></th>
						<th></th>
					</thead>

					<tbody class="product-owners-container">
						<?php $c = 0; foreach ( $product_owners as $product_owner ) : $product_owner = (object) $product_owner; ?>
							<tr class="product-owner">
								<td class="label">
									<span class="skios-product-owner label"><?php echo $product_owner->label; ?></span>

									<input
										class=""
										type="text"
										name="product_owners[<?php echo $c; ?>][label]"
										id="product_owners[<?php echo $c; ?>][label]"
										value="<?php echo $product_owner->label; ?>"
										placeholder="<?php _e( 'Benämning', 'skios' ); ?>"
									>
								</td>

								<td class="email">
									<span class="skios-product-owner email"><?php echo $product_owner->email; ?></span>

									<input
										class=""
										type="email"
										name="product_owners[<?php echo $c; ?>][email]"
										id="product_owners[<?php echo $c; ?>][email]"
										value="<?php echo $product_owner->email; ?>"
										placeholder="<?php _e( 'E-postadress', 'skios' ); ?>"
									>
								</td>

								<td class="actions">
									<span class="dashicons dashicons-edit edit"></span>
									<span class="dashicons dashicons-trash remove"></span>

									<input type="hidden" name="product_owners[<?php echo $c; ?>][id]" value="<?php echo $product_owner->id; ?>">
								</td>
							</tr>
						<?php $c++; endforeach; ?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate Button HTML.
	 *
	 * @param mixed $key
	 * @param mixed $data
	 * @since 0.1
	 * @return string
	 */
	public function generate_add_button_html( $key, $data ) {
		$field    = $this->plugin_id . $this->id . '_' . $key;
		$defaults = array(
			'class'             => 'button-secondary',
			'css'               => '',
			'custom_attributes' => array(),
			'desc_tip'          => false,
			'description'       => '',
			'title'             => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top" class="add-product-owner-row">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $data[ 'id' ] ); ?>" id="<?php echo esc_attr( $data[ 'id' ] ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['label'] ); ?></button>
					<div class="loader">
						<div class="spinner"></div>
						<div class="indicator"></div>
					</div>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the POSTed data, to be used to save the settings.
	 * 
	 * Overloaded from WC_Settings_API.
	 * 
	 * @return array
	 */
	public function get_post_data() {
		$data = array();
		if ( ! empty( $this->data ) && is_array( $this->data ) ) {
			 $data = $this->data;
		}
		$data = $_POST;

		// Remove certain stuff.
		if ( isset( $data[ 'product_owners_title' ] ) ) {
			unset( $data[ 'product_owners_title' ] );
		}

		if ( isset( $data[ 'product_owners_html' ] ) ) {
			unset( $data[ 'product_owners_html' ] );
		}

		if ( isset( $data[ 'add_product_owner_btn' ] ) ) {
			unset( $data[ 'add_product_owner_btn' ] );
		}

		// Also make sure that product owners is an array since
		// we'll try to loop over it later.
		if ( empty( $data[ 'product_owners' ] ) ) {
			$data[ 'product_owners' ] = array();
		}

		// Return data.
		return $data;
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * Overloaded from WC_Settings_API.
	 * 
	 * @return bool was anything saved?
	 */
	public function process_admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		// Normal settings behavior.
		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

		// Check for product owner data.
		if ( ! empty( $post_data[ 'product_owners' ] ) ) {
			foreach ( $post_data[ 'product_owners' ] as $key => $product_owner ) {
				$product_owner = (object) $product_owner;

				// Check if it exists.
				if ( skios_get_product_owner_by_id( $product_owner->id ) ) {
					// Update existing.
					$post_data[ 'product_owners' ][ $key ] = skios_update_product_owner(
						(int) $product_owner->id,
						array(
							'label'		=> $product_owner->label,
							'email'		=> $product_owner->email,
						)
					);
				} else {
					// Create new.
					$post_data[ 'product_owners' ][ $key ] = skios_insert_product_owner( array(
						'label'		=> $product_owner->label,
						'email'		=> $product_owner->email,
					) );
				}
			}

			// Get settings.
			// Compare the saved array of product owners with the one
			// that's posted so we can determine if we should remove any.
			$curr_settings = get_option( 'woocommerce_skios_settings' );
			$old_product_owners = $curr_settings[ 'product_owners' ];
			foreach ( $old_product_owners as $key => $old_po ) {
				$found = false;
				foreach ( $post_data[ 'product_owners' ] as $po ) {
					if ( $old_po[ 'id' ] === $po[ 'id' ] ) {
						$found = true;
					}
				}

				if ( ! $found ) {
					unset( $curr_settings[ 'product_owners' ][ $key ] );
				}
			}

			$this->settings = array_merge( $this->settings, $curr_settings );
		}

		// Save all settings.
		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ) );
	}

}
