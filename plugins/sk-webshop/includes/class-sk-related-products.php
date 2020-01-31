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
		if ( isset( $_POST['add-to-cart'] ) ) {
			add_action( 'get_footer', array( $this, 'sk_related_products_reminder_modal' ) );
			wp_enqueue_style( 'related-products', plugin_dir_url( __FILE__ ) . '../assets/related_products.css' );
		}
	}

	public function sk_related_products_reminder_modal() {
		?>

		<div class="sk-modal active sk-related-products-reminder">
			<div>
				<button class="sk-modal__close | js-close">Stäng</button>
				<h2>Lorem ipsum</h2>
				<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Architecto dolore numquam ad! Inventore cupiditate velit temporibus sint, praesentium vel accusamus tempore veritatis distinctio. Quod rem inventore voluptates unde dicta consequuntur?</p>
			</div>
		</div>

		<script>
			var $ = jQuery;
			$('.sk-modal').each( function() {
				var $modal = $(this);

				// Close the modal with the close-button
				$(this).on( 'click', '.js-close', function() {
					$modal.removeClass('active');
				});

				// Close the modal with the ESC-button
				$(document).keyup(function(e){
					if(e.keyCode === 27) {
						$modal.removeClass('active');
					}
				});
			});
		</script>
		<?php

	}

}
