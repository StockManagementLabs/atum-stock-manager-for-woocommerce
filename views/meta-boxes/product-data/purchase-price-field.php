<?php
/**
 * View for the Purchase Price field within the WC Product Data meta box
 *
 * @since 1.4.1
 */

defined( 'ABSPATH' ) or die;
?>
<p class="form-field <?php echo $wrapper_class ?>">
	<label for="_purchase_price"><?php echo $field_title ?></label>

	<span class="atum-field input-group">
		<?php \Atum\Inc\Helpers::atum_field_input_addon() ?>
		<input type="text" class="short wc_input_price" style="" name="<?php echo $field_name ?>" id="<?php echo $field_id ?>" value="<?php echo $field_value ?>" placeholder="">
	</span>
</p>

