<?php
/**
 * View for the Supplier default settings meta box
 *
 * @since 1.2.9
 *
 * @var \Atum\Suppliers\Supplier $supplier
 */

defined( 'ABSPATH' ) || die;
?>

<div class="atum-meta-box supplier">

	<p class="description"><?php esc_html_e( 'Set defaults that ATUM will use when creating Purchase Orders for this supplier.', ATUM_TEXT_DOMAIN ) ?></p>

	<div class="form-field form-field-wide">
		<label for="assigned_to"><?php esc_html_e( 'Assigned To', ATUM_TEXT_DOMAIN ) ?></label>

		<?php
		$args = array(
			'show_option_none' => esc_html__( 'Choose a user&hellip;', ATUM_TEXT_DOMAIN ),
			'selected'         => $supplier->assigned_to,
			'name'             => 'default_settings[assigned_to]',
			'id'               => 'assigned_to',
			'class'            => 'wc-enhanced-select atum-enhanced-select',
		);
		wp_dropdown_users( $args );
		?>
	</div>

	<div class="form-field form-field-wide">
		<label for="location"><?php esc_html_e( 'Location', ATUM_TEXT_DOMAIN ) ?></label>
		<input type="text" id="location" name="default_settings[location]" value="<?php echo esc_attr( $supplier->location ) ?>">
	</div>

</div>
