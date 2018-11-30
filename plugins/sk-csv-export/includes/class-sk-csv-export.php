<?php
/**
 * SK_CSV_Export
 * =====
 *
 * The main plugin class file.
 *
 * @since   20181130
 * @package 
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SK_CSV_Export {

	private $csv = null;

	const CSV_PATH = __dir__;

	/**
	 * Constructor.
	 */
	function __construct() {
		add_action( 'wp', [ $this, 'listener' ] );
	}

	/**
	 * Our listener that runs certain actions based on GET-parameter.
	 *
	 * @return void
	 */
	public function listener() {
		if ( isset( $_GET['export_products'] ) ) {
			switch ( $_GET['export_products'] ) {
				case 'run':
					$this->export_products();
					break;
			}
		}
	}

	/**
	* Initiate csv export function
	*
	* @return void
	*/
	private function export_products() {

		$owners = skios_get_product_owners();

		foreach( $owners as $owner ) {
			$filename =  $owner['label'].'.csv';
			$posts = $this->get_products( $owner['id'] );
			$this->generate_csv( $posts, $filename );
		}

		$posts = $this->get_products();
		$this->generate_csv( $posts, 'no_owner.csv' );

		die();
	}

	/**
	* Get products
	*
	* @param int $owner Id of product owner to filter query by.
	*
	* @return array Returns the posts
	*/
	private function get_products( $owner = null ) {


		$args = array(
        	'post_type' => 'product',
        	'posts_per_page' => -1
        );

        if ( $owner ) {
			$args['meta_key'] = '_product_owner';
			$args['meta_value'] = $owner;
		} else {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key' => '_product_owner',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key' => '_product_owner',
					'value' => ''
				),
				array(
					'key' => '_product_owner',
					'value' => 0
				)
			);
		}
 
        $q = new WP_Query( $args );

      	return $q->posts;
	}

	/**
	* Generate CSV with products.
	*
	* @param array  $posts    Array of posts of type product.
	* @param string $filename Name of outputted file.
	*
	* @return array Returns the posts
	*/
	private function generate_csv( $posts, $filename ) {

		$this->csv_init($filename);

		foreach ( $posts as $post ) {

			$product = wc_get_product( $post->ID );

			if ( 'variable' == $product->get_type() ) {
				$variations = $product->get_available_variations();
				foreach ( $variations as $variation ) {
					$this->parse_product( $product, $variation );
				}
			} else {
				$this->parse_product( $product );
			}

		}

		$this->csv_close();
	}

	/**
	* Parse product and pass it to CSV row function.
	*
	* @param WC_Product           $product   The product.
	* @param WC_Product_Variation $variation The variation if the product is variatble.
	*
	* @return void
	*/
	private function parse_product( $product, $variation = null ) {

		$pr_owner = skios_get_product_owner_by_id( $product->get_meta( '_product_owner', true ) );	

		$title = $product->get_title();
		$description = strip_tags( $product->get_description() );
		$owner = $pr_owner['label'];
		$categories = $this->list_categories( $product );

		if ( $variation ) {
			$sku   = $variation['sku'];
  			$regular_price = html_entity_decode( strip_tags( wc_price( $variation['display_regular_price'] ) ) );
  			$attributes = $this->list_attributes( $product, $variation );
		} else {
			$sku = $product->get_sku();
			$regular_price = html_entity_decode( strip_tags( wc_price( $product->get_price() ) ) );
  			$attributes = $this->list_attributes( $product );
		}

		$pr = array(
			$sku,
	  		$title,
  			$description,
  			$regular_price,
  			$attributes,
  			$owner,
  			$categories
		);

		$this->csv_add_row( $pr );

	}

	/**
	* Initiate csv and add first row.
	*
	* @param string $filename Name of outputted file.
	*
	* @return void
	*/
	private function csv_init($filename) {

		$this->csv = fopen( self::CSV_PATH . '/' . $filename, 'w');

		$columns = [
			'Artikelnummer',
			'Namn',
			'Beskrivning',
			'Ord.pris',
			'Attribut',
			'Produktägare',
			'Kategorier'
		];

		$this->csv_add_row( $columns );
	}

	/**
	* Add row to csv file.
	*
	* @param array $row
	*
	* @return void
	*/
	private function csv_add_row( $row ) {
		fputcsv( $this->csv, $row );
	}

	/**
	* Close csv
	*
	* @param array $row
	*
	* @return void
	*/
	private function csv_close() {
		fclose($this->csv);
	}
	
	/**
	* Return a comma separated string of all product categories.
	*
	* @param WC_Product $product
	*
	* @return string
	*/
	private function list_categories( $product ) {

		$cat_ids = $product->get_category_ids();
		$pr_cats = [];
		foreach ( $cat_ids as $category ) {
			if( $term = get_term_by( 'id', $category, 'product_cat' ) ){
			    $pr_cats[] = $term->name;
			}
		}

		return implode( $pr_cats, ', ' );
	}

	/**
	* Return a comma separated string of product attributes.
	*
	* @param WC_Product           $product
	* @param WC_Product_Variation $variation
	*
	* @return string
	*/
	private function list_attributes( $product, $variation = null ) {

		$attr_list = [];

		$p_attributes = $product->get_attributes();

		if ( $variation ) {


			foreach ( $variation['attributes'] as $taxonomy => $attribute ) {

				$p_attr = $p_attributes[str_replace('attribute_', '', $taxonomy )];

				if ( $p_attr->is_taxonomy() ) {
					$meta = get_post_meta($variation['variation_id'], $taxonomy, true);
					$term = get_term_by( 'slug', $meta, str_replace('attribute_', '', $taxonomy ) );

					$attr_list[] = wc_attribute_label( $p_attr->get_name() ) . ': ' . $term->name;
				} else {
					$attr_list[] = $p_attr->get_name() . ': ' . $attribute;
				}
			}


		} else {

			foreach ( $p_attributes as $taxonomy => $attribute ) {
				
				$attr_str = wc_attribute_label( $attribute->get_name() ) . ': ';

				$attr_term_str = [];

				foreach ( $attribute->get_options() as $option ) {
					
					if ( $attribute->is_taxonomy() ) {
						$term = get_term_by( 'id', $option, str_replace('attribute_', '', $taxonomy ) );
						$attr_term_str[] = $term->name;
					} else {
						$attr_term_str[] = $option;
					}

				}

				$attr_str .= implode( $attr_term_str, ', ' );

				$attr_list[] = $attr_str;

			}

		}

		return implode( $attr_list, '. ' );
	}

}
