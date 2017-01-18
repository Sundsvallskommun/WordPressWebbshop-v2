<?php
/**
 * SK_DeDU_XML
 * ===========
 *
 * Class to generate XML needed for the WebService.
 *
 * @since   0.1
 * @package SK_DeDU
 */

class Sk_DeDU_XML {

	/**
	 * Order items.
	 * @var array
	 */
	private $items;

	/**
	 * Constructs the class.
	 *
	 * Saves the order_items as a property.
	 */
	public function __construct( $items ) {
		$this->items = $items;
	}

	/**
	 * Generates the XML.
	 * @return string
	 */
	public function generate_xml() {
		return $this->get_xml_template();
	}

	/**
	 * Generates a XML string from a template.
	 * @return string
	 */
	private function get_xml_template() {
		ob_start();

		// Make items a scope variable.
		$items = $this->items;

		// Loop through $items and get the values.
		foreach ( $items as $item ) {
			$item[ 'dedu_fields' ] = get_post_meta( $item[ 'post_id' ], 'sk_dedu', true );
		}

		// Include template.
		include __DIR__ . '/template/create-web-task.phtml';
	}

}