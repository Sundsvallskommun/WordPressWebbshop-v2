<?php

/**
 * Unhooks certain parent theme actions.
 * @return void
 */
function remove_parent_theme_actions() {
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
}
add_action( 'after_setup_theme', 'remove_parent_theme_actions' );

/**
 * Adds an option to pages to hide the page title.
 */
include_once __DIR__ . '/functions/hide-page-title.php';

/**
 * Adds the css and javascript needed in order
 * to be able to collapse categories in the widget.
 * @return void
 */
function collapsable_categories() {
	?>

  <style>

		.widget_product_categories ul li:before {
            min-width: 2em;
            text-align: right;
		}

		.widget_product_categories .current-cat > a {
			font-weight: 600 !important;
		}

		.widget_product_categories .cat-parent.closed .children {
			display: none;
		}

		.widget_product_categories .cat-parent:before {
			content: "- \f07c" !important;
		}

		.widget_product_categories .cat-parent.closed:before {
			content: "+ \f07b" !important;
		}

		.widget_product_categories .cat-parent:before {
			cursor: pointer;
		}
	</style>

	<script>
		jQuery( document ).ready( function($) {
			// Collapse all parents.
			$( '.widget_product_categories ul .cat-parent:not(.current-cat-parent, .current-cat)' ).addClass( 'closed' );

			// Make sure all parents of current category are still open.
			$( '.current-cat' ).parents( '.cat-parent' ).removeClass( 'closed' );

			$( '.widget_product_categories' ).on( 'click', '.cat-parent', function(e) {
				if (e.target !== this) return;

				$(this).toggleClass('closed');
			});
		});
	</script>
	<?php
}

add_action( 'wp_footer', 'collapsable_categories' );

/**
 * Adds "Categories" and "products" headings in product archive.
 */
function separate_cat_and_products() {
	?>
	<script type="text/javascript">
		jQuery( document ).ready( function($) {


			var $cats = $('.products > .product-category');

			if ( $cats.length > 0 ) {

				var $catUl = $('<ul class="products"></ul>');
				var $productUl = $('ul.products');

				$cats.detach();
				$cats.appendTo($catUl);
				$catUl.insertBefore($productUl);

				$('<h2>Kategorier</h2>').insertBefore($catUl);
				$('<h2>Produkter</h2>').insertBefore($productUl);

				$catUl.find('.product').each(fixFirstLast);
				$productUl.find('.product').each(fixFirstLast);

				function fixFirstLast(i) {
					$(this).removeClass('first');
					$(this).removeClass('last');

					if (i%3 == 0) { $(this).addClass('first'); }
					if (i%3 == 2) { $(this).addClass('last'); }

				}

			}

		});
	</script>
	<?php
}

add_action( 'wp_footer', 'separate_cat_and_products' );

/**
 * Adds a tooltip for products in product archive if the product has a short
 * description.
 */
add_action( 'woocommerce_before_shop_loop_item', function() {

	if (is_cart()) {
		return false;
	}

	global $product;

	$short_description = $product->get_short_description();

	$has_description = mb_strlen( $short_description ) > 0;

	if ( $has_description ) {
?>

<div class="product-tooltip">
	<div>
		<h2><?php echo $product->get_title(); ?></h2>
		<?php echo $short_description; ?>
	</div>
</div>

	<?php
	}

});

// add_filter( 'woocommerce_add_to_cart_validation', function () {
// 	$sk_raindance->validate($field, $value);
// });

function sk_product_tooltip_script() {
?>
	<script>
jQuery(document).ready(function($) {

	var $tooltips = $('.product-tooltip');

	function alignToolTips() {

		$tooltips.each(function() {

			var img = $(this).next('img');
			var imgHeight = img.height();
			var imgWidth = img.width();

			$(this).css({
				'top' : 0,
				'width' : imgWidth,
				'height' : imgHeight,
				'left' : '50%',
				'transform': 'translateX(-50%)'
			})
		});

	}

	$(window).resize(alignToolTips);


	alignToolTips();

});
	</script>
<?php
}

add_action( 'wp_footer', 'sk_product_tooltip_script' );

/**
 * Override inline styles from theme customization to prevent it from
 * being changed.
 */
include_once __DIR__.'/inline-styles.php';

/**
 * Change the translation of the upsells title on the product page.
 */
