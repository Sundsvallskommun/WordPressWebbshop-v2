;(function($) {
	'use strict';

	/**
	 * The button to add a new product owner.
	 * @type {jQuery}
	 */
	var $addBtn = null,

		/**
		 * The container to which we will append the new fields.
		 * @type {jQuery}
		 */
		$productOwnerContainer = null,

		/**
		 * All product owners.
		 * @type {jQuery}
		 */
		$productOwners = null,

		/**
		 * Main form.
		 * @type {jQuery}
		 */
		$form = null;

	/**
	 * Binds events etc.
	 * @return {Void}
	 */
	function init() {
		// All product owners.
		$productOwners = $( '.product-owner' );

		// Bind the edit action.
		$productOwners.on( 'click', '.edit', editProductOwner );

		// Bind the delete action.
		$productOwners.on( 'click', '.remove', deleteProductOwner );

		// Bind the button which allows a user to add
		// a new product owner.
		$addBtn = $( '#skios_add_product_owner' );
		$addBtn.on( 'click', addProductOwnerFields );

		// Bind form.
		$form = $( 'form#mainform' );
		$form.on( 'submit', validate );
	}

	/**
	 * Edits a product owner.
	 * @param  {Event} e
	 * @return {Void}
	 */
	function editProductOwner( e ) {
		var $parent = $( this ).parent().parent(),
			$inputs = $parent.find( '.label input, .email input' ),
			$spans = $parent.find( '.label span, .email span' );

		$inputs.show();
		$spans.hide();
	}

	/**
	 * Prompts the user to make sure that they want to
	 * remove the product owner.
	 * @param  {Event} e
	 * @return {Void}
	 */
	function deleteProductOwner( e ) {
		var $parent = $( this ).parent().parent(),
			label = $parent.find( '.label input' ).val(),
			email = $parent.find( '.email input' ).val(),
			str = skios.i10n.delete_product_owner_prompt.replace( '%%', label + '(' + email + ')' );
		if ( window.confirm( str ) ) {
			$parent.remove();
		}
	}

	/**
	 * Appends the necessary fields that are required for
	 * a product owner.
	 * @param {Event} e
	 */
	function addProductOwnerFields( e ) {
		var i = $productOwners.length,
			$tr = $( '<tr class="product-owner"></tr>' ),
			$labelTd = $( '<td class="label"></td>' ),
			$labelInput = $( '<input type="text" name="product_owners[' + i + '][label]" placeholder="' + skios.i10n.new_product_owner_label_placeholder + '" style="display: inline-block;">'),
			$emailTd = $( '<td class="email"></td>' ),
			$emailInput = $( '<input type="email" name="product_owners[' + i + '][identifier]" placeholder="' + skios.i10n.new_product_owner_email_placeholder + '" style="display: inline-block;">'),
			$actionTd = $( '<td class="actions">' ),
			$deleteAction = $( '<span class="dashicons dashicons-trash remove"></span>' ),
			$idInput = $( '<input type="hidden" name="product_owners[' + i + '][id]" value="null">' );

		// Build the product owner row.
		$tr.append( $labelTd ).append( $emailTd ).append( $actionTd );
		$labelTd.append( $labelInput );
		$emailTd.append( $emailInput );
		$actionTd.append( $deleteAction ).append( $idInput );

		// Append to DOM.
		$( '.product-owners-container' ).append( $tr );

		// Add to array.
		$productOwners.push( $tr );
	}

	/**
	 * Validates that the data is correct and don't allow
	 * submitting if it isn't.
	 * @param  {Event} e
	 * @return {Void}
	 */
	function validate( e ) {
		if ( $( '.product-owner' ).length === 0 ) {
			alert( skios.i10n.empty_product_owners_not_allowed );
			return false;
		}

		return true;
	}

	// Init when document is ready.
	$( document ).ready( init );

})(jQuery);