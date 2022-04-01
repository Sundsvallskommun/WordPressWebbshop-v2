<?php 

/**
 * Unhooks certain parent theme actions.
 * @return void
 */
function remove_parent_theme_actions() {
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
}
add_action( 'after_setup_theme', 'remove_parent_theme_actions' );

/**
 * Adds an option to pages to hide the page title.
 */
include_once __DIR__ . '/functions/hide-page-title.php';

/**
 * Adds the css and javascript needed in order
 * to be able to collapse categories in the widget.
 * @return void
 */
function collapsable_categories() {
	?>

  <style>

		.widget_product_categories ul li:before {
            min-width: 2em;
            text-align: right;
		}

		.widget_product_categories .current-cat > a {
			font-weight: 600 !important;
		}

		.widget_product_categories .cat-parent.closed .children {
			display: none;
		}

		.widget_product_categories .cat-parent:before {
			content: "- \f07c" !important;
		}

		.widget_product_categories .cat-parent.closed:before {
			content: "+ \f07b" !important;
		}

		.widget_product_categories .cat-parent:before {
			cursor: pointer;
		}
	</style>

	<script>
		jQuery( document ).ready( function($) {
			// Collapse all parents.
			$( '.widget_product_categories ul .cat-parent:not(.current-cat-parent, .current-cat)' ).addClass( 'closed' );

			// Make sure all parents of current category are still open.
			$( '.current-cat' ).parents( '.cat-parent' ).removeClass( 'closed' );

			$( '.widget_product_categories' ).on( 'click', '.cat-parent', function(e) {
				if (e.target !== this) return;

				$(this).toggleClass('closed');
			});
		});
	</script>
	<?php
}

add_action( 'wp_footer', 'collapsable_categories' );

/**
 * Adds "Categories" and "products" headings in product archive.
 */
function separate_cat_and_products() {
	?>
	<script type="text/javascript">
		jQuery( document ).ready( function($) {


			var $cats = $('.products > .product-category');

			if ( $cats.length > 0 ) {

				var $catUl = $('<ul class="products"></ul>');
				var $productUl = $('ul.products');

				$cats.detach();
				$cats.appendTo($catUl);
				$catUl.insertBefore($productUl);

				$('<h2>Kategorier</h2>').insertBefore($catUl);
				$('<h2>Produkter</h2>').insertBefore($productUl);

				$catUl.find('.product').each(fixFirstLast);
				$productUl.find('.product').each(fixFirstLast);

				function fixFirstLast(i) {
					$(this).removeClass('first');
					$(this).removeClass('last');

					if (i%3 == 0) { $(this).addClass('first'); }
					if (i%3 == 2) { $(this).addClass('last'); }

				}

			}

		});
	</script>
	<?php
}

add_action( 'wp_footer', 'separate_cat_and_products' );

/**
 * Adds a tooltip for products in product archive if the product has a short
 * description.
 */
add_action( 'woocommerce_before_shop_loop_item', function() {

	if (is_cart()) {
		return false;
	}

	global $product;

	$short_description = $product->get_short_description();

	$has_description = mb_strlen( $short_description ) > 0;

	if ( $has_description ) {
?>

<div class="product-tooltip">
	<div>
		<h2><?php echo $product->get_title(); ?></h2>
		<?php echo $short_description; ?>
	</div>
</div>

	<?php
	}

});

function sk_product_tooltip_script() {
?>
	<script>
jQuery(document).ready(function($) {

	var $tooltips = $('.product-tooltip');

	function alignToolTips() {

		$tooltips.each(function() {

			var img = $(this).next('img');
			var imgHeight = img.height();
			var imgWidth = img.width();

			$(this).css({
				'top' : 0,
				'width' : imgWidth,
				'height' : imgHeight,
				'left' : '50%',
				'transform': 'translateX(-50%)'
			})
		});

	}

	$(window).resize(alignToolTips);


	alignToolTips();

});
	</script>
<?php
}

add_action( 'wp_footer', 'sk_product_tooltip_script' );

/**
 * Override inline styles from theme customization to prevent it from
 * being changed.
 */
include_once __DIR__.'/inline-styles.php';

/**
 * Change the translation of the upsells title on the product page.
 */
function translate_upsells_title( $translated ) {
   $translated = str_ireplace( 'Du gillar kanske också&hellip;', 'Du kanske behöver', $translated );
   return $translated;
}

add_filter( 'gettext', 'translate_upsells_title' );

/**
 * Add navigation icons (my account and support) to site header.
 */
function sk_navigation_icons() {

	echo '<div class="sk-navigation-icons">';
		echo get_icon_link( 'Mitt konto', get_permalink( get_option('woocommerce_myaccount_page_id') ), 'user' );

		$support_url = get_theme_mod( 'sk_support_url' );
		if ( $support_url ) {
			echo get_icon_link( 'Support', $support_url, 'headset' );
		}
	echo '</div>';
}
add_action('storefront_header', 'sk_navigation_icons', 41 );

/**
 * Get link with icon.
 */
function get_icon_link( $text, $url, $icon ) {
	return sprintf(
		'<a class="icon-link" href="%s"><span class="icon"><i class="fas fa-%s"></i></span>%s</a>',
		$url,
		$icon,
		$text
	);
}

/**
 * Add support-url setting to the customizer.
 * The value is used in the site header.
 */
function support_url_setting( $wp_customize ) {
	$wp_customize->add_setting( 'sk_support_url' );
	$wp_customize->add_control( 'sk_support_url', array(
		'label'    => __( 'Support-url', '' ),
		'section'  => 'header_image',
		'settings' => 'sk_support_url',
		'type'     => 'text'
	));
}

add_action( 'customize_register', 'support_url_setting' );