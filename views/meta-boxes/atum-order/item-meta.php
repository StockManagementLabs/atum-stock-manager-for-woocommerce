<?php
/**
 * View for the ATUM Order items' meta
 *
 * @since 1.2.4
 *
 * @var \Atum\Components\AtumOrders\Models\AtumOrderModel      $atum_order
 * @var \Atum\Components\AtumOrders\Items\AtumOrderItemProduct $item
 * @var int                                                    $item_id
 */

defined( 'ABSPATH' ) || die;

$hidden_item_meta = apply_filters( 'atum/atum_order/hidden_item_meta', array(
	'_qty',
	'_tax_class',
	'_product_id',
	'_variation_id',
	'_line_subtotal',
	'_line_subtotal_tax',
	'_line_total',
	'_line_tax',
	'_line_tax_data',
	'_method_id',
	'_cost',
	'_total_tax',
	'_taxes',
	'_stock_changed',
) );


$meta_data = $item->get_formatted_meta_data( '' );
?>
<div class="view">
	<?php if ( ! empty( $meta_data ) ) : ?>

		<table cellspacing="0" class="display_meta">
			<?php foreach ( $meta_data as $meta_id => $meta ) :

				if ( in_array( $meta->key, $hidden_item_meta, TRUE ) ) :
					continue;
				endif;

				if ( '_order_id' === $meta->display_key ) :
					$meta->display_key = '<strong>' . __( 'Order ID', ATUM_TEXT_DOMAIN ) . '</strong>';
				endif;
				?>
				<tr>
					<th><?php echo wp_kses_post( $meta->display_key ); ?>:</th>
					<td><?php echo wp_kses_post( force_balance_tags( $meta->display_value ) ); ?></td>
				</tr>

			<?php endforeach; ?>
		</table>

	<?php endif; ?>
</div>

<div class="edit" style="display: none;">
	<table class="meta" cellspacing="0">
		<tbody class="meta_items">
			<?php if ( ! empty( $meta_data ) ) : ?>

				<?php foreach ( $meta_data as $meta_id => $meta ) :

					if ( in_array( $meta->key, $hidden_item_meta, TRUE ) ) :
						continue;
					endif;
					?>
					<tr data-meta_id="<?php echo esc_attr( $meta_id ) ?>">
						<td>
							<input type="text" placeholder="<?php esc_attr_e( 'Name (required)', ATUM_TEXT_DOMAIN ) ?>" name="meta_key[<?php echo esc_attr( $item_id ) ?>][<?php echo esc_attr( $meta_id ) ?>]" value="<?php echo esc_attr( $meta->key ) ?>" />
							<textarea placeholder="<?php esc_attr_e( 'Value (required)', ATUM_TEXT_DOMAIN ) ?>" name="meta_value[<?php echo esc_attr( $item_id ) ?>][<?php echo esc_attr( $meta_id ) ?>]"><?php echo esc_textarea( rawurldecode( $meta->value ) ) ?></textarea>
						</td>
						<td style="width: 1%"><button class="remove-atum-order-item-meta button">&times;</button></td>
					</tr>

				<?php endforeach; ?>

			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="4">
					<button class="add-atum-order-item-meta button"><?php esc_html_e( 'Add&nbsp;meta', ATUM_TEXT_DOMAIN ) ?></button>
					<?php do_action( 'atum/atum_order/item_meta_controls', $item, $atum_order ) ?>
				</td>
			</tr>
		</tfoot>
	</table>
</div>
