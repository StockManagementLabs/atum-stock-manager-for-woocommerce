<?php
/**
 * View for the Inventory Logs single shipping items
 *
 * @since 1.2.4
 *
 * @var \Atum\InventoryLogs\Items\LogItemShipping $item
 * @var \Atum\InventoryLogs\Models\Log            $log
 * @var int                                       $item_id
 * @var array                                     $shipping_methods
 */

defined( 'ABSPATH' ) or die;

do_action( 'atum/inventory_logs/log/before_log_item_shipping_html', $item, $log );
$currency = $log->get_currency();

?>
<tr class="shipping <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-log_item_id="<?php echo absint( $item_id ); ?>">
	<td class="thumb"><div></div></td>

	<td class="name">
		<div class="view">
			<?php echo esc_html( $item->get_name() ? $item->get_name() : __( 'Shipping', ATUM_TEXT_DOMAIN ) ); ?>
		</div>

		<div class="edit" style="display: none;">
			<input type="hidden" name="shipping_method_id[]" value="<?php echo absint( $item_id ); ?>" />
			<input type="text" class="shipping_method_name" placeholder="<?php esc_attr_e( 'Shipping name', ATUM_TEXT_DOMAIN ); ?>" name="shipping_method_title[<?php echo absint( $item_id ); ?>]" value="<?php echo esc_attr( $item->get_name() ); ?>" />
			<select class="shipping_method" name="shipping_method[<?php echo absint( $item_id ); ?>]">
				<optgroup label="<?php esc_attr_e( 'Shipping method', ATUM_TEXT_DOMAIN ); ?>">
					<option value=""><?php _e( 'N/A', ATUM_TEXT_DOMAIN ); ?></option>
					<?php
						$found_method = false;

						foreach ( $shipping_methods as $method ):
							$current_method = ( 0 === strpos( $item->get_method_id(), $method->id ) ) ? $item->get_method_id() : $method->id;

							echo '<option value="' . esc_attr( $current_method ) . '" ' . selected( $item->get_method_id() === $current_method, true, false ) . '>' . esc_html( $method->get_method_title() ) . '</option>';

							if ( $item->get_method_id() === $current_method ):
								$found_method = true;
							endif;
						endforeach;

						if ( ! $found_method && $item->get_method_id() ):
							echo '<option value="' . esc_attr( $item->get_method_id() ) . '" selected="selected">' . __( 'Other', ATUM_TEXT_DOMAIN ) . '</option>';
						else:
							echo '<option value="other">' . __( 'Other', ATUM_TEXT_DOMAIN ) . '</option>';
						endif;
					?>
				</optgroup>
			</select>
		</div>

		<?php do_action( 'atum/inventory_logs/log/before_item_meta', $item_id, $item, null ) ?>
		<?php include( 'item-meta.php' ); ?>
		<?php do_action( 'atum/inventory_logs/log/after_item_meta', $item_id, $item, null ) ?>
	</td>

	<?php do_action( 'atum/inventory_logs/log/shipping_item_values', null, $item, $item_id ); ?>

	<td class="item_cost" width="1%">&nbsp;</td>
	<td class="quantity" width="1%">&nbsp;</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php
				echo wc_price( $item->get_total(), array( 'currency' => $currency ) );

				/*if ( $refunded = $order->get_total_refunded_for_item( $item_id, 'shipping' ) ) {
					echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $currency ) ) . '</small>';
				}*/
			?>
		</div>
		<div class="edit" style="display: none;">
			<input type="text" name="shipping_cost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $item->get_total() ) ); ?>" class="line_total wc_input_price" />
		</div>
		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_total wc_input_price" />
		</div>
	</td>

	<?php if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ):

		foreach ( $log->get_taxes() as $tax_item ):

			$tax_item_id    = $tax_item->get_rate_id();
			$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			?>
				<td class="line_tax" width="1%">
					<div class="view">
						<?php
							echo ( '' !== $tax_item_total ) ? wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) ) : '&ndash;';

							/*if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'shipping' ) ) {
								echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $currency ) ) . '</small>';
							}*/
						?>
					</div>

					<div class="edit" style="display: none;">
						<input type="text" name="shipping_taxes[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $tax_item_total ) ) ? esc_attr( wc_format_localized_price( $tax_item_total ) ) : ''; ?>" class="line_tax wc_input_price" />
					</div>

					<div class="refund" style="display: none;">
						<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
					</div>
				</td>
			<?php

		endforeach;

	endif; ?>

	<td class="atum-log-edit-line-item">
		<?php if ( $log->is_editable() ) : ?>
			<div class="atum-log-edit-line-item-actions">
				<a class="edit-log-item" href="#"></a><a class="delete-log-item" href="#"></a>
			</div>
		<?php endif; ?>
	</td>
</tr>
<?php

do_action( 'atum/inventory_logs/log/after_log_item_shipping_html', $item, $log );