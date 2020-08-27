<?php
add_action( 'wp_footer', 'theme_inline_styles');


function theme_inline_styles() {

  $gronsta1 = "rgb(106, 176, 35)";
  $gronsta2 = "rgb(0, 115, 59)";
  $gronsta3 = "rgb(206, 226, 185)";

  $primary = $gronsta2;
  $primary_hover = '#004f29';
?>

<style>
.main-navigation ul li a,
.site-title a,
ul.menu li a,
.site-branding h1 a,
.site-footer .storefront-handheld-footer-bar a:not(.button),
button.menu-toggle,
button.menu-toggle:hover {
  color: #0a0909;
}

button.menu-toggle,
button.menu-toggle:hover {
  border-color: #0a0909;
}

.main-navigation ul li a:hover,
.main-navigation ul li:hover > a,
.site-title a:hover,
a.cart-contents:hover,
.site-header-cart .widget_shopping_cart a:hover,
.site-header-cart:hover > li > a,
.site-header ul.menu li.current-menu-item > a {
  color: #ffffff;
}

table th {
  background-color: #f8f8f8;
}

table tbody td {
  background-color: #fdfdfd;
}

table tbody tr:nth-child(2n) td {
  background-color: #fbfbfb;
}

.site-header,
.secondary-navigation ul ul,
.main-navigation ul.menu > li.menu-item-has-children:after,
.secondary-navigation ul.menu ul,
.storefront-handheld-footer-bar,
.storefront-handheld-footer-bar ul li > a,
.storefront-handheld-footer-bar ul li.search .site-search,
button.menu-toggle,
button.menu-toggle:hover {
  background-color: #f9f9f9;
}

p.site-description,
.site-header,
.storefront-handheld-footer-bar {
  color: #0a0909;
}

.storefront-handheld-footer-bar ul li.cart .count,
button.menu-toggle:after,
button.menu-toggle:before,
button.menu-toggle span:before {
  background-color: #0a0909;
}

.storefront-handheld-footer-bar ul li.cart .count {
  color: #f9f9f9;
}

.storefront-handheld-footer-bar ul li.cart .count {
  border-color: #f9f9f9;
}

h1, h2, h3, h4, h5, h6 {
  color: #000000;
}

.widget h1 {
  border-bottom-color: #000000;
}

body,
.secondary-navigation a,
.onsale,
.pagination .page-numbers li .page-numbers:not(.current), .woocommerce-pagination .page-numbers li .page-numbers:not(.current) {
  color: #000000;
}

.widget-area .widget a,
.hentry .entry-header .posted-on a,
.hentry .entry-header .byline a {
  color: #323232;
}

a  {
  color: <?php echo $primary; ?>;
}

a:focus,
.button:focus,
.button.alt:focus,
.button.added_to_cart:focus,
.button.wc-forward:focus,
button:focus,
input[type="button"]:focus,
input[type="reset"]:focus,
input[type="submit"]:focus {
  outline-color: <?php echo $primary; ?>;
}

button, input[type="button"], input[type="reset"], input[type="submit"], .button, .added_to_cart, .widget a.button, .site-header-cart .widget_shopping_cart a.button {
  background-color: <?php echo $primary; ?>;
  border-color: <?php echo $primary; ?>;
  color: #ffffff;
}

button:hover, input[type="button"]:hover, input[type="reset"]:hover, input[type="submit"]:hover, .button:hover, .added_to_cart:hover, .widget a.button:hover, .site-header-cart .widget_shopping_cart a.button:hover {
  background-color: <?php echo $primary_hover ?>;
  border-color: <?php echo $primary_hover ?>;
  color: #ffffff;
}

button.alt, input[type="button"].alt, input[type="reset"].alt, input[type="submit"].alt, .button.alt, .added_to_cart.alt, .widget-area .widget a.button.alt, .added_to_cart, .pagination .page-numbers li .page-numbers.current, .woocommerce-pagination .page-numbers li .page-numbers.current, .widget a.button.checkout {
  background-color: #2c2d33;
  border-color: #2c2d33;
  color: #ffffff;
}

button.alt:hover, input[type="button"].alt:hover, input[type="reset"].alt:hover, input[type="submit"].alt:hover, .button.alt:hover, .added_to_cart.alt:hover, .widget-area .widget a.button.alt:hover, .added_to_cart:hover, .widget a.button.checkout:hover {
  background-color: #13141a;
  border-color: #13141a;
  color: #ffffff;
}

.wp-block-button__link {
    background-color: <?php echo $primary; ?> !important;
    color: #ffffff !important;
}

.wp-block-button__link:hover {
    background-color: <?php echo $primary_hover; ?> !important;
    color: #ffffff !important;
}

#comments .comment-list .comment-content .comment-text {
  background-color: #f8f8f8;
}

