<?php
/**
 * View for the ATUM tab panel within the WC Product Data meta box in variations
 *
 * @since 1.4.5
 *
 * @var int                                                          $loop
 * @var \WC_Product_Variation|\Atum\Models\Products\AtumProductTrait $variation
 * @var array                                                        $variation_data
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;

?>
<div class="atum-data-panel">
	<h2 class="atum-section-title">
		<img src="<?php echo esc_url( ATUM_URL ) . 'assets/images/atum-icon.svg'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
		<?php esc_html_e( 'ATUM Inventory', ATUM_TEXT_DOMAIN ) ?>
	</h2>

	<div class="options_group">

		<?php $field_id = Globals::ATUM_CONTROL_STOCK_KEY . '_' . $loop ?>
		<p class="form-field <?php echo esc_attr( $field_id ) ?>_field">
			<label for="<?php echo esc_attr( $field_id ) ?>"><?php esc_html_e( 'ATUM Control Switch', ATUM_TEXT_DOMAIN ); ?></label>

			<?php echo wc_help_tip( __( 'Turn the switch ON or OFF to allow the ATUM plugin to include this product in its lists, counters and statistics.', ATUM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php $field_value = $variation->get_atum_controlled() ?>
			<span class="form-switch">
				<input type="checkbox" name="<?php echo esc_attr( 'variation_atum_tab[' . Globals::ATUM_CONTROL_STOCK_KEY . "][$loop]" ) ?>"
					id="<?php echo esc_attr( $field_id ) ?>" class="form-check-input variation-atum-controlled"
					value="yes" <?php checked( $field_value, 'yes' ) ?>
				>
			</span>
		</p>

	</div>

	<?php
	// Allow other fields to be added to the ATUM panel.
	do_action( 'atum/after_variation_product_data_panel', $loop, $variation_data, $variation ); ?>
</div>

