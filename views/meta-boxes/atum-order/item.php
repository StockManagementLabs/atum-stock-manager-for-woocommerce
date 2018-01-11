<?php
/**
 * View for the ATUM Order product items
 *
 * @since 1.2.4
 *
 * @var \Atum\Components\AtumOrders\Models\AtumOrderModel      $atum_order
 * @var \Atum\Components\AtumOrders\Items\AtumOrderItemProduct $item
 * @var int                                                    $item_id
 * @var string                                                 $class
 */

defined( 'ABSPATH' ) or die;

use Atum\Inc\Globals;
use Atum\Components\AtumCapabilities;

do_action( 'atum/atum_order/before_item_product_html', $item, $atum_order );

$product = $item->get_product();

if ( empty($product) ) return;

$product_id   = ( $product->get_type() == 'variation' ) ? $product->get_parent_id() : $product->get_id();
$product_link = $product ? admin_url( 'post.php?post=' . $item->get_product_id() . '&action=edit' ) : '';
$thumbnail    = $product ? apply_filters( 'atum/atum_order/item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
?>
<tr class="item <?php echo apply_filters( 'atum/atum_order/item_class', ( ! empty( $class ) ? $class : '' ), $item, $atum_order ); ?>" data-atum_order_item_id="<?php echo absint( $item_id ); ?>">
	<td class="thumb">
		<?php echo '<div class="atum-order-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>'; ?>
	</td>

	<td class="name" data-sort-value="<?php echo esc_attr( $item->get_name() ); ?>">
		<?php
			echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="atum-order-item-name">' . esc_html( $item->get_name() ) . '</a>' : '<div class="atum-order-item-name">' . esc_html( $item->get_name() ) . '</div>';

			if ( $product && $product->get_sku() ): ?>
				<div class="atum-order-item-sku"><strong><?php _e( 'SKU:', ATUM_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $product->get_sku() ) ?></div>
			<?php endif;

			if ( $product && AtumCapabilities::current_user_can('read_supplier') ):
				$supplier_sku = get_post_meta($product_id, '_supplier_sku', TRUE);

				if ($supplier_sku): ?>
					<div class="atum-order-item-sku"><strong><?php _e( 'Supplier SKU:', ATUM_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $supplier_sku ) ?></div>
				<?php endif;
			endif;

			if ( $item->get_variation_id() ) : ?>
				<div class="atum-order-item-variation"><strong><?php _e( 'Variation ID:', ATUM_TEXT_DOMAIN ) ?></strong>

					<?php if ( 'product_variation' == get_post_type( $item->get_variation_id() ) ):
						echo esc_html( $item->get_variation_id() );
					else :
						printf( esc_html__( '%s (No longer exists)', ATUM_TEXT_DOMAIN ), $item->get_variation_id() );
					endif; ?>

				</div>
			<?php endif; ?>

		<input type="hidden" class="atum_order_item_id" name="atum_order_item_id[]" value="<?php echo esc_attr( $item_id ); ?>" />
		<input type="hidden" name="atum_order_item_tax_class[<?php echo absint( $item_id ); ?>]" value="<?php echo esc_attr( $item->get_tax_class() ); ?>" />

		<?php do_action( 'atum/atum_order/before_item_meta', $item_id, $item, $product ) ?>
		<?php include( 'item-meta.php' ); ?>
		<?php do_action( 'atum/atum_order/ater_item_meta', $item_id, $item, $product ) ?>
	</td>

	<?php
	do_action( 'atum/atum_order/item_values', $product, $item, absint( $item_id ) );

	$locations      = wc_get_product_terms( $product_id, Globals::PRODUCT_LOCATION_TAXONOMY, array( 'fields' => 'names' ) );
	$locations_list = ( ! empty( $locations ) ) ? implode( ', ', $locations ) : '&ndash;';
	?>
	<td class="item_location"<?php if ($product) echo ' data-sort-value="' . $locations_list . '"' ?>>
		<?php echo $locations_list ?>
	</td>

	<td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $atum_order->get_item_subtotal( $item, FALSE, TRUE ) ); ?>">
		<div class="view">
			<?php
				$currency = $atum_order->get_currency();
				echo wc_price( $atum_order->get_item_total( $item, FALSE, TRUE ), array( 'currency' => $currency ) );

				if ( $item->get_subtotal() != $item->get_total() ) : ?>
					<span class="atum-order-item-discount">-<?php echo wc_price( wc_format_decimal( $atum_order->get_item_subtotal( $item, FALSE, FALSE ) - $atum_order->get_item_total( $item, FALSE, FALSE ), '' ), array( 'currency' => $currency ) ) ?></span>
				<?php endif; ?>
		</div>
	</td>

	<td class="quantity" width="1%">

		<div class="view">
			<small class="times">&times;</small> <?php echo esc_html( $item->get_quantity() ); ?>
		</div>

		<div class="edit" style="display: none;">
			<input type="number" step="<?php echo apply_filters( 'atum/atum_order/quantity_input_step', '1', $product ); ?>" min="0" autocomplete="off" name="atum_order_item_qty[<?php echo absint( $item_id ); ?>]" placeholder="0" value="<?php echo esc_attr( $item->get_quantity() ); ?>" data-qty="<?php echo esc_attr( $item->get_quantity() ); ?>" size="4" class="quantity" />
		</div>

	</td>
	<td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( $item->get_total() ); ?>">
		<div class="view">
			<?php
				echo wc_price( $item->get_total(), array( 'currency' => $currency ) );

				if ( $item->get_subtotal() != $item->get_total() ) : ?>
					<span class="atum-order-item-discount">-<?php echo wc_price( wc_format_decimal( $item->get_subtotal() - $item->get_total(), '' ), array( 'currency' => $currency ) ) ?></span>
				<?php endif; ?>
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

	</td>

	<?php
	if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :

		foreach ( $atum_order->get_taxes() as $tax_item ) :
			$tax_item_id       = $tax_item->get_rate_id();
			$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';
			?>
			<td class="line_tax" width="1%">
				<div class="view">
					<?php
						if ( '' != $tax_item_total ):
							echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) );
						else:
							echo '&ndash;';
						endif;

						if ( $item->get_subtotal() != $item->get_total() ):
							if ( '' === $tax_item_total ) : ?>
								<span class="atum-order-item-discount">&ndash;</span>
							<?php else : ?>
								<span class="atum-order-item-discount">-<?php echo wc_price( wc_round_tax_total( $tax_item_subtotal - $tax_item_total ), array( 'currency' => $currency ) ) ?></span>
							<?php endif;
						endif;
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

			</td>
		<?php endforeach;

	endif; ?>

	<td class="atum-order-edit-line-item" width="1%">
		<div class="atum-order-edit-line-item-actions">
			<?php if ( $atum_order->is_editable() ) : ?>
				<a class="edit-atum-order-item" href="#" data-toggle="tooltip" title="<?php esc_attr_e( 'Edit item', ATUM_TEXT_DOMAIN ); ?>"></a><a class="delete-atum-order-item" href="#" data-toggle="tooltip" title="<?php esc_attr_e( 'Delete item', ATUM_TEXT_DOMAIN ); ?>"></a>
			<?php endif; ?>
		</div>
	</td>
</tr>
<?php

do_action( 'atum/atum_order/after_item_product_html', $item, $atum_order );
