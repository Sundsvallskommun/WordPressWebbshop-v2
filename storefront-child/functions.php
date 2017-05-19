<?php

/**
 * Adds the css and javascript needed in order
 * to be able to collapse categories in the widget.
 * @return void
 */
function collapsable_categories() {
	?>

	<style>
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