function translate_upsells_title( $translated ) {
   $translated = str_ireplace( 'Du gillar kanske också&hellip;', 'Du kanske behöver', $translated );
   return $translated;
}

add_filter( 'gettext', 'translate_upsells_title' );

/**
 * Add navigation icons (my account and support) to site header.
 */
function sk_navigation_icons() {

	echo '<div class="sk-navigation-icons">';
		echo get_icon_link( 'Mitt konto', get_permalink( get_option('woocommerce_myaccount_page_id') ), 'user' );

		$support_url = get_theme_mod( 'sk_support_url' );
		if ( $support_url ) {
			echo get_icon_link( 'Support', $support_url, 'headset' );
		}
	echo '</div>';
}
add_action('storefront_header', 'sk_navigation_icons', 41 );

/**
 * Get link with icon.
 */
function get_icon_link( $text, $url, $icon ) {
	return sprintf(
		'<a class="icon-link" href="%s"><span class="icon"><i class="fas fa-%s"></i></span>%s</a>',
		$url,
		$icon,
		$text
	);
}

/**
 * Add support-url setting to the customizer.
 * The value is used in the site header.
 */
function support_url_setting( $wp_customize ) {
	$wp_customize->add_setting( 'sk_support_url' );
	$wp_customize->add_control( 'sk_support_url', array(
		'label'    => __( 'Support-url', '' ),
		'section'  => 'header_image',
		'settings' => 'sk_support_url',
		'type'     => 'text'
	));
}

add_action( 'customize_register', 'support_url_setting' );

add_action( 'gform_field_standard_settings', 'sundsvall_standard_settings', 10, 2 );
function sundsvall_standard_settings( $position, $form_id ) {
  
    //create settings on position 25 (right after Field Label)
    if ( $position == 25 ) {
        ?>
        <li class="pob_id_setting field_setting">
            <label for="field_pob_id" class="section_label">
                <?php _e("Insert POB ID", "your_text_domain"); ?>
                <?php gform_tooltip("form_field_encrypt_value") ?>
            </label>
            <input onchange="SetFieldProperty('pobId', this.value);" name="field_pob_id" type="text" id="field_pob_id" />
        </li>
		<li>
			<label for="notification_type" class="section_label">
				<?php _e("Notification", "your_text_domain"); ?>
                <?php gform_tooltip("form_field_notification_type") ?>
			</label>
			<input onchange="SetFieldProperty('notificationType', this.value);" name="notification_type" type="text" id="notification_type" />
		</li>
        <li class="raindance_number_type_setting field_setting">
            <label for="field_raindance_number_type" class="section_label">
                <?php _e("Choose Numbertype", "your_text_domain"); ?>
                <?php gform_tooltip("form_field_encrypt_value") ?>
            </label>
            <select onchange="SetFieldProperty('raindanceNumberType', this.value);" name="field_raindance_number_type" id="field_raindance_number_type">
				<option value="responsibility_number" <?php if (rgar($form, 'field_raindance_number_type') == 'responsibility_number') : ?> selected <?php endif ?>>Ansvarsnummer</option>
				<option value="occupation_number" <?php if (rgar($form, 'field_raindance_number_type') == 'occupation_number') : ?> selected <?php endif ?>>Verksamhetsnummer</option>
				<option value="activity_number" <?php if (rgar($form, 'field_raindance_number_type') == 'activity_number') : ?> selected <?php endif ?>>Aktivitetsnummer</option>
				<option value="project_number" <?php if (rgar($form, 'field_raindance_number_type') == 'project_number') : ?> selected <?php endif ?>>Projektnummer</option>
				<option value="object_number" <?php if (rgar($form, 'field_raindance_number_type') == 'object_number') : ?> selected <?php endif ?>>Objektnummer</option>
			</select>
        </li>
        <?php
    }
}
//Action to inject supporting script to the form editor page
add_action( 'gform_editor_js', 'sundsvall_editor_script' );
function sundsvall_editor_script(){
    ?>
    <script type='text/javascript'>
        //adding setting to fields of type "text"
        fieldSettings.text += ', .pob_id_setting';
        fieldSettings.name += ', .pob_id_setting';
        fieldSettings.date += ', .pob_id_setting';
        fieldSettings.time += ', .pob_id_setting';
        fieldSettings.phone += ', .pob_id_setting';
        fieldSettings.address += ', .pob_id_setting';
        fieldSettings.website += ', .pob_id_setting';
        fieldSettings.email += ', .pob_id_setting';
        fieldSettings.list += ', .pob_id_setting';
        fieldSettings.radio += ', .pob_id_setting';
        fieldSettings.number += ', .pob_id_setting';
        fieldSettings.checkbox += ', .pob_id_setting';
        fieldSettings.select += ', .pob_id_setting';
        fieldSettings.textarea += ', .pob_id_setting';
        fieldSettings.fileupload += ', .pob_id_setting';
        fieldSettings.multiselect += ', .pob_id_setting';
        fieldSettings.sk_conditional_owner += ', .pob_id_setting';
        fieldSettings['sk-enduser'] += ', .pob_id_setting';
        fieldSettings['sk-raindance-number'] += ', .pob_id_setting';
        fieldSettings['sk-raindance-number'] += ', .raindance_number_type_setting';
        // binding to the load field settings event to initialize the checkbox
        jQuery(document).on('gform_load_field_settings', function(event, field, form){
            jQuery( '#field_pob_id' ).prop( 'value', rgar( field, 'pobId' ) );
        });
        jQuery(document).on('gform_load_field_settings', function(event, field, form){
            jQuery( '#field_raindance_number_type' ).prop( 'value', rgar( field, 'raindanceNumberType' ) );
        });
        jQuery(document).on('gform_load_field_settings', function(event, field, form){
            jQuery( '#notification_type' ).prop( 'value', rgar( field, 'notificationType' ) );
        });
    </script>
    <?php
}
//Filter to add a new tooltip
add_filter( 'gform_tooltips', 'sundsvall_add_encryption_tooltips' );
function sundsvall_add_encryption_tooltips( $tooltips ) {
   $tooltips['form_field_encrypt_value'] = "<h6>Pob ID</h6>Write the ID of POB field";
   $tooltips['form_field_notification_type'] = "<h6>Notification Type</h6>Ange valet på frågan som du vill villkorsstyra via API";
   return $tooltips;
}

