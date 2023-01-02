(function($) {
	var Enduser = {
		formId: 0,
		fieldId: 0,
		el: null,
		qty: null,
		init: function( enduser, qty ) {
			this.el = enduser;
			this.qty = qty;

			this.formId = $( this.el ).parents( 'form' ).get(0).id.split('_')[1];
			this.fieldId = this.el.id.split('_')[1];

			var conditionalLogic = ( typeof gf_get_field_logic === 'function' )
				? gf_get_field_logic( this.formId, this.fieldId )
				: false;
			if ( conditionalLogic ) {
				// Save the rules of course.
				this.conditionalLogic = conditionalLogic.field.rules[0];
				// Figure out the DOM node for the required node
				// and save it for future references.
				var requiredField = $( '#input_' + this.formId + '_' + this.conditionalLogic.fieldId + ' input' );
				this.conditionalLogic.requiredField = requiredField;
			} else {
				this.conditionalLogic = false;
			}

			this.binds();
		},
		binds: function() {
			if ( this.conditionalLogic ) {
				$( this.conditionalLogic.requiredField ).on( 'change', this.maybeChangeQty.bind( this ) );
			} else {
				$( this.el ).on( 'change', this.maybeChangeQty.bind( this ) );
			}
		},
		maybeChangeQty: function() {
			// if ( this.conditionalLogic ) {
			// 	if ( $( this.conditionalLogic.requiredField ).is( ':checked' ) ) {
			// 		this.hideQty();
			// 	} else {
			// 		this.showQty();
			// 	}

			// 	return;
			// }

			// if ( this.el.value.trim().length > 0 ) {
			// 	this.hideQty();
			// } else {
				this.showQty();
			// }
		},
		// hideQty: function() {
		// 	$( this.qty ).hide();
		// 	this.setQty( 1 );
		// },
		showQty: function() {
			$( this.qty ).show();
		},
		setQty: function( quantity ) {
			$( this.qty ).val( quantity );
		}
	};

	$( document ).ready( function() {
		window.setTimeout( function() {
			var $enduser_select = $( 'select.js-search-enduser' );
			var $qty_input = $( 'input[name=quantity]' );
			if ( $enduser_select.length === 0 || $qty_input.length === 0 ) {
				return;
			}

			var _enduser = Enduser.init( $enduser_select.get(0), $qty_input.get(0) );
		}, 200 );
	} );

})(jQuery);
