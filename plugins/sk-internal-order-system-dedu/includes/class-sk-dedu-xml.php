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
			$item['dedu_fields'] = get_post_meta( $item['product_id'], 'sk_dedu_fields', true );
			if ( empty( $item['dedu_fields'] ) ) {
				return new WP_Error( 'missing_dedu_fields', __( 'Produkten saknar DeDU fält fastän DeDU är satt som produktägare.', 'sk-dedu' ) );
			}

			$reference_number = htmlspecialchars( $order->get_meta( '_billing_reference_number' ) );

			// Opening tag.
			$xml = '<Sundsvall_CreateWebShopTask>';

				$xml .= "<ADName>{$this->get_adname()}</ADName>";
				$xml .= '<YrkeId>' . $item['dedu_fields']['YrkeId'] . '</YrkeId>';
				$xml .= '<ArendetypId>' . $item['dedu_fields']['ArendetypId'] . '</ArendetypId>';
				$xml .= '<KategoriId>' . $item['dedu_fields']['KategoriId'] . '</KategoriId>';
				$xml .= '<UnderkategoriId>' . $item['dedu_fields']['UnderkategoriId'] . '</UnderkategoriId>';
				$xml .= "<Anmarkning>{$this->generate_anmarkning_xml( $items )}</Anmarkning>";
				$xml .= '<PrioritetId>' . $item['dedu_fields']['PrioritetId'] . '</PrioritetId>';
				$xml .= "<Referensnummer>{$reference_number}</Referensnummer>";
				$xml .= "<InternKommentar>{$this->generate_internkommentar_xml( $order, $items )}</InternKommentar>";

			// Closing tag.
			$xml .= '</Sundsvall_CreateWebShopTask>';
		}

		// Return XML string.
		return $xml;
	}

	/**
	 * Returns ADName for DeDU.
	 * @return string
	 */
	private function get_adname() {
		$current_user = wp_get_current_user();
		if ( $current_user->ID > 0 ) {
			return $current_user->user_login;
		} else {
			throw new WP_Error( __( 'User is not logged in!', 'sk-dedu' ) );
		}
	}

	/**
	 * Generates the 'Anmärkning' part of the CreateWebShopTask.
	 * @param  array $items
	 * @return string
	 */
	private function generate_anmarkning_xml( $items ) {
		$pad_number = 20;

		// Keep track of the longest string for quantity and SKU.
		$qty_str_len = strlen( 'Antal' );
		$sku_str_len = strlen( 'Artikelnr' );

		// Array for all products.
		$products = array();

		// Loop through all items to figure out string lengths
		// and to get all products.
		foreach ( $items as $key => $item ) {
			$product = wc_get_product( $item->get_product() );

			// Change longest string for quantity or sku?
			$qty_str_len = ( strlen( $item->get_quantity() ) > $qty_str_len ) ? strlen( $item->get_quantity() ) : $qty_str_len;
			$sku_str_len = ( strlen( $product->get_sku() ) > $sku_str_len ) ? strlen( $product->get_sku() ) : $sku_str_len;

			// Add the product to the array with the same key.
			$products[ $key ] = $product;
		}

		$str  = "\nBeställningsnummer: {$this->order->get_id()}\n";
		$str .= sprintf( "%s%sArtikel\n",
			str_pad( 'Antal', ( $qty_str_len + $pad_number ), ' ', STR_PAD_RIGHT ),
			str_pad( 'Artikel', ( $sku_str_len + $pad_number ), ' ', STR_PAD_RIGHT )
		);

		// Loop through all products to create a new string.
		foreach ( $items as $key => $item ) {
			// Get product from the array.
			$product = $products[ $key ];

			// Build the string.
			$str .= sprintf( "%1\$s%2\$s%3\$s (%4\$s %6\$s / %5\$s %6\$s)\n",
				str_pad( $item->get_quantity(), ( $qty_str_len + $pad_number ), ' ', STR_PAD_RIGHT ),
				str_pad( $product->get_sku(), ( $sku_str_len + $pad_number ), ' ', STR_PAD_RIGHT ),
				str_pad( $product->get_name(), 0, ' ', STR_PAD_LEFT ),
				wc_get_price_to_display( $product ),
				$item->get_total(),
				html_entity_decode( get_woocommerce_currency_symbol() )
			);

			if ( ! empty( $item->get_formatted_meta_data() ) ) {
				// Add an empty line.
				$str .= "\n";

				// Add all meta data at the end of the line.
				foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
					$meta_string = sprintf( "%s: %s\n\n", $meta->key, $meta->value );
					$str .= str_pad( $meta_string, $qty_str_len + $sku_str_len + ( $pad_number * 2 ) + strlen( $meta_string ), ' ', STR_PAD_LEFT );
				}
			}
		}

		return htmlspecialchars( $str );
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
		$string .= sprintf( __( "E-post: %s\n", 'sk-dedu' ), $order->get_billing_email() );

		// Add phone.
		$string .= sprintf( __( "Telefon: %s\n", 'sk-dedu' ), $order->get_billing_phone() );

		// Add company
		$string .= sprintf( __( "Förvaltning: %s\n", 'sk-dedu' ), $order->get_billing_company() );

		// Add customer message (order comment).
		$string .= sprintf( __( "Kommentar: %s\n", 'sk-dedu' ), $order->get_customer_note() );

		// Add shipping address.
		$string .= sprintf( __( "Leveransadress:\n%s\n%s\n%s\n%s %s\n", 'sk-dedu' ),
			$order->get_meta('_billing_organization', true),
			$order->get_meta('_billing_department', true),
			$order->get_billing_address_1(),
			$order->get_billing_postcode(),
			$order->get_billing_city()
		);

		// Add billing address.
		$string .= sprintf( __( "Fakturaadress (gäller endast vid extern faktura): %s\n", 'sk-dedu' ), '' );
		return htmlspecialchars( $string );
	}

}
