<?php
/**
 * View for the ATUM Order shipping items
 *
 * @since 1.2.4
 *
 * @var \Atum\Components\AtumOrders\Items\AtumOrderItemShipping $item
 * @var \Atum\Components\AtumOrders\Models\AtumOrderModel       $atum_order
 * @var int                                                     $item_id
 * @var array                                                   $shipping_methods
 */

defined( 'ABSPATH' ) || die;

do_action( 'atum/atum_order/before_item_shipping_html', $item, $atum_order );
$currency = $atum_order->currency;

?>
<tr class="shipping <?php echo esc_attr( ! empty( $class ) ? $class : '' ); ?>" data-atum_order_item_id="<?php echo absint( $item_id ); ?>">
	<td class="thumb"><div></div></td>

	<td class="name">
		<div class="view">
			<?php echo esc_html( $item->get_name() ?: __( 'Shipping', ATUM_TEXT_DOMAIN ) ); ?>
		</div>

		<div class="edit" style="display: none;">
			<input type="hidden" name="shipping_method_id[]" value="<?php echo absint( $item_id ); ?>" />
			<input type="text" class="shipping_method_name" placeholder="<?php esc_attr_e( 'Shipping name', ATUM_TEXT_DOMAIN ); ?>" name="shipping_method_title[<?php echo absint( $item_id ); ?>]" value="<?php echo esc_attr( $item->get_name() ); ?>" />
			<select class="shipping_method" name="shipping_method[<?php echo absint( $item_id ); ?>]">
				<optgroup label="<?php esc_attr_e( 'Shipping method', ATUM_TEXT_DOMAIN ); ?>">
					<option value=""><?php esc_html_e( 'N/A', ATUM_TEXT_DOMAIN ); ?></option>
					<?php
					$found_method = FALSE;

					if ( ! empty( $shipping_methods ) ) :

						foreach ( $shipping_methods as $method ) :
							$current_method = ( 0 === strpos( $item->get_method_id(), $method->id ) ) ? $item->get_method_id() : $method->id;

							echo '<option value="' . esc_attr( $current_method ) . '" ' . selected( $item->get_method_id() === $current_method, true, false ) . '>' . esc_html( $method->get_method_title() ) . '</option>';

							if ( $item->get_method_id() === $current_method ) :
								$found_method = true;
							endif;
						endforeach;

						if ( ! $found_method && $item->get_method_id() ) :
							echo '<option value="' . esc_attr( $item->get_method_id() ) . '" selected="selected">' . esc_html__( 'Other', ATUM_TEXT_DOMAIN ) . '</option>';
						else :
							echo '<option value="other">' . esc_html__( 'Other', ATUM_TEXT_DOMAIN ) . '</option>';
						endif;

					endif;
					?>
				</optgroup>
			</select>
		</div>

		<?php do_action( 'atum/atum_order/before_item_meta', $item_id, $item, null, $atum_order ) ?>
		<?php require 'item-meta.php'; ?>
		<?php do_action( 'atum/atum_order/after_item_meta', $item_id, $item, null, $atum_order ) ?>
	</td>

	<?php do_action( 'atum/atum_order/shipping_item_values', null, $item, $item_id ); ?>

	<td class="item_location">&nbsp;</td>
	<td class="item_cost" style="width: 1%">&nbsp;</td>
	<td class="quantity" style="width: 1%">&nbsp;</td>

	<td class="line_cost" style="width: 1%">
		<div class="view">
			<?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<div class="edit" style="display: none;">
			<input type="text" name="shipping_cost[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ) ?>" value="<?php echo esc_attr( wc_format_localized_price( $item->get_total() ) ); ?>" class="line_total wc_input_price" />
		</div>

		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ) ?>" class="refund_line_total wc_input_price" />
		</div>
	</td>

	<?php if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :

		foreach ( $atum_order->get_taxes() as $tax_item ) :

			/**
			 * Variable definition
			 *
			 * @var WC_Order_Item_Tax $tax_item
			 */
			$tax_item_id    = $tax_item->get_rate_id();
			$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			?>
			<td class="line_tax" style="width: 1%">
				<div class="view">
					<?php echo ( '' !== $tax_item_total ? wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) ) : '&ndash;' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="edit" style="display: none;">
					<input type="text" name="shipping_taxes[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ) ?>" value="<?php echo ( isset( $tax_item_total ) ) ? esc_attr( wc_format_localized_price( $tax_item_total ) ) : ''; ?>" class="line_tax wc_input_price" />
				</div>

				<div class="refund" style="display: none;">
					<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ) ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
				</div>
			</td>
			<?php

		endforeach;

	endif; ?>

	<td class="atum-order-edit-line-item" style="width: 1%">
		<div class="atum-order-edit-line-item-actions">
			<?php if ( $atum_order->is_editable() ) : ?>
				<a class="edit-atum-order-item atum-tooltip" href="#" data-tip="<?php esc_attr_e( 'Edit item', ATUM_TEXT_DOMAIN ); ?>"></a><a class="delete-atum-order-item atum-tooltip" href="#" title="<?php esc_attr_e( 'Delete item', ATUM_TEXT_DOMAIN ); ?>"></a>
			<?php endif; ?>
		</div>
	</td>
</tr>
<?php

do_action( 'atum/atum_order/after_item_shipping_html', $item, $atum_order );