add_filter( 'gform_form_settings', 'sundsvall_form_type_setting', 10, 2 );
function sundsvall_form_type_setting( $settings, $form ) {
    $settings[ __( 'Form Type', 'gravityforms' ) ]['form_type'] = '
	<tr>
		<th><label for="form_type">Form Type</label></th>
		<td>
			<select name="form_type" id="form_type">
				<option value="0">Välj typ</option>
				<option value="Service Request"' .( ( rgar($form, 'form_type') == 'Service Request' ) ? 'selected' : '' ) . '>Service Request</option>
				<option value="Incident"' . (( rgar($form, 'form_type') == 'Incident' ) ? 'selected' : '') . '>Incident</option>
			</select>
		</td>
	</tr>';
 
    return $settings;
}
 
// save your custom form setting
add_filter( 'gform_pre_form_settings_save', 'save_sundsvall_form_type_setting' );
function save_sundsvall_form_type_setting($form) {
    $form['form_type'] = rgpost( 'form_type' );
    return $form;
}

add_action( 'gform_after_submission', 'set_post_content', 10, 2 );
function set_post_content( $entry, $form ) {
	global $sk_pob;
	$send_with_pob = false;
	$casetype = rgar($form, 'form_type');
	$casetype = ! empty( $casetype ) ? $casetype : 'Incident';
	$data = [
		"CaseType" => $casetype,
		"Description" =>  "Test av Web Service Rest 1", 
		"PriorityInfo.Priority" =>  "IT4",
		"ResponsibleGroup" => "First Line IT",
	];

	$memo = '';
	foreach ($form['fields'] as $field) {
		$field_label = $field->label;
		$field_value = rgar($entry, $field->id);
		$pob_id = rgar($field, 'pobId');
		$notification = rgar($field, "notificationType");

		if (!empty($field_value)) {
			$memo .= $field_label . ": " . $field_value.PHP_EOL;
		}

		if ($pob_id) {
			$data[$pob_id] = $field_value;
		}

		if (!empty($notification) && $field_value == $notification) {
			$send_with_pob = true;
		}

	}
	if ($send_with_pob) {
		$sk_pob->create_pob_case($data, $memo, 'pob_form');
	}
}

function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}