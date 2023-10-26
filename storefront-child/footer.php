<?php

/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */

?>

</div><!-- .col-full -->
</div><!-- #content -->
<?php if (is_product() || is_checkout()) : ?>
	<?php $post = get_post(get_the_ID()); ?>
	<?php
	$form_id = get_post_meta(get_the_ID(), '_gravity_form_data', true)['id'];
	$form_title = RGFormsModel::get_form($form_id)->title;
	$is_advania = false;
	$fields_to_exclude = array();
	if ($form_advania = strpos($form_title, 'Advania') !== false) {
		$is_advania = true;
		$form = GFAPI::get_form($form_id);
		$fields = $form['fields'];
		foreach ($fields as $field) {
			if ($field->type == 'sk-enduser') {
				$fields_to_exclude[] = 'input_' . $field->id;
			}
		}
	}

	// Add the following JavaScript code to save the form values in local storage
	?>
	<script>
		const fieldsToExclude = <?php echo json_encode($fields_to_exclude); ?>;
		const isAdvania = <?php echo json_encode($is_advania); ?>;
		// const formId = <?php //echo $form_id ? $form_id : 0; ?>;
	</script>
	<script src="<?php echo get_stylesheet_directory_uri() . '/assets/js/persistant-form-advania.js'; ?>"></script>

<?php endif; ?>

<?php do_action('storefront_before_footer'); ?>

<footer id="colophon" class="site-footer" role="contentinfo">
	<div class="col-full">

		<?php
		/**
		 * Functions hooked in to storefront_footer action
		 *
		 * @hooked storefront_footer_widgets - 10
		 * @hooked storefront_credit         - 20
		 */
		do_action('storefront_footer');
		?>

	</div><!-- .col-full -->
</footer><!-- #colophon -->



</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>