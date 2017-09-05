<?php

/**
 * Adds the css and javascript needed in order
 * to be able to collapse categories in the widget.
 * @return void
 */
function collapsable_categories() {
	?>

  <style>

		.widget_product_categories ul li:before {
      min-width: 1.74em;
      text-align: right;
		}

		.widget_product_categories .current-cat > a {
			font-weight: 600 !important;
		}

		.widget_product_categories .cat-parent.closed .children {
			display: none;
		}

		.widget_product_categories .cat-parent:before {
			content: "- \f115" !important;
		}

		.widget_product_categories .cat-parent.closed:before {
			content: "+ \f114" !important;
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
