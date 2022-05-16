<?php
/**
 * SK_pob_WS
 * ==========
 *
 * Wrapper class for sending order data to the pob WebService.
 *
 * @since   0.1
 * @package SK_pob
 */

class Sk_POB_WS {

	/**
	 * Given username.
	 * @var string
	 */
	private $ws_username;

	/**
	 * Given password.
	 * @var string
	 */
	private $ws_password;

	/**
	 * The URL for the endpoint for creating tasks.
	 * @var string
	 */
	private $ws_create_task_url = '/pobg6/api/v110/cases';

	/**
	 * Authenticates with the WebService on construct.
	 * @param string $base_url
	 * @param string $username
	 * @param string $password
	 */
	public function __construct( $base_url, $username, $password ) {
		// Set URLs.
		$this->ws_create_task_url = untrailingslashit( $base_url ) . $this->ws_create_task_url;
		

		// Set credentials as class properties.
		$this->ws_username = $username;
		$this->ws_password = $password;
	}

	/**
	 * Sends an order to the pob WebService.
	 * @param  WC_Order $order
	 * @param  array    $order_items Array of products
	 * @return
	 */
	// "Virtual.Shop_JoinContact" => "test01test",
				// "Virtual.Shop_ForvaltningBolag" => "{$item->get_meta('Arbetsplats')}",
	public function send_order( WC_Order $order, $order_items ) {
		$form_id = RGFormsModel::get_form_id('Slutanvändare på utrustning');
		$form = GFAPI::get_form( $form_id );
		$casetype = rgar($form, 'form_type');
		foreach ($order_items as $item) {
			$occupations .= get_occupation_string( $order, $item );
			preg_match("/<span id='occupationString'>(.*?)<\/span><br>/s", $occupations, $CI_description);
			$item_pob_fields = get_post_meta( $item['product_id'], 'sk_pob_fields', true );
			$data = [
				"Description" => "[Shop] Beställning av tjänst {$order->id} {$item->get_id()}",
				"CaseType" => "{$casetype}",
				"PriorityInfo.Priority" => "IT4",
				"ResponsibleGroup" => "First Line IT",
				"Virtual.Shop_Office" => "1",
				"Virtual.Shop_Kst_Underkonto" => "{$item_pob_fields['Underkonto']}",
				"Virtual.Shop_Kst_Motpart" => "{$item_pob_fields['Motpart']}",
				"Virtual.Shop_ExterntArtikelnummer" => "{$item_pob_fields['Externt artikelnummer']}",
				"Virtual.Shop_CI_Description" => "{$CI_description[1]}",
				"Virtual.Shop_Adr_Gatuadress" => "{$order->data['billing']['address_1']}",
				"Virtual.Shop_Adr_Postnr" => "{$order->data['billing']['postcode']}",
				"Virtual.Shop_Adr_Postort" => "{$order->data['billing']['city']}",
				"Virtual.Shop_Kontaktperson" => "{$order->data['billing']['first_name']} {$order->data['billing']['last_name']}",
				"Virtual.Shop_Telefonnummer" => "{$order->data['billing']['phone']}",
				"Virtual.Shop_Epost" => "{$order->data['billing']['email']}",
			];
			$memo = 
				"Description: " . "[Shop] Beställning av tjänst {$order->id} {$item->get_id()}\r\n".
				"CaseType: " . "{$casetype}\r\n" .
				"PriorityInfo.Priority: " . "IT4\r\n" .
				"ResponsibleGroup: " . "First Line IT\r\n" .
				"Virtual.Shop_Office: " . "1\r\n" .
				"Virtual.Shop_Kst_Underkonto: " . "{$item_pob_fields['Underkonto']}\r\n" .
				"Virtual.Shop_Kst_Motpart: " . "{$item_pob_fields['Motpart']}\r\n" .   
				"Virtual.Shop_ExterntArtikelnummer: " . "{$item_pob_fields['Externt artikelnummer']}\r\n" .   
				"Virtual.Shop_CI_Description: " . "{$CI_description[1]}\r\n" .
				"Virtual.Shop_Adr_Gatuadress: " . "{$order->data['billing']['address_1']}\r\n" .
				"Virtual.Shop_Adr_Postnr: " . "{$order->data['billing']['postcode']}\r\n" .
				"Virtual.Shop_Adr_Postort: " . "{$order->data['billing']['city']}\r\n" .
				"Virtual.Shop_Kontaktperson: " . "{$order->data['billing']['first_name']} {$order->data['billing']['last_name']}\r\n" .
				"Virtual.Shop_Telefonnummer: " . "{$order->data['billing']['phone']}\r\n" .
				"Virtual.Shop_Epost: " . "{$order->data['billing']['email']}\r\n" ;
			
			$meta = $item->get_meta_data();
			foreach ($meta as $m) {
				$meta_label = $m->get_data()['key'];
				$pob_id = $this->get_pob_id($item, $meta_label);
				$datavalue = $m->get_data();
				
				if ($pob_id) {
					$data[$pob_id] = $m->get_data()['value'];
					$memo .= $pob_id . ": " . $m->get_data()['value']. "\r\n" ;
				}
			}
			$this->create_pob_case($data, $memo);
		}
	} 

