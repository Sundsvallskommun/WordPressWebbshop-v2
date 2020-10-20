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

if ( ! class_exists( 'GF_Field' ) ) {
	return;
}

class GF_Field_Conditional_Owner extends GF_Field {

	/**
	 * Field type.
	 * @var string
	 */
	public $type = 'sk_conditional_owner';

	/**
	 * Return the field title.
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'SK Conditional Owner', 'sk' );
	}

	/**
	 * Return the field settings.
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'label_setting',
			'rules_setting',
			'css_class_setting',
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
		if ($this->is_form_editor()) {
			return '';
		}

		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$id              = (int) $this->id;

		$invert_answer = $this->emailInvertField;

		$size                  = $this->size;

		return Timber::compile( __DIR__ . '/views/input.twig', array(
			'size'        => $size,
			'id'          => $id,
		) );
	}

}

GF_Fields::register( new GF_Field_Conditional_Owner() );

/**
 * Add fields for email settings
 *
 * @param int $position
 * @param int $form_id
 *
 * @return void
 */
function product_owner_email_settings( $position, $form_id ) {

	if ( $position == 50 ) {
		?>
		<li class="owner_email_setting field_setting">

			<p>
				<label class="section_label" for="email_field_label">
					<?php _e( "Epost-etikett", "sk" ); ?>
					<?php gform_tooltip( "email_field_label" ) ?>
				</label>
				<input type="email" id="email_field_label" onchange="SetFieldProperty('emailFieldLabel', this.value);" />
			</p>

			<p>
				<label class="section_label" for="owner_email_yes">
					<?php _e( "E-post vid ja", "sk" ); ?>
					<?php gform_tooltip( "owner_email_yes" ) ?>
				</label>
				<input type="email" id="owner_email_yes" onchange="SetFieldProperty('ownerEmailYes', this.value);" />
			</p>

			<p>
				<label class="section_label" for="owner_email_no">
					<?php _e( "E-post vid nej", "sk" ); ?>
					<?php gform_tooltip( "owner_email_no" ) ?>
				</label>
				<input type="email" id="owner_email_no" onchange="SetFieldProperty('ownerEmailNo', this.value);" />
			</p>

			<p>
				<label class="section_label" for="owner_email_no">
					<?php _e( "Invertera svar i mejl", "sk" ); ?>
					<?php gform_tooltip( "owner_email_invert" ) ?>
				</label>
				<input type="checkbox" id="owner_email_invert" onclick="SetFieldProperty('emailInvertField', this.checked);" />
			</p>

		</li>
		<?php
	}
}
add_action( 'gform_field_standard_settings', 'product_owner_email_settings', 10, 2 );

/**
 * Inject supporting script to the form editor page
 */
function editor_script(){
	?>
	<script type='text/javascript'>
		//adding setting to fields of type "sk_conditional_owner"
		fieldSettings.sk_conditional_owner += ", .owner_email_setting";

		//binding to the load field settings event to initialize the checkbox
		jQuery(document).on("gform_load_field_settings", function(event, field, form){
			jQuery("#owner_email_invert").attr( "checked", field["emailInvertField"] == true );
			jQuery("#owner_email_yes").attr( "value", field["ownerEmailYes"] );
			jQuery("#owner_email_no").attr( "value", field["ownerEmailNo"] );
			jQuery("#email_field_label").attr( "value", field["emailFieldLabel"] );
		});
	</script>
	<?php
}
add_action( 'gform_editor_js', 'editor_script' );

/**
 * Add tooltips to settings fields
 */
function add_encryption_tooltips( $tooltips ) {
	$tt = '<h6>E-post vid %1$s</h6> Ange en epost-adress som får en kopia av beställningsmejlet om svaret på frågan är <em>%1$s</em>';
	$tooltips['owner_email_yes']     = sprintf( $tt, 'ja' );
	$tooltips['owner_email_no']      = sprintf( $tt, 'nej' );
	$tooltips['email_field_label']   = '<h6>Epost-etikett</h6>Ange en etikett som visas istället för fältetikett i mejlet som går ut till produktägaren.';
	$tooltips['owner_email_invert']  = '<h6>Invertera svar i mejl</h6>Bocka i om mejl till produktägare ska visa inverterade svar tillsammans med mejletiketten. Ja blir då nej och vice versa.';
	return $tooltips;
}
add_filter( 'gform_tooltips', 'add_encryption_tooltips' );
