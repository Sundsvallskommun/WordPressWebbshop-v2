<?php
/**
 * GF_Field_Equipment_Name
 * ================
 *
 * Custom gravity forms field for selecting
 * an end user in a form.
 *
 * @since   20191111
 * @package SK_Equipment_Name
 */

class GF_Field_Equipment_Name extends GF_Field {

	/**
	 * Field type.
	 * @var string
	 */
	public $type = 'sk-equipment-name';

	/**
	 * Return the field title.
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'SK Equipment Name', 'sk' );
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
			'pob_id_setting'
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

}

GF_Fields::register( new GF_Field_Equipment_Name() );
