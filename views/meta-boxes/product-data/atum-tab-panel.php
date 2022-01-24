<?php
/**
 * View for the ATUM tab panel within the WC Product Data meta box
 *
 * @since 1.4.5
 *
 * @var \WC_Product|\Atum\Models\Products\AtumProductTrait $product
 * @var string                                             $product_status
 * @var array                                              $checkbox_wrapper_classes
 * @var array                                              $control_button_classes
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;

?>
<div id="atum_product_data" class="atum-data-panel panel woocommerce_options_panel hidden">
	<div class="options_group">

		<p class="form-field <?php echo esc_attr( implode( ' ', $checkbox_wrapper_classes ) . ' ' . Globals::ATUM_CONTROL_STOCK_KEY ) ?>_field">
			<label for="<?php echo esc_attr( Globals::ATUM_CONTROL_STOCK_KEY ) ?>"><?php esc_html_e( 'ATUM Control Switch', ATUM_TEXT_DOMAIN ); ?></label>

			<?php echo wc_help_tip( __( 'Turn the switch ON or OFF to allow the ATUM plugin to include this product in its lists, counters and statistics.', ATUM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php $field_value = 'auto-draft' === $product_status ? 'yes' : $product->get_atum_controlled() ?>
			<span class="form-switch">
				<input type="checkbox" name="<?php echo esc_attr( 'atum_product_tab[' . Globals::ATUM_CONTROL_STOCK_KEY . ']' ) ?>"
					id="<?php echo esc_attr( Globals::ATUM_CONTROL_STOCK_KEY ) ?>" class="form-check-input"
					value="yes" <?php checked( $field_value, 'yes' ) ?>
				>
			</span>
		</p>

		<p class="form-field product-tab-runner <?php echo esc_attr( implode( ' ', $control_button_classes ) ) ?>">
			<label for="stock_control_status"><?php esc_html_e( "Variations' ATUM Control", ATUM_TEXT_DOMAIN ) ?></label>
			<select id="stock_control_status">
				<option value="controlled"><?php esc_html_e( 'Controlled', ATUM_TEXT_DOMAIN ) ?></option>
				<option value="uncontrolled"><?php esc_html_e( 'Uncontrolled', ATUM_TEXT_DOMAIN ) ?></option>
			</select>
			&nbsp;
			<?php /* translators: the status of the ATUM control switch */ ?>
			<button type="button" class="run-script button button-primary" data-action="atum_set_variations_control_status" data-confirm="<?php esc_attr_e( 'This will change the ATUM Control Switch for all the variations within this product to %s', ATUM_TEXT_DOMAIN ) ?>">
				<?php esc_html_e( 'Apply', ATUM_TEXT_DOMAIN ) ?>
			</button>

			<?php echo wc_help_tip( esc_html__( 'Changes the ATUM Control switch for all the variations to the status set at once.', ATUM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</p>

		<p class="form-field product-tab-runner <?php echo esc_attr( implode( ' ', $control_button_classes ) ) ?>">
			<label for="variations_supplier"><?php esc_html_e( "Variations' Supplier", ATUM_TEXT_DOMAIN ) ?></label>
			<?php echo Helpers::suppliers_dropdown( [ 'enhanced' => TRUE ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			&nbsp;
			<button type="button" class="run-script button button-primary" data-action="atum_set_variations_supplier" data-confirm="<?php esc_attr_e( 'This will set the specified supplier for all the variations within this product', ATUM_TEXT_DOMAIN ) ?>">
				<?php esc_html_e( 'Apply', ATUM_TEXT_DOMAIN ) ?>
			</button>

			<?php echo wc_help_tip( esc_html__( 'Sets the supplier for all the variations at once', ATUM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</p>

	</div>

	<?php
	// Allow other fields to be added to the ATUM panel.
	do_action( 'atum/after_product_data_panel' ); ?>
</div>

