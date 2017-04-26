<?php

add_action( 'wp_footer', 'collapsable_categories' );

function collapsable_categories() {
  ?>

    <style>
    .widget_product_categories .cat-parent.closed .children {
      display: none;
    }

    .widget_product_categories .cat-parent:before {
      content: "- \f114"
    }

    .widget_product_categories .cat-parent:before {
      cursor: pointer;
    }

    .widget_product_categories .cat-parent:before {
      content: "\f115" !important;
    }

    .widget_product_categories .cat-parent.closed:before {
      content: "\f114" !important;
    }

    </style>
    <script>
      jQuery( document ).ready( function($) {

        $( '.widget_product_categories' ).on( 'click', '.cat-parent', function(e) {

          if (e.target !== this) return;
          
          $(this).toggleClass('closed');
        });

      });
    </script>
  <?php
}
