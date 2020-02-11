<?php
/**
 * SK_Term
 * =======
 *
 * Replacement Term class for \Timber\Term.
 *
 * @since   20200211
 * @package SKW
 */

use Timber\Term;

class SK_Term extends \Timber\Term {

	/**
	 * @var string
	 */
	public $TermClass = '\SK_Term';

	/**
	 * @param int $tid
	 * @param string $tax
	 */
	public function __construct( $tid = null, $tax = '' ) {
		parent::__construct( $tid, $tax );
	}

	/**
	 * Returns an array of immediate children.
	 * @return array
	 */
	public function children() {
		$terms = get_terms( [
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
			'parent'     => $this->id,
		] );

		return array_map( function( $term ) {
			return new SK_Term( $term );
		}, $terms );
	}

}
