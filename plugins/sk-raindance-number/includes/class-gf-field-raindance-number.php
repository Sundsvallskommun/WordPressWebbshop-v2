<?php
/**
 * GF_Field_Raindance_Number
 * ================
 *
 * Custom gravity forms field for selecting
 * an end user in a form.
 *
 * @since   20191111
 * @package SK_Raindance_Number
 */

class GF_Field_Raindance_Number extends GF_Field {

	/**
	 * Field type.
	 * @var string
	 */
	public $type = 'sk-raindance-number';

	/**
	 * Return the field title.
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'SK Raindance Number', 'sk' );
	}

	/**
	 * Return the field settings.
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'label_setting',
			'placeholder_setting',
			'conditional_logic_field_setting',
			'rules_setting',
		);
	}

	/**
	 * Outputs the field select input.
	 * @param  array  $form
	 * @param  string $value
	 * @param  mixed  $entry
	 * @since  20191111
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$id              = (int) $this->id;

		$placeholder_attribute = $this->placeholder;
		$size                  = $this->size;

		return Timber::compile( __DIR__ . '/views/select2.twig', array(
			'size'        => $size,
			'id'          => $id,
			'placeholder' => $placeholder_attribute,
		) );
	}

	public function validate( $value, $form ) {
		global $sk_raindance;

		$field = rgar($this->get_number_type_field($form['fields']), 'raindanceNumberType');
		if ( $value !== '' && $value !== 0 && is_wp_error( $sk_raindance->validate($field, $value)) ) {
			$this->failed_validation = true;
			if ( ! empty( $this->errorMessage ) ) {
				$this->validation_message = $this->errorMessage;
			}
		}
	}

	private function get_number_type_field ($fields) {
		foreach ($fields as $field) {
			if ($field->id == $this->id) {
				return $field;
			}
		}
	}
}

GF_Fields::register( new GF_Field_Raindance_Number() );