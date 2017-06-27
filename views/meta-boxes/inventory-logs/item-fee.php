<?php
/**
 * View for the Inventory Logs single fee items
 *
 * @since 1.2.4
 *
 * @var \Atum\InventoryLogs\Items\LogItemFee $item
 * @var \Atum\InventoryLogs\Models\Log       $log
 * @var int                                  $item_id
 */

defined( 'ABSPATH' ) or die;

do_action( 'atum/inventory_logs/log/before_log_item_fee_html', $item, $log );
$currency = $log->get_currency();

?>
<tr class="fee <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-log_item_id="<?php echo absint( $item_id ); ?>">
	<td class="thumb"><div></div></td>

	<td class="name">
		<div class="view">
			<?php echo esc_html( $item->get_name() ? $item->get_name() : __( 'Fee', ATUM_TEXT_DOMAIN ) ); ?>
		</div>

		<div class="edit" style="display: none;">
			<input type="text" placeholder="<?php esc_attr_e( 'Fee name', ATUM_TEXT_DOMAIN ); ?>" name="log_item_name[<?php echo absint( $item_id ); ?>]" value="<?php echo ( $item->get_name() ) ? esc_attr( $item->get_name() ) : ''; ?>" />
			<input type="hidden" class="log_item_id" name="log_item_id[]" value="<?php echo absint( $item_id ); ?>" />
			<input type="hidden" name="log_item_tax_class[<?php echo absint( $item_id ); ?>]" value="<?php echo esc_attr( $item->get_tax_class() ); ?>" />
		</div>
	</td>

	<?php do_action( 'atum/inventory_logs/log/fee_item_values', null, $item, $item_id ); ?>

	<td class="item_cost" width="1%">&nbsp;</td>
	<td class="quantity" width="1%">&nbsp;</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php
			echo wc_price( $item->get_total(), array( 'currency' => $currency ) );

			/*if ( $refunded = $order->get_total_refunded_for_item( $item_id, 'fee' ) ) {
				echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $currency ) ) . '</small>';
			}*/
			?>
		</div>

		<div class="edit" style="display: none;">
			<input type="text" name="line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $item->get_total() ) ); ?>" class="line_total wc_input_price" />
		</div>

		<?php /*
		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_total wc_input_price" />
		</div>
        */ ?>
	</td>

	<?php
	if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ):

		foreach ( $log->get_taxes() as $tax_item ):

			$tax_item_id    = $tax_item->get_rate_id();
			$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			?>
			<td class="line_tax" width="1%">
				<div class="view">
					<?php
					echo ( '' !== $tax_item_total ) ? wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) ) : '&ndash;';

					/*if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'fee' ) ) {
						echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $currency ) ) . '</small>';
					}*/
					?>
				</div>

				<div class="edit" style="display: none;">
					<input type="text" name="line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $tax_item_total ) ) ? esc_attr( wc_format_localized_price( $tax_item_total ) ) : ''; ?>" class="line_tax wc_input_price" />
				</div>

				<?php /*
				<div class="refund" style="display: none;">
					<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
				</div>
                */ ?>
			</td>

		<?php endforeach;

	endif; ?>

	<td class="atum-log-edit-line-item">
		<?php if ( $log->is_editable() ): ?>
			<div class="atum-log-edit-line-item-actions">
				<a class="edit-log-item" href="#"></a><a class="delete-log-item" href="#"></a>
			</div>
		<?php endif; ?>
	</td>
</tr>
<?php

do_action( 'atum/inventory_logs/log/after_log_item_fee_html', $item, $log );
