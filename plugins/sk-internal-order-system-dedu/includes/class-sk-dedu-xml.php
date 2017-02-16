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
		$xml = '<?xml version="1.0" encoding="utf-8" ?>';

		$xml .= '<Sundsvall_ListOfWebShopTasks xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">';

			$xml .= '<listOfTasks>';

				// Get the XML for the order items.
				$order_items = $this->generate_order_items_xml( $this->items );
				if ( ! is_wp_error( $order_items ) ) {
					$xml .= $order_items;
				} else {
					// Otherwise return the instance of the WP_Error.
					return $order_items;
				}

			$xml .= '</listOfTasks>';

		$xml .= '</Sundsvall_ListOfWebShopTasks>';

		return $xml;
	}

	/**
	 * Generates a XML string from a template.
	 * @return string
	 */
	private function generate_order_items_xml( $items ) {
			// Loop through $items and get the values.
			foreach ( $items as $item ) {
				// Get the fields first.
				$item[ 'dedu_fields' ] = get_post_meta( $item[ 'product_id' ], 'sk_dedu_fields', true );

				// Opening tag.
				$xml = '<Sundsvall_CreateWebShopTask>';

				if ( empty( $item[ 'dedu_fields' ] ) ) {
					return new WP_Error( 'missing_dedu_fields', __( 'Produkten saknar DeDU fält fastän DeDU är satt som produktägare.', 'sk-dedu' ) );
				}

					$xml .= '<ADName>HELSJD</ADName>';
					$xml .= '<YrkeId>' . $item[ 'dedu_fields' ][ 'YrkeId' ] . '</YrkeId>';
					$xml .= '<ArendetypId>' . $item[ 'dedu_fields' ][ 'ArendetypId' ] . '</ArendetypId>';
					$xml .= '<KategoriId>' . $item[ 'dedu_fields' ][ 'KategoriId' ] . '</KategoriId>';
					$xml .= '<UnderkategoriId>' . $item[ 'dedu_fields' ][ 'UnderkategoriId' ] . '</UnderkategoriId>';
					$xml .= "<Anmarkning>Anmärkningstext</Anmarkning>";
					$xml .= '<PrioritetId>' . $item[ 'dedu_fields' ][ 'PrioritetId' ] . '</PrioritetId>';
					$xml .= "<Referensnummer>123</Referensnummer>";
					$xml .= "<InternKommentar>Internkommentar</InternKommentar>";

				// Closing tag.
				$xml .= '</Sundsvall_CreateWebShopTask>';
			}

		// Return XML string.
		return $xml;
	}

}