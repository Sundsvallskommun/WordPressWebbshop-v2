<?php
/**
 * SKIOS_Occupation_String
 * =======================
 *
 * @package SKIOS
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SKIOS_Occupation_String {

	/**
	 * Registers hooks and filters.
	 */
	public function __construct() {
		add_filter( 'gform_pre_form_settings_save', [ $this, 'save_my_custom_form_setting' ] );
		add_filter( 'gform_form_settings', [ $this, 'my_custom_form_setting' ], 10, 2 );
	}

	public function save_my_custom_form_setting( $form ) {
		$form['occupation_string_1'] = rgpost( 'occupation_string_1' );
		$form['occupation_string_2'] = rgpost( 'occupation_string_2' );
		$form['occupation_string_3'] = rgpost( 'occupation_string_3' );
		return $form;
	}

	public function my_custom_form_setting( $settings, $form ) {
		$settings[ __( 'Verksamhetsbeskrivning', 'sk' ) ]['occupation_string_description'] = '
		<tr>
		<th colspan="2"><p>Ange de exakta fältetiketten för de fält som ska användas i den kommaseparerade verskamhetsbeskrivningen i beställningmejl.</p></th>
			</tr>';

		$settings[ __( 'Verksamhetsbeskrivning', 'sk' ) ]['occupation_string_1'] = '
			<tr>
				<th><label for="occupation_string_1">Fält 1 (Förvaltning/bolag)</label></th>
				<td><input value="' . rgar( $form, 'occupation_string_1' ) . '" name="occupation_string_1"></td>
			</tr>';

		$settings[ __( 'Verksamhetsbeskrivning', 'sk' ) ]['occupation_string_2'] = '
			<tr>
				<th><label for="occupation_string_2">Fält 2 (Avdelning/Arbetsplats)</label></th>
				<td><input value="' . rgar( $form, 'occupation_string_2' ) . '" name="occupation_string_2"></td>
			</tr>';

		$settings[ __( 'Verksamhetsbeskrivning', 'sk' ) ]['occupation_string_3'] = '
			<tr>
				<th><label for="occupation_string_3">Fält 3 (Namn)</label></th>
				<td><input value="' . rgar( $form, 'occupation_string_3' ) . '" name="occupation_string_3"></td>
			</tr>';

		return $settings;
	}
}