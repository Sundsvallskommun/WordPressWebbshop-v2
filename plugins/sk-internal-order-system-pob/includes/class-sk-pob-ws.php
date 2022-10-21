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
	 * Type of POB case to send.
	 * @var string
	 */
	private $pob_type;

	/**
	 * The URL for the endpoint for creating tasks.
	 * @var string
	 */
	private $ws_create_task_url = '/pobg6/api/v110/cases';
	private $ws_get_equipment_name_url = '/pobg6/api/v110/configurationitems?fields=Id,OptionalNumber&filter=Virtual.WebLookupPCandMore=LookupDator01,OptionalNumber=';

	/**
	 * Authenticates with the WebService on construct.
	 * @param string $base_url
	 * @param string $username
	 * @param string $password
	 */
	public function __construct( $base_url, $username, $password, $type ) {
		// Set URLs.
		$this->ws_create_task_url = untrailingslashit( $base_url ) . $this->ws_create_task_url;
		$this->ws_get_equipment_name_url = untrailingslashit( $base_url ) . $this->ws_get_equipment_name_url;
		

		// Set credentials as class properties.
		$this->ws_username = $username;
		$this->ws_password = $password;
		$this->pob_type = $type;
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
		$current_user = wp_get_current_user();
		$form_id = RGFormsModel::get_form_id('Slutanvändare på utrustning');
		$form = GFAPI::get_form( $form_id );
		$casetype = rgar($form, 'form_type');
		$casetype = ! empty( $casetype ) ? $casetype : 'Service Request';
		$count = 1;
		$total_items = count($order_items);
		foreach ($order_items as $item) {
			$occupations .= get_occupation_string( $order, $item );
			preg_match("/<span id='occupationString'>(.*?)<\/span><br>/s", $occupations, $CI_description);
			$item_pob_fields = get_post_meta( $item['product_id'], 'sk_pob_fields', true );
			$product = $item->get_product();
			$sku = $product !== false ? $product->get_sku() : $item->get_id();

			$data = [
				"Description" => "Beställning {$order->id} - {$item->get_name()} ($count/$total_items)",
				"CaseType" => "{$casetype}",
				"CaseCategory" => $this->get_case_category_by_type(),
				"Contact.Customer" => $current_user->user_login,
				"PriorityInfo.Priority" => "IT4",
				"ResponsibleGroup" => "First Line IT",
				"Virtual.Shop_WebbshopOrdernummer" => "{$order->id}",
				"Virtual.Shop_AntalArtiklarIOrder" => "$count/$total_items",
				"Virtual.Shop_Office" => "0",
				"Virtual.Shop_Kst_Underkonto" => "{$item_pob_fields['Underkonto']}",
				"Virtual.Shop_Kst_Motpart" => "{$item_pob_fields['Motpart']}",
				"Virtual.Shop_ExterntArtikelnummer" => "{$item_pob_fields['Externt artikelnummer']}",
				"Virtual.Shop_ServiceIdExternalSync" => "{$sku}",
				"Virtual.Shop_CI_Description" => "{$CI_description[1]}",
				"Virtual.Shop_Adr_Gatuadress" => "{$order->data['billing']['address_1']}",
				"Virtual.Shop_Adr_Postnr" => "{$order->data['billing']['postcode']}",
				"Virtual.Shop_Adr_Postort" => "{$order->data['billing']['city']}",
				"Virtual.Shop_Kontaktperson" => "{$order->data['billing']['first_name']} {$order->data['billing']['last_name']}",
				"Virtual.Shop_Telefonnummer" => "{$order->data['billing']['phone']}",
				"Virtual.Shop_Epost" => "{$order->data['billing']['email']}",
			];

			$memo = 
				"Beställning {$order->id} - {$item->get_name()} ($count/$total_items) <br/><br/>".
				"Typ: " . "{$casetype} <br/>" .
				"Prioritet: " . "IT4 <br/>" .
				"Ansvarig grupp: " . "First Line IT <br/>" .
				"Webbshop Ordernummer: " . "{$order->id} <br/>" .
				"Antalet artiklar: " . "$count/$total_items <br/>" .
				"Underkonto: " . "{$item_pob_fields['Underkonto']} <br/>" .
				"Motpart: " . "{$item_pob_fields['Motpart']} <br/>" .   
				"Externt Artikelnummer: " . "{$item_pob_fields['Externt artikelnummer']} <br/>" .   
				"Artikelnummer: " . "{$sku} <br/>" .
				"Beskrivning: " . "{$CI_description[1]} <br/>" .
				"Kontaktperson: " . "{$order->data['billing']['first_name']} {$order->data['billing']['last_name']} <br/>" .
				"Telefonnummer: " . "{$order->data['billing']['phone']} <br/>" .
				"Epost: " . "{$order->data['billing']['email']} <br/>" ;
			
			$meta = $item->get_meta_data();

			foreach ($meta as $m) {
				$meta_label = $m->get_data()['key'];
				$pob_id = $this->get_pob_id($item, $meta_label);
				
				if ( $pob_id ) {
					if ( $pob_id == 'Virtual.Shop_Office' ) {
						$value = $this->get_pob_boolean( $m->get_data()['value'] );
					} else {
						$value = $m->get_data()['value'];
					}
					$data[$pob_id] = $value;
					$memo .= $meta_label . ": " . $value . "<br/>" ;
				}
			}

			$this->create_pob_case($data, $memo);
			$count++;
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

	public function get_equipment_name ($term) {
		$ch = curl_init();
		
		curl_setopt_array($ch, array(
			CURLOPT_URL => $this->ws_get_equipment_name_url . $term . '%',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
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
			$data = json_decode($data);
			return $data;
		} else {

			// Translators: WC_Order::ID.
			SKW()->log( sprintf(
				'PHP Notice: Failed to get equipment name from POB.',
				$term
				
			), E_WARNING );

			$log_entry = str_replace( "\r", ' ', str_replace( "\n", ' ', $data ) );
			// Otherwise, log the incident and the request.
			// Translators: the cURL response.
			SKW()->log( sprintf( __( 'PHP Debug: Equipment Name cURL response: %2$s', 'sk-pob' ), $log_entry ), E_WARNING );

			// Return a generic error message.
			return new WP_Error( 'pob_error', __( 'Något gick fel vid hämtning av utrustningsnamn.', 'sk-pob' ) );
		}
		curl_close($ch);
	}

	private function get_case_category_by_type() {
		switch( $this->pob_type ) {
			case 'pob_form':
				return 'Felanmälan via formulär';
			case 'pob':
			default:
				return 'Beställning av Hårdvara Advania';
		}
	}

	private function get_pob_boolean( $value ) {
		switch( $value ) {
			case 1:
			case '1':
			case 'Ja':
				return 'Yes';
			case 0:
			case '0':
			case 'Nej':
			default:
				return 'No';
		}
	}
}
