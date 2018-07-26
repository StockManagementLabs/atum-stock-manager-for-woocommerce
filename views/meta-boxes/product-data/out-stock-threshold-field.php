<?php
/**
 * Set an individual out of stock threshold on stock managed at product level items
 *
 * @since 1.4.10
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

/**
 * Used variabled
 *
 * @var array    $out_stock_threshold_classes
 * @var string   $out_stock_threshold_field_id
 * @var string   $out_stock_threshold_field_name
 * @var int      $out_stock_threshold
 * @var int      $woocommerce_notify_no_stock_amount
 * @var string   $product_type
 * @var \WP_Post $variation
 * @var int      $loop
 */

if ( 'yes' === Helpers::get_option( 'out_stock_threshold', 'no' ) ) : ?>

	<?php if ( empty( $variation ) ) : ?>
	<div class="options_group <?php echo implode( ' ', $out_stock_threshold_classes ) ?>">
	<?php endif; ?>

		<p class="form-field _out_stock_threshold_field <?php if ( ! empty( $variation ) ) echo ' show_if_variation_manage_stock form-row form-row-last' ?>">
			<label for="<?php echo $out_stock_threshold_field_id ?>">
				<?php _e( 'Out of stock threshold', ATUM_TEXT_DOMAIN ) ?>
			</label>

			<span class="atum-field input-group">
				<?php Helpers::atum_field_input_addon() ?>

				<input type="number" class="short" step="1" name="<?php echo $out_stock_threshold_field_name ?>"
					id="<?php echo $out_stock_threshold_field_id ?>" value="<?php echo $out_stock_threshold ?>"
					placeholder="<?php echo $woocommerce_notify_no_stock_amount ?>" data-onload-product-type="<?php echo $product_type ?>"
					<?php echo apply_filters( 'atum/views/meta_boxes/out_stock_threshold_field', '', $variation, $loop ) ?>>

				<?php echo wc_help_tip( __( "This value will override the global WooComerce's 'Out of stock threshold' for this individual product.", ATUM_TEXT_DOMAIN ) ); ?>
			</span>
		</p>

	<?php if ( empty( $variation ) ) : ?>
		</div>
	<?php endif; ?>

<?php endif;
