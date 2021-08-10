<?php
/**
 * View for the Supplier default settings meta box
 *
 * @since 1.2.9
 *
 * @var \Atum\Suppliers\Supplier $supplier
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

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
		wp_dropdown_users( $args ); // TODO: THIS CAN CAUSE PERFORMANCE ISSUES IF THERE ARE THOUSANDS USERS.
		?>
	</div>

	<div class="form-field form-field-wide">
		<label for="location"><?php esc_html_e( 'Location', ATUM_TEXT_DOMAIN ) ?></label>
		<input type="text" id="location" name="default_settings[location]" value="<?php echo esc_attr( $supplier->location ) ?>">
	</div>

	<div class="form-field form-field-wide">
		<label for="discount"><?php esc_html_e( 'Discount', ATUM_TEXT_DOMAIN ) ?></label>
		<input type="number" step="1" min="0" id="discount" name="default_settings[discount]" value="<?php echo esc_attr( $supplier->discount ) ?>">
	</div>

	<div class="form-field form-field-wide">
		<label for="tax_rate"><?php esc_html_e( 'Tax Rate', ATUM_TEXT_DOMAIN ) ?></label>
		<input type="number" step="1" min="0" id="tax_rate" name="default_settings[tax_rate]" value="<?php echo esc_attr( $supplier->tax_rate ) ?>">
	</div>

	<div class="form-field form-field-wide">
		<label for="lead_time"><?php esc_html_e( 'Lead Time (in days)', ATUM_TEXT_DOMAIN ) ?></label>
		<input type="number" step="1" min="0" id="lead_time" name="default_settings[lead_time]" value="<?php echo esc_attr( $supplier->lead_time ) ?>">
	</div>

	<div class="form-field form-field-wide">
		<label for="delivery_terms"><?php esc_html_e( 'Payments and Delivery Terms', ATUM_TEXT_DOMAIN ) ?></label>
		<textarea id="delivery_terms" name="default_settings[delivery_terms]" rows="5"><?php echo esc_textarea( $supplier->delivery_terms ) ?></textarea>
	</div>

	<div class="form-field form-field-wide">
		<label for="days_to_cancel"><?php esc_html_e( 'Days to Cancel', ATUM_TEXT_DOMAIN ) ?></label>
		<input type="number" step="1" min="0" id="days_to_cancel" name="default_settings[days_to_cancel]" value="<?php echo esc_attr( $supplier->days_to_cancel ) ?>">
	</div>

	<div class="form-field form-field-wide">
		<label for="cancelation_policy"><?php esc_html_e( 'Cancelation Policy', ATUM_TEXT_DOMAIN ) ?></label>
		<textarea id="cancelation_policy" name="default_settings[cancelation_policy]" rows="5"><?php echo esc_textarea( $supplier->cancelation_policy ) ?></textarea>
	</div>

</div>