.site-footer {
  background-color: <?php echo $primary; ?>;
  color: #ffffff;
}

.site-footer a:not(.button) {
  color: #ffffff;
}

.site-footer h1, .site-footer h2, .site-footer h3, .site-footer h4, .site-footer h5, .site-footer h6 {
  color: #ffffff;
}

#order_review,
#payment .payment_methods > li .payment_box {
  background-color: #ffffff;
}

#payment .payment_methods > li {
  background-color: #fafafa;
}

#payment .payment_methods > li:hover {
  background-color: #f5f5f5;
}

@media screen and ( min-width: 768px ) {
  .secondary-navigation ul.menu a:hover {
    color: #232222;
  }

  .secondary-navigation ul.menu a {
    color: #0a0909;
  }

  .site-header-cart .widget_shopping_cart,
  .main-navigation ul.menu ul.sub-menu,
  .main-navigation ul.nav-menu ul.children {
    background-color: #f1f1f1;
  }
}

a.cart-contents,
.site-header-cart .widget_shopping_cart a {
  color: #0a0909;
}

a.cart-contents,
.site-header-cart .widget_shopping_cart a:hover {
  color: #0a0909;
}

table.cart td.product-remove,
table.cart td.actions {
  border-top-color: #ffffff;
}

.woocommerce-tabs ul.tabs li.active a,
ul.products li.product .price,
.onsale,
.widget_search form:before,
.widget_product_search form:before {
  color: #000000;
}

.woocommerce-breadcrumb a,
a.woocommerce-review-link,
.product_meta a {
  color: #323232;
}

.onsale {
  border-color: #000000;
}

.star-rating span:before,
.quantity .plus, .quantity .minus,
p.stars a:hover:after,
p.stars a:after,
.star-rating span:before,
#payment .payment_methods li input[type=radio]:first-child:checked+label:before {
  color: <?php echo $primary; ?>;
}

.widget_price_filter .ui-slider .ui-slider-range,
.widget_price_filter .ui-slider .ui-slider-handle {
  background-color: <?php echo $primary; ?>;
}

.woocommerce-breadcrumb,
#reviews .commentlist li .comment_container {
  background-color: #f8f8f8;
}

.order_details {
  background-color: #f8f8f8;
}

.order_details > li {
  border-bottom: 1px dotted #e3e3e3;
}

.order_details:before,
.order_details:after {
  background: -webkit-linear-gradient(transparent 0,transparent 0),-webkit-linear-gradient(135deg,#f8f8f8 33.33%,transparent 33.33%),-webkit-linear-gradient(45deg,#f8f8f8 33.33%,transparent 33.33%)
}

p.stars a:before,
p.stars a:hover~a:before,
p.stars.selected a.active~a:before {
  color: #000000;
}

p.stars.selected a.active:before,
p.stars:hover a:before,
p.stars.selected a:not(.active):before,
p.stars.selected a.active:before {
  color: <?php echo $primary; ?>;
}

.single-product div.product .woocommerce-product-gallery .woocommerce-product-gallery__trigger {
  background-color: <?php echo $primary; ?>;
  color: #ffffff;
}

.single-product div.product .woocommerce-product-gallery .woocommerce-product-gallery__trigger:hover {
  background-color: <?php echo $primary_hover ?>;
  border-color: <?php echo $primary_hover ?>;
  color: #ffffff;
}

@media screen and ( min-width: 768px ) {
  .site-header-cart .widget_shopping_cart,
  .site-header .product_list_widget li .quantity {
    color: #0a0909;
  }
}

</style>
<?php

}
