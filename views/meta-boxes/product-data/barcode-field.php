<?php
/**
 * View for the Barcode field within the WC Product Data meta box
 *
 * @since 1.9.18
 *
 * @var string   $barcode
 * @var string   $barcode_field_classes
 * @var string   $barcode_field_id
 * @var string   $barcode_field_name
 * @var \WP_Post $variation
 * @var int      $loop
 */

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCapabilities;
use Atum\Inc\Helpers;

if ( empty( $variation ) ) : ?>
<div class="options_group <?php echo esc_attr( $barcode_field_classes ) ?>">
<?php endif; ?>

	<p class="form-field _barcode_field<?php if ( ! empty( $variation ) ) echo ' form-row form-row-first ' ?> <?php echo esc_attr( $barcode_field_classes ) ?>">
		<label for="<?php echo esc_attr( $barcode_field_id ) ?>"><?php esc_html_e( 'Barcode', ATUM_TEXT_DOMAIN ) ?></label>

		<span class="atum-field input-group">
			<?php Helpers::atum_field_input_addon() ?>

			<input type="text" class="short" name="<?php echo esc_attr( $barcode_field_name ) ?>"
				id="<?php echo esc_attr( $barcode_field_id ) ?>" value="<?php echo esc_attr( $barcode ) ?>"
				<?php disabled( AtumCapabilities::current_user_can( 'edit_barcode' ), FALSE ) ?>
				<?php echo apply_filters( 'atum/views/meta_boxes/barcode_field/barcode_extra_atts', '', $variation, $loop ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>

			<?php if ( ! Addons::is_addon_active( 'barcodes_pro' ) ) : ?>
				<?php echo wc_help_tip( esc_attr__( 'If you want more functionalities for barcodes, try out the ATUM Barcodes PRO add-on', ATUM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</span>
	</p>

	<?php do_action( 'atum/barcodes/after_barcode_field', $variation, $barcode ) ?>

<?php if ( empty( $variation ) ) : ?>
</div>
<?php endif;
