/*global ajaxurl, inlineEditPost, inlineEditL10n, woocommerce_admin */
;(function($) {
	'use strict';

	function init() {
		var $inline_editor = inlineEditPost.edit;
		inlineEditPost.edit = function( id ) {
			// Call old copy.
			$inline_editor.apply( this, arguments );

			// Custom functionality below.
			var post_id = 0;
			if ( typeof id === 'object' ) {
				post_id = parseInt( this.getId( id ), 10 );
			}

			// If we found a post id.
			if ( post_id !== 0 ) {
				// Set product owner.
				var product_owner = $(' #the-list tr#post-' + post_id ).find( '.product-owner' ).data( 'id' );

				$( 'select[name="_product_owner"] option[value="' + product_owner + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
			}
		}
	}

	// Init when document is ready.
	$( document ).ready( init );

})(jQuery);
