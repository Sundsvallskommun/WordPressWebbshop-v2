<?php
/**
 * SK_Clear_Orders
 * =====
 *
 * The main plugin class file.
 *
 * @since   20181128
 * @package 
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SK_Clear_Orders {

	/**
	 * Minimium time to keep orders before moving them to trash.
	 *
	 * 1 year, 10 months + 1 month in trash + 1 month in backup.
	 */
	const TIMELIMIT = MONTH_IN_SECONDS * 22;

	/**
	 * Constructor.
	 */
	function __construct() {
		add_action( 'parse_request', [ $this, 'listener' ], 5 );
	}

	/**
	 * Our listener that runs certain actions based on GET-parameter.
	 *
	 * @return void
	 */
	public function listener() {
		if ( isset( $_GET['clear_orders'] ) ) {
			switch ( $_GET['clear_orders'] ) {
				case 'run':
					$this->clear_orders();
					break;
				case 'preview':
					$this->preview();
					break;
			}
		}
	}

	/**
	* Print how many orders exists that are older than the limit.
	*
	* @return void
	*/
	private function preview() {
		$orders_to_trash = $this->get_orders_to_trash();
		$num = count($orders_to_trash);

		printf( _n( "%s order would be trashed.", "%s orders would be trashed.", $num, 'sk-clearorders' ), $num);
		die();
	}

	/**
	* Trash orders that are older than the limit.
	*
	* @return void
	*/
	private function clear_orders() {
		$orders_to_trash = $this->get_orders_to_trash();

		$num_trashed = 0;

		foreach( $orders_to_trash as $order ) {
			if ( wp_trash_post($order) ) {
				$num_trashed += 1;
			}
		}

		printf( _n( "%s order trashed.", "%s orders trashed.", $num_trashed, 'sk-clearorders' ), $num_trashed);
		die();
	}

	/**
	* Get id of orders to trash
	* 
	* @param  string $limit Maximum orders to return, default -1 (unlimited)
	* @return array
	*/
	private function get_orders_to_trash( $limit = -1 ) {
		
		// Get 10 most recent order ids in date descending order.
		$query = new WC_Order_Query( array(
		    'limit' => $limit,
		    'orderby' => 'date',
		    'order' => 'DESC',
		    'return' => 'ids',
		    'date_created' => '<' . ( time() - self::TIMELIMIT ),
		) );
		$orders = $query->get_orders();

		return $orders;

	}

}
