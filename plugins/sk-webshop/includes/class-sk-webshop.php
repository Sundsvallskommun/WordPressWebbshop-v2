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
		// Init Timber.
		$this->init_timber();

		// Add the order action item for re-trying our gateway.
		add_filter( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_action' ) );

		// Add the action for re-trying our gateway.
		add_action( 'woocommerce_order_action_skios_retry_gateway', array( $this, 'retry_order_through_skios' ) );

		// Add SKU to products if it's missing when saving.
		add_action( 'save_post', array( $this, 'add_sku_if_missing' ), 10, 3 );

		add_filter( 'woocommerce_variable_free_price_html',  array( $this, 'hide_free_price_notice' ) );
		add_filter( 'woocommerce_free_price_html',           array( $this, 'hide_free_price_notice' ) );
    add_filter( 'woocommerce_variation_free_price_html', array( $this, 'hide_free_price_notice' ) );

		add_action( 'wp_footer', array( $this, 'hide_gravityforms_diff' ) );
		add_action( 'wp_footer', array( $this, 'gravity_form_description_linebreaks' ) );

		add_filter('tiny_mce_before_init', array(&$this, 'tiny_mce_settings'));

		// Filter the description on my address on my account.
		add_filter( 'woocommerce_my_account_my_address_description', array( $this, 'change_my_address_description' ) );

		// Filter the address titles on my account.
		add_filter( 'woocommerce_my_account_get_addresses', array( $this, 'change_address_titles' ) );

		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'disable_add_to_cart_custom_product_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'disable_add_to_cart_custom_product_field_save' ) );

		// Disable add to cart link in loop if setting is checked
		add_filter('woocommerce_loop_add_to_cart_link', function($link, $product) {
			$disabled = 'yes' == get_post_meta( $product->get_id(), '_add_to_cart_disabled', true );
			if (!$disabled) return $link;
			return null;
		}, 10, 2);

		// Disable add to cart link on product if setting is checked
		add_action('wp', function() {
			global $post;
			$disabled = 'yes' == get_post_meta( $post->ID, '_add_to_cart_disabled', true );

			if (!$disabled) return;

			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

		});

    /**
     * Change text on thank-you page when order is completed
     */
    add_filter('woocommerce_thankyou_order_received_text', function($text){
      return 'Tack för din beställning! Ett ärendenummer kommer att skickas till din e-post inom kort.';
    }, 10, 1 );

		add_action('parse_request', array( $this, 'forcelogin' ), 10);

		// Include all class files.
		$this->includes();

		// Init classes.
		$this->init_classes();
	}

	/**
	 * Inits Timber.
	 * @return void
	 */
	private function init_timber() {
		$timber = new \Timber\Timber();
		Timber::$dirname = [ 'views' ];
	}

	/**
	 * Adds our action for re-trying the order
	 * through the gateway.
	 * @param array $actions
	 */
	public function add_order_meta_box_action( $actions ) {
		$actions['skios_retry_gateway'] = __( 'Skicka order igen', 'skios' );
		return $actions;
	}

	/**
	 * Re-tries the order through SKIOS gateway.
	 * @param  WC_Order $order
	 * @return void
	 */
	public function retry_order_through_skios( $order ) {
		if ( 'skios' === $order->get_payment_method() && class_exists( 'SKIOS_Gateway' ) ) {
			// Get an instance of the gateway.
			$gateway = new SKIOS_Gateway();

			// Send the order through the gateway manually.
			$result = $gateway->process_payment( $order->get_id() );
		}
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
	 * Include all class files.
	 * @return void
	 */
	private function includes() {
		include __DIR__ . '/class-sk-webshop-unittype.php';
		include __DIR__ . '/class-sk-webshop-checkout-fields.php';
		include __DIR__ . '/class-sk-webshop-required-fields.php';
		include __DIR__ . '/class-sk-gf-privacy.php';
		include __DIR__ . '/class-sk-related-products.php';
	}

	/**
	 * Inits all classes.
	 * @return void
	 */
	private function init_classes() {
		$this->taxonomies = new SK_Webshop_Unittype();
		$this->checkout_fields = new SK_Webshop_Checkout_Fields();
		$this->required_fields = new SK_Webshop_Required_Fields();
		$this->gf_privacy = new SK_GF_Privacy();
		$this->related_products = new SK_Related_Products();
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
		if ( empty( $_REQUEST[ '_sku' ] ) && ( empty( $_REQUEST[ 'bulk_edit' ] ) && empty( $_REQUEST[ 'woocommerce_quick_edit' ] ) ) ) {
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

	/**
	* Show 0kr instead of "Free" (gratis)
	*/
	function hide_free_price_notice( $price ) {
		return wc_price(0);
  }

	/**
	 * Hide "+10,00 Kr" and similar next to GF-option
	 */
	function hide_gravityforms_diff() {
	?>
		<style>
			.ginput_price {
				display: none;
			}
		</style>
	<?php 
  }

	function gravity_form_description_linebreaks() {
	?>
		<style>
			.gfield_description {
        white-space: pre-line;
			}
		</style>
	<?php 
	}



	private function get_tinymce_toolbar_items($toolbar = 1) {
		if ( 1 === intval($toolbar) ) return 'formatselect, bold, link, unlink, blockquote, bullist, numlist, table, spellchecker, eservice_button, youtube_button, sk_collapse, rml_folder';
		if ( 2 === intval($toolbar) ) return 'pastetext, removeformat, charmap, undo, redo';
		return false;
	}

	/**
	* TinyMCE-settings
	*
	* @author Johan Linder <johan@flatmate.se>
	*/
	function tiny_mce_settings($settings) {
		/**
		* Select what to be shown in tinymce toolbars.
		*
		* Original settings:
		*
		* toolbar1 = 'bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv,eservice_button'
		* toolbar2 = 'formatselect,underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
		*/
		$settings['toolbar1'] = $this->get_tinymce_toolbar_items(1);
		$settings['toolbar2'] = $this->get_tinymce_toolbar_items(2);
		/**
		* Always show toolbar 2
		*/
		$settings['wordpress_adv_hidden'] = false;
		/**
		* Block formats to show in editor dropdown. We remove h1 as we set page
		* title to h1 in the theme.
		*/
		$settings['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;';
		return $settings;
	}

	/**
	 * Changes the description of addresses.
	 * @param  string $description
	 * @return string
	 */
	public function change_my_address_description( $description ) {
		return __( 'Följande uppgifter kommer att användas i kassan.', 'sk-webshop' );
	}

	/**
	 * Changes the titles of the address endpoints.
	 * @param  array $addresses
	 * @return array
	 */
	public function change_address_titles( $addresses ) {
		return array(
			'billing'	=> __( 'Faktureringsuppgifter', 'sk-webshop' ),
			'shipping'	=> __( 'Leveransuppgifter', 'sk-webshop' ),
		);
	}

	/**
	 * Add field to disable "add to cart" for product
	 * */
	public function disable_add_to_cart_custom_product_field() {
		global $woocommerce, $post;

		echo '<div class="options_group">';

		woocommerce_wp_checkbox(
			array(
				'id'            => '_add_to_cart_disabled',
				'wrapper_class' => '',
				'label'         => __('Ej köpbar', 'skpp' ),
				'description'   => __( 'Gör så produkten inte kan köpas, men utan att det står att den är slut i lager.', 'skpp' )
			)
		);

		echo '</div>';

	}

	/**
	 * Adds an entry to error_log() if the provided
	 * $level matches current error_reporting() level.
	 * @param  string  $entry
	 * @param  integer $level
	 * @return void
	 */
	public function log( $entry, $level = E_WARNING ) {
		$current_error_level = error_reporting();

		if ( $level <= $current_error_level ) {
			error_log( $entry );
		}
	}


	/**
	 * Save field
	 */
	public function disable_add_to_cart_custom_product_field_save($post_id) {
		$woocommerce_checkbox = isset( $_POST['_add_to_cart_disabled'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_add_to_cart_disabled', $woocommerce_checkbox );
  }

	function getUrl() {
		$url  = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
		$url .= '://' . $_SERVER['SERVER_NAME'];
		$url .= in_array( $_SERVER['SERVER_PORT'], array('80', '443') ) ? '' : ':' . $_SERVER['SERVER_PORT'];
		$url .= $_SERVER['REQUEST_URI'];
		return $url;
	}

	function forcelogin() {

		if (array_key_exists('use_sso', $_GET)) {
			return;
		}
		// Prevent infinite loop when used with SAML-plugin.
		if (array_key_exists('SAMLRequest', $_GET) || array_key_exists('SAMLResponse', $_GET)) {
			return;
		}
		if (array_key_exists('SAMLRequest', $_POST) || array_key_exists('SAMLResponse', $_POST)) {
			return;
		}


		if( !is_user_logged_in() ) {
			$url = $this->getUrl();
			$whitelist = apply_filters('forcelogin_whitelist', array());
			$redirect_url = apply_filters('forcelogin_redirect', $url);
			if( preg_replace('/\?.*/', '', $url) != preg_replace('/\?.*/', '', wp_login_url()) && !in_array($url, $whitelist) ) {
				wp_safe_redirect( wp_login_url( $redirect_url ), 302 ); exit();
			}
		}
	}

}