	private function get_pob_id($item, $field_label) {
		$gravity_form_data = get_post_meta( $item->get_product_id(), '_gravity_form_data', true );
		$gravityform       = null;
		if ( is_array( $gravity_form_data ) && isset( $gravity_form_data['id'] ) && is_numeric( $gravity_form_data['id'] ) ) {
			$form_meta = RGFormsModel::get_form_meta( $gravity_form_data['id'] );
			if ( ! empty( $form_meta ) ) {
				$gravityform = RGFormsModel::get_form( $gravity_form_data['id'] );
			}
			foreach ($form_meta['fields'] as $field) {
				if ($field->label == $field_label) {
					return rgar($field, 'pobId');
				}
			}
		}
		return false;
	}

	public function create_pob_case($data, $memo) {
		// Init cURL.
		$ch = curl_init();
		$post_fields = [
			"Type" => "Case",
			"Data" => $data,
			"Memo" => [
				"Problem"=> [
					"Extension" => ".html", 
					"IsValidForWeb" => false, 
					"Style" => 2,
					"Memo" => $memo
				] 
			]
		];
		curl_setopt_array($ch, array(
			CURLOPT_URL => $this->ws_create_task_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_POSTFIELDS => json_encode($post_fields),
			CURLOPT_HTTPHEADER => array(
			  'Authorization: ' . $this->ws_username . ' ' . $this->ws_password,
			  'Content-Type: application/json'
			),
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0
		));
		// Execute request.
		$data = curl_exec( $ch );
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$err_no = curl_errno( $ch );
		if ($status_code === 400 ) {
			$message = json_decode($data);
			error_log("Ett fel uppstod vid kommunikation med POB: " . $message->Message);
		}
		// Check if we had any errors and if the HTTP status code was 201.
		if ( ! curl_errno( $ch )) {  //&& curl_getinfo( $ch, CURLINFO_HTTP_CODE ) === 201
			return true;
		} else {
			// Try to get the error from headers.
			$error = ( isset( SKW()->get_headers_from_curl( $data )['ErrorDescription'] ) ) ?
				SKW()->get_headers_from_curl( $data )['ErrorDescription'] : '';

			// Translators: WC_Order::ID.
			SKW()->log( sprintf(
				'PHP Notice: Failed to export WC_Order #%1$s to POB in %2$s. Message from POB: %3$s',
				$order->get_id(),
				__FILE__,
				$error
			), E_WARNING );

			$log_entry = str_replace( "\r", ' ', str_replace( "\n", ' ', $data ) );
			// Otherwise, log the incident and the request.
			// Translators: the cURL response.
			SKW()->log( sprintf( __( 'PHP Debug: WC_Order #%1$s cURL response: %2$s', 'sk-pob' ), $order->get_id(), $log_entry ), E_WARNING );

			// Return a generic error message.
			return new WP_Error( 'pob_error', __( 'Något gick fel vid beställningen.', 'sk-pob' ) );
		}
		curl_close($ch);
	}
}