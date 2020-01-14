<?php
/**
 * GF_Field_Enduser
 * ================
 *
 * Custom gravity forms field for selecting
 * an end user in a form.
 *
 * @since   20191111
 * @package SK_Enduser
 */

class GF_Field_Enduser extends GF_Field {

	/**
	 * Field type.
	 * @var string
	 */
	public $type = 'sk-enduser';

	/**
	 * Return the field title.
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'SK Enduser', 'sk' );
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

		$input = '<div class="ginput_container ginput_container_select2">';

		$input .=
		"<select class='js-search-enduser {$size}' id='input_{$id}' name='input_{$id}' data-placeholder='{$placeholder_attribute}'>
			<option></option>
		</select>";

		$input .= '</div>';
		return $input;
	}

}

GF_Fields::register( new GF_Field_Enduser() );
