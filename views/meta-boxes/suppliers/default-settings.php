<?php
/**
 * View for the Supplier default settings meta box
 *
 * @since 1.2.9
 *
 * @var int $supplier_id
 */

defined( 'ABSPATH' ) or die;
?>

<div class="atum-meta-box supplier">

	<p class="description"><?php _e('Set defaults that ATUM will use when creating Purchase Orders for this supplier.', ATUM_TEXT_DOMAIN) ?></p>

	<div class="form-field form-field-wide">
		<label for="assigned_to"><?php _e('Assigned To', ATUM_TEXT_DOMAIN) ?></label>

		<?php
		$args = array(
			'show_option_none' => esc_html__( 'Choose a user&hellip;', ATUM_TEXT_DOMAIN ),
			'selected'         => get_post_meta( $supplier_id, '_default_settings_assigned_to', TRUE ),
			'name'             => 'default_settings[assigned_to]',
			'id'               => 'assigned_to',
			'class'            => 'wc-enhanced-select'
		);
		wp_dropdown_users($args);
		?>
	</div>

	<div class="form-field form-field-wide">
		<label for="location"><?php _e('Location', ATUM_TEXT_DOMAIN) ?></label>
		<input type="text" id="location" name="default_settings[location]" value="<?php echo get_post_meta( $supplier_id, '_default_settings_location', TRUE ) ?>">
	</div>

</div>