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
	public function __construct( WC_Order $order, $items ) {
		$this->order = $order;
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
				$order_items = $this->generate_order_items_xml( $this->order, $this->items );
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
	 * @param  WC_Order $order
	 * @param  array    $items
	 * @return string
	 */
	private function generate_order_items_xml( WC_Order $order, $items ) {
			// Loop through $items and get the values.
			foreach ( $items as $item ) {
				// Get the fields first and check that they're not empty.
				$item[ 'dedu_fields' ] = get_post_meta( $item[ 'product_id' ], 'sk_dedu_fields', true );
				if ( empty( $item[ 'dedu_fields' ] ) ) {
					return new WP_Error( 'missing_dedu_fields', __( 'Produkten saknar DeDU fält fastän DeDU är satt som produktägare.', 'sk-dedu' ) );
				}

				// Opening tag.
				$xml = '<Sundsvall_CreateWebShopTask>';

					$xml .= '<ADName>HELSJD</ADName>';
					$xml .= '<YrkeId>' . $item[ 'dedu_fields' ][ 'YrkeId' ] . '</YrkeId>';
					$xml .= '<ArendetypId>' . $item[ 'dedu_fields' ][ 'ArendetypId' ] . '</ArendetypId>';
					$xml .= '<KategoriId>' . $item[ 'dedu_fields' ][ 'KategoriId' ] . '</KategoriId>';
					$xml .= '<UnderkategoriId>' . $item[ 'dedu_fields' ][ 'UnderkategoriId' ] . '</UnderkategoriId>';
					$xml .= "<Anmarkning>{$this->generate_anmarkning_xml( $items )}</Anmarkning>";
					$xml .= '<PrioritetId>' . $item[ 'dedu_fields' ][ 'PrioritetId' ] . '</PrioritetId>';
					$xml .= "<Referensnummer>123</Referensnummer>";
					$xml .= "<InternKommentar>{$this->generate_internkommentar_xml( $order, $items )}</InternKommentar>";

				// Closing tag.
				$xml .= '</Sundsvall_CreateWebShopTask>';
			}

		// Return XML string.
		return $xml;
	}

	/**
	 * Generates the 'Anmärkning' part of the CreateWebShopTask.
	 * @param  array $items
	 * @return string
	 */
	private function generate_anmarkning_xml( $items ) {
		// Add header.
		$string = sprintf( __( "Beställningsnummer: %d\n\n", 'sk-dedu' ), $this->order->id );
		$string .= __( "Antal\t\t\tAnrtikel nr\t\t\t\t\tArtikel\n\n", 'sk-dedu' );

		// Loop through all items.
		foreach ( $items as $item ) {
			$string .= $item[ 'qty' ] . "\t\t\t\t" . get_post_meta( $item[ 'product_id' ], '_sku', true ) . "\t\t\t\t\t" . $item[ 'name' ] . "\n";
		}

		return $string;
	}

	/**
	 * Generates the 'Intern kommentar' part of the CreateWebShopTask.
	 * @param  WC_Order $order
	 * @param  array    $items
	 * @return string
	 */
	private function generate_internkommentar_xml( WC_Order $order, $items ) {
		// Add name.
		$string  = sprintf( __( "Namn: %s\n", 'sk-dedu' ), $order->get_formatted_billing_full_name() );

		// Add email.
		$string .= sprintf( __( "E-post: %s\n", 'sk-dedu' ), $order->billing_email );

		// Add phone.
		$string .= sprintf( __( "Telefon: %s\n", 'sk-dedu' ), $order->billing_phone );

		// Add customer message (order comment).
		$string .= sprintf( __( "Kommentar: %s\n", 'sk-dedu' ), $order->customer_message );

		// Add shipping address.
		$string .= sprintf( __( "Leveransadress: %s\n%s\n%s %s\n", 'sk-dedu' ),
			$order->billing_city,
			$order->billing_address_1,
			$order->billing_zipcode,
			$order->billing_city );

		// Add billing address.
		$string .= sprintf( __( "Fakturaadress (gäller endast vid extern faktura): %s\n%s\n%s %s\n", 'sk-dedu' ),
			$order->shipping_city,
			$order->shipping_address_1,
			$order->shipping_zipcode,
			$order->shipping_city );
		return $string;
	}

}