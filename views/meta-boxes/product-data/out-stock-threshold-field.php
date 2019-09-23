<?php
/**
 * Set an individual out of stock threshold on stock managed at product level items
 *
 * @since 1.4.10
 *
 * @var array    $visibility_classes
 * @var string   $out_stock_threshold_field_id
 * @var string   $out_stock_threshold_field_name
 * @var int      $out_stock_threshold
 * @var int      $woocommerce_notify_no_stock_amount
 * @var string   $product_type
 * @var \WP_Post $variation
 * @var int      $loop
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

$visibility_classes = implode( ' ', $visibility_classes );

if ( 'yes' === Helpers::get_option( 'out_stock_threshold', 'no' ) ) : ?>

	<?php if ( empty( $variation ) ) : ?>
	<div class="options_group <?php echo esc_attr( $visibility_classes ) ?>">
	<?php endif; ?>

		<p class="form-field _out_stock_threshold_field <?php echo esc_attr( $visibility_classes ) ?><?php if ( ! empty( $variation ) ) echo ' show_if_variation_manage_stock form-row form-row-last' ?>">
			<label for="<?php echo esc_attr( $out_stock_threshold_field_id ) ?>">
				<?php esc_attr_e( 'Out of stock threshold', ATUM_TEXT_DOMAIN ) ?>
			</label>

			<span class="atum-field input-group">
				<?php Helpers::atum_field_input_addon() ?>

				<input type="number" class="short" step="1" name="<?php echo esc_attr( $out_stock_threshold_field_name ) ?>"
					id="<?php echo esc_attr( $out_stock_threshold_field_id ) ?>" value="<?php echo esc_attr( $out_stock_threshold ) ?>"
					placeholder="<?php echo esc_attr( $woocommerce_notify_no_stock_amount ) ?>" data-onload-product-type="<?php echo esc_attr( $product_type ) ?>"
					<?php echo apply_filters( 'atum/views/meta_boxes/out_stock_threshold_field_extra_atts', '', $variation, $loop ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>

				<?php echo wc_help_tip( esc_attr__( "This value will override the global WooComerce's 'Out of stock threshold' for this individual product.", ATUM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
		</p>

	<?php if ( empty( $variation ) ) : ?>
		</div>
	<?php endif; ?>

<?php endif;
