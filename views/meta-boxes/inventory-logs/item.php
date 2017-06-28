<?php
/**
 * View for the Inventory Logs single items
 *
 * @since 1.2.4
 *
 * @var \Atum\InventoryLogs\Models\Log           $log
 * @var \Atum\InventoryLogs\Items\LogItemProduct $item
 * @var int                                      $item_id
 * @var string                                   $class
 */

defined( 'ABSPATH' ) or die;

do_action( 'atum/inventory_logs/log/before_log_item_product_html', $item, $log );

$product      = $item->get_product();
$product_link = $product ? admin_url( 'post.php?post=' . $item->get_product_id() . '&action=edit' ) : '';
$thumbnail    = $product ? apply_filters( 'atum/inventory_logs/log/item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
?>
<tr class="item <?php echo apply_filters( 'atum/inventory_logs/log/item_class', ( ! empty( $class ) ? $class : '' ), $item, $log ); ?>" data-log_item_id="<?php echo absint( $item_id ); ?>">
	<td class="thumb">
		<?php echo '<div class="atum-log-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>'; ?>
	</td>

	<td class="name" data-sort-value="<?php echo esc_attr( $item->get_name() ); ?>">
		<?php
			echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="atum-log-item-name">' . esc_html( $item->get_name() ) . '</a>' : '<div class="atum-log-item-name">' . esc_html( $item->get_name() ) . '</div>';

			if ( $product && $product->get_sku() ) {
				echo '<div class="atum-log-item-sku"><strong>' . __( 'SKU:', ATUM_TEXT_DOMAIN ) . '</strong> ' . esc_html( $product->get_sku() ) . '</div>';
			}

			if ( $item->get_variation_id() ) {
				echo '<div class="atum-log-item-variation"><strong>' . __( 'Variation ID:', ATUM_TEXT_DOMAIN ) . '</strong> ';

				if ( 'product_variation' === get_post_type( $item->get_variation_id() ) ) {
					echo esc_html( $item->get_variation_id() );
				}
				else {
					printf( esc_html__( '%s (No longer exists)', ATUM_TEXT_DOMAIN ), $item->get_variation_id() );
				}

				echo '</div>';
			}
		?>
		<input type="hidden" class="log_item_id" name="log_item_id[]" value="<?php echo esc_attr( $item_id ); ?>" />
		<input type="hidden" name="log_item_tax_class[<?php echo absint( $item_id ); ?>]" value="<?php echo esc_attr( $item->get_tax_class() ); ?>" />

		<?php do_action( 'atum/inventory_logs/log/before_log_item_meta', $item_id, $item, $product ) ?>
		<?php include( 'item-meta.php' ); ?>
		<?php do_action( 'atum/inventory_logs/log/ater_log_item_meta', $item_id, $item, $product ) ?>
	</td>

	<?php do_action( 'atum/inventory_logs/log/item_values', $product, $item, absint( $item_id ) ); ?>

	<td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $log->get_item_subtotal( $item, FALSE, TRUE ) ); ?>">
		<div class="view">
			<?php
				$currency = $log->get_currency();
				echo wc_price( $log->get_item_total( $item, FALSE, TRUE ), array( 'currency' => $currency ) );

				if ( $item->get_subtotal() !== $item->get_total() ) {
					echo '<span class="atum-log-item-discount">-' . wc_price( wc_format_decimal( $log->get_item_subtotal( $item, FALSE, FALSE ) - $log->get_item_total( $item, FALSE, FALSE ), '' ), array( 'currency' => $currency ) ) . '</span>';
				}
			?>
		</div>
	</td>

	<td class="quantity" width="1%">

		<div class="view">
			<?php
				echo '<small class="times">&times;</small> ' . esc_html( $item->get_quantity() );

				/*if ( $refunded_qty = $order->get_qty_refunded_for_item( $item_id ) ) {
					echo '<small class="refunded">' . ( $refunded_qty * -1 ) . '</small>';
				}*/
			?>
		</div>

		<div class="edit" style="display: none;">
			<input type="number" step="<?php echo apply_filters( 'atum/inventory_logs/log/quantity_input_step', '1', $product ); ?>" min="0" autocomplete="off" name="log_item_qty[<?php echo absint( $item_id ); ?>]" placeholder="0" value="<?php echo esc_attr( $item->get_quantity() ); ?>" data-qty="<?php echo esc_attr( $item->get_quantity() ); ?>" size="4" class="quantity" />
		</div>

		<?php /*
		<div class="refund" style="display: none;">
			<input type="number" step="<?php echo apply_filters( 'atum/inventory_logs/log/quantity_input_step', '1', $product ); ?>" min="0" max="<?php echo $item->get_quantity(); ?>" autocomplete="off" name="refund_log_item_qty[<?php echo absint( $item_id ); ?>]" placeholder="0" size="4" class="refund_log_item_qty" />
		</div>
        */ ?>

	</td>
	<td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( $item->get_total() ); ?>">
		<div class="view">
			<?php
				echo wc_price( $item->get_total(), array( 'currency' => $currency ) );

				if ( $item->get_subtotal() !== $item->get_total() ) {
					echo '<span class="atum-log-item-discount">-' . wc_price( wc_format_decimal( $item->get_subtotal() - $item->get_total(), '' ), array( 'currency' => $currency ) ) . '</span>';
				}

				/*if ( $refunded = $order->get_total_refunded_for_item( $item_id ) ) {
					echo '<small class="refunded">' . wc_price( $refunded, array( 'currency' => $currency ) ) . '</small>';
				}*/
			?>
		</div>

		<div class="edit" style="display: none;">
			<div class="split-input">
				<div class="input">
					<label><?php esc_attr_e( 'Pre-discount:', ATUM_TEXT_DOMAIN ); ?></label>
					<input type="text" name="line_subtotal[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $item->get_subtotal() ) ); ?>" class="line_subtotal wc_input_price" data-subtotal="<?php echo esc_attr( wc_format_localized_price( $item->get_subtotal() ) ); ?>" />
				</div>

				<div class="input">
					<label><?php esc_attr_e( 'Total:', ATUM_TEXT_DOMAIN ); ?></label>
					<input type="text" name="line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $item->get_total() ) ); ?>" class="line_total wc_input_price" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', ATUM_TEXT_DOMAIN ); ?>" data-total="<?php echo esc_attr( wc_format_localized_price( $item->get_total() ) ); ?>" />
				</div>
			</div>
		</div>

		<?php /*
		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_total wc_input_price" />
		</div>
		*/ ?>
	</td>

	<?php
	if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :

		foreach ( $log->get_taxes() as $tax_item ) :
			$tax_item_id       = $tax_item->get_rate_id();
			$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';
			?>
			<td class="line_tax" width="1%">
				<div class="view">
					<?php
						if ( '' != $tax_item_total ) {
							echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) );
						}
						else {
							echo '&ndash;';
						}

						if ( $item->get_subtotal() !== $item->get_total() ) {
							if ( '' === $tax_item_total ) {
								echo '<span class="atum-log-item-discount">&ndash;</span>';
							}
							else {
								echo '<span class="atum-log-item-discount">-' . wc_price( wc_round_tax_total( $tax_item_subtotal - $tax_item_total ), array( 'currency' => $currency ) ) . '</span>';
							}
						}

						/*if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id ) ) {
							echo '<small class="refunded">' . wc_price( $refunded, array( 'currency' => $currency ) ) . '</small>';
						}*/
					?>
				</div>

				<div class="edit" style="display: none;">
					<div class="split-input">
						<div class="input">
							<label><?php esc_attr_e( 'Pre-discount:', ATUM_TEXT_DOMAIN ); ?></label>
							<input type="text" name="line_subtotal_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $tax_item_subtotal ) ); ?>" class="line_subtotal_tax wc_input_price" data-subtotal_tax="<?php echo esc_attr( wc_format_localized_price( $tax_item_subtotal ) ); ?>" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
						</div>

						<div class="input">
							<label><?php esc_attr_e( 'Total:', ATUM_TEXT_DOMAIN ); ?></label>
							<input type="text" name="line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $tax_item_total ) ); ?>" class="line_tax wc_input_price" data-total_tax="<?php echo esc_attr( wc_format_localized_price( $tax_item_total ) ); ?>" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
						</div>
					</div>
				</div>

				<?php /*
				<div class="refund" style="display: none;">
					<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
				</div>
                */ ?>
			</td>
		<?php endforeach;

	endif; ?>

	<td class="atum-log-edit-line-item" width="1%">
		<div class="atum-log-edit-line-item-actions">
			<?php if ( $log->is_editable() ) : ?>
				<a class="edit-log-item" href="#" data-toggle="tooltip" title="<?php esc_attr_e( 'Edit item', ATUM_TEXT_DOMAIN ); ?>"></a><a class="delete-log-item" href="#" data-toggle="tooltip" title="<?php esc_attr_e( 'Delete item', ATUM_TEXT_DOMAIN ); ?>"></a>
			<?php endif; ?>
		</div>
	</td>
</tr>
<?php

do_action( 'atum/inventory_logs/log/after_log_item_product_html', $item, $log );
