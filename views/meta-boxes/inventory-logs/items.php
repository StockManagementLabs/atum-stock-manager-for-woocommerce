<?php
/**
 * View for the Inventory Logs items meta box
 *
 * @since 1.2.4
 *
 * @var \Atum\InventoryLogs\Models\Log $log
 */

defined( 'ABSPATH' ) or die;

global $wpdb;

// Get the payment gateway
//$payment_gateway = wc_get_payment_gateway_by_order( $order );

// Get line items
$line_items          = $log->get_items( apply_filters( 'atum/inventory_logs/log/item_types', 'line_item' ) );
$line_items_fee      = $log->get_items( 'fee' );
$line_items_shipping = $log->get_items( 'shipping' );

if ( wc_tax_enabled() ) {
	$log_taxes        = $log->get_taxes();
	$tax_classes      = WC_Tax::get_tax_classes();
	$classes_options  = wc_get_product_tax_class_options();
	$show_tax_columns = sizeof( $log_taxes ) === 1;
}

$currency = $log->get_currency();
?>
<div class="atum_log_items_wrapper atum-log-items-editable">
	<table cellpadding="0" cellspacing="0" class="atum_log_items">

		<thead>
			<tr>
				<th class="item sortable" colspan="2" data-sort="string-ins">
					<?php _e( 'Item', ATUM_TEXT_DOMAIN ); ?>
				</th>
				<?php do_action( 'atum/inventory_logs/log/item_headers', $log ); ?>
				<th class="item_cost sortable" data-sort="float">
					<?php _e( 'Cost', ATUM_TEXT_DOMAIN ); ?>
				</th>
				<th class="quantity sortable" data-sort="int">
					<?php _e( 'Qty', ATUM_TEXT_DOMAIN ); ?>
				</th>
				<th class="line_cost sortable" data-sort="float">
					<?php _e( 'Total', ATUM_TEXT_DOMAIN ); ?>
				</th>

				<?php if ( ! empty( $log_taxes ) ) :

					foreach ( $log_taxes as $tax_id => $tax_item ) :

						$tax_class      = wc_get_tax_class_by_tax_id( $tax_item['rate_id'] );
						$tax_class_name = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax', ATUM_TEXT_DOMAIN );
						$column_label   = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', ATUM_TEXT_DOMAIN );
						$column_tip     = sprintf( esc_html__( '%1$s (%2$s)', ATUM_TEXT_DOMAIN ), $tax_item['name'], $tax_class_name );
						?>
						<th class="line_tax" data-toggle="tooltip" title="<?php echo esc_attr( $column_tip ); ?>">
							<?php echo esc_attr( $column_label ); ?>
							<input type="hidden" class="log-tax-id" name="log_taxes[<?php echo $tax_id; ?>]" value="<?php echo esc_attr( $tax_item['rate_id'] ); ?>">
							<a class="delete-log-tax" href="#" data-rate_id="<?php echo $tax_id; ?>"></a>
						</th>
						<?php

					endforeach;

				endif; ?>

				<th class="atum-log-edit-line-item" width="1%">&nbsp;</th>
			</tr>
		</thead>

		<tbody id="log_line_items">
			<?php
			foreach ( $line_items as $item_id => $item ):

				do_action( 'atum/inventory_logs/log/before_log_item_' . $item->get_type() . '_html', $item_id, $item, $log );
				include( 'item.php' );
				do_action( 'atum/inventory_logs/log/after_log_item_' . $item->get_type() . '_html', $item_id, $item, $log );

			endforeach;

			do_action( 'atum/inventory_logs/log/after_line_items', $log->get_id() );
			?>
		</tbody>

		<tbody id="log_shipping_line_items">
			<?php
			$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();
			foreach ( $line_items_shipping as $item_id => $item ):
				include( 'item-shipping.php' );
			endforeach;

			do_action( 'atum/inventory_logs/log/after_shipping', $log->get_id() );
			?>
		</tbody>

		<tbody id="log_fee_line_items">
			<?php
			foreach ( $line_items_fee as $item_id => $item ):
				include( 'item-fee.php' );
			endforeach;

			do_action( 'atum/inventory_logs/log/after_fees', $log->get_id() );
			?>
		</tbody>

		<?php /*
		<tbody id="log_refunds">
			<?php
			if ( $refunds = $log->get_refunds() ) {
				foreach ( $refunds as $refund ) {
					include( 'refunds.php' );
				}

				do_action( 'atum/inventory_logs/log/after_refunds', $log->get_id() );
			}
			?>
		</tbody>
        */ ?>

	</table>
</div>

<div class="atum-log-data-row atum-log-item-bulk-edit" style="display:none;">
	<button type="button" class="button bulk-delete-items"><?php _e( 'Delete selected row(s)', ATUM_TEXT_DOMAIN ); ?></button>
	<?php /*<button type="button" class="button bulk-decrease-stock"><?php _e( 'Reduce stock', ATUM_TEXT_DOMAIN ); ?></button>
	<button type="button" class="button bulk-increase-stock"><?php _e( 'Increase stock', ATUM_TEXT_DOMAIN ); ?></button>*/ ?>
	<?php do_action( 'atum/inventory_logs/log/item_bulk_actions', $log ); ?>
</div>

<div class="atum-log-data-row atum-log-totals-items atum-log-items-editable">
	<?php
	/*$coupons = $log->get_items( array( 'coupon' ) );

	if ( $coupons ) {
		?>
		<div class="atum-used-coupons">
			<ul class="atum_coupon_list">
				<?php
				echo '<li><strong>' . __( 'Coupon(s)', ATUM_TEXT_DOMAIN ) . '</strong></li>';

				foreach ( $coupons as $item_id => $item ) {
					$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $item->get_code() ) );
					$link    = $post_id ? add_query_arg( array( 'post' => $post_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) : add_query_arg( array( 's' => $item->get_code(), 'post_status' => 'all', 'post_type' => 'shop_coupon' ), admin_url( 'edit.php' ) );

					echo '<li class="code"><a href="' . esc_url( $link ) . '" data-toggle="tooltip" title="' . esc_attr( wc_price( $item->get_discount(), array( 'currency' => $currency ) ) ) . '"><span>' . esc_html( $item->get_code() ) . '</span></a></li>';
				}
				?>
			</ul>
		</div>
		<?php
	}*/
	?>

	<table class="atum-log-totals">
		<tr>
			<td class="label"><span class="atum-help-tip" data-toggle="tooltip" title="<?php esc_attr_e( 'This is the total discount. Discounts are defined per line item.', ATUM_TEXT_DOMAIN ) ?>"></span> <?php _e( 'Discount:', ATUM_TEXT_DOMAIN ); ?></td>
			<td width="1%"></td>
			<td class="total">
				<?php echo wc_price( $log->get_total_discount(), array( 'currency' => $currency ) ); ?>
			</td>
		</tr>

		<?php do_action( 'atum/inventory_logs/log/totals_after_discount', $log->get_id() ); ?>

		<tr>
			<td class="label"><span class="atum-help-tip" data-toggle="tooltip" title="<?php esc_attr_e( 'This is the shipping and handling total costs for the log.', ATUM_TEXT_DOMAIN ) ?>"></span> <?php _e( 'Shipping:', ATUM_TEXT_DOMAIN ); ?></td>
			<td width="1%"></td>
			<td class="total">
				<?php
				/*if ( ( $refunded = $log->get_total_shipping_refunded() ) > 0 ) {
					echo '<del>' . strip_tags( wc_price( $log->get_shipping_total(), array( 'currency' => $currency ) ) ) . '</del> <ins>' . wc_price( $log->get_shipping_total() - $refunded, array( 'currency' => $currency ) ) . '</ins>';
				}
				else {*/
					echo wc_price( $log->get_shipping_total(), array( 'currency' => $currency ) );
				//}
				?>
			</td>
		</tr>

		<?php do_action( 'atum/inventory_logs/log/totals_after_shipping', $log->get_id() ); ?>

		<?php if ( wc_tax_enabled() ) :

			foreach ( $log->get_tax_totals() as $code => $tax ) : ?>
				<tr>
					<td class="label"><?php echo $tax->label; ?>:</td>
					<td width="1%"></td>
					<td class="total"><?php
						/*if ( ( $refunded = $log->get_total_tax_refunded_by_rate_id( $tax->rate_id ) ) > 0 ) {
							echo '<del>' . strip_tags( $tax->formatted_amount ) . '</del> <ins>' . wc_price( WC_Tax::round( $tax->amount, wc_get_price_decimals() ) - WC_Tax::round( $refunded, wc_get_price_decimals() ), array( 'currency' => $currency ) ) . '</ins>';
						}
						else {*/
							echo $tax->formatted_amount;
						//}
						?></td>
				</tr>
			<?php endforeach;

		endif; ?>

		<?php do_action( 'atum/inventory_logs/log/totals_after_tax', $log->get_id() ); ?>

		<tr>
			<td class="label"><?php _e( 'Log total', ATUM_TEXT_DOMAIN ); ?>:</td>
			<td width="1%"></td>
			<td class="total">
				<?php echo $log->get_formatted_total(); ?>
			</td>
		</tr>

		<?php do_action( 'atum/inventory_logs/log/totals_after_total', $log->get_id() ); ?>

		<?php /*if ( $log->get_total_refunded() ) : ?>
			<tr>
				<td class="label refunded-total"><?php _e( 'Refunded', ATUM_TEXT_DOMAIN ); ?>:</td>
				<td width="1%"></td>
				<td class="total refunded-total">-<?php echo wc_price( $log->get_total_refunded(), array( 'currency' => $currency ) ); ?></td>
			</tr>
		<?php endif; ?>

		<?php do_action( 'atum/inventory_logs/log/totals_after_refunded', $log->get_id() );*/ ?>

	</table>

	<div class="clear"></div>
</div>

<div class="atum-log-data-row atum-log-bulk-actions atum-log-data-row-toggle">
	<p class="add-items">

		<?php if ( $log->is_editable() ) : ?>
			<button type="button" class="button add-line-item"><?php _e( 'Add item(s)', ATUM_TEXT_DOMAIN ); ?></button>
		<?php else : ?>
			<span class="description"><span class="atum-help-tip" data-toggle="tooltip" title="<?php esc_attr_e( "To edit log items change the status back to 'Pending'", ATUM_TEXT_DOMAIN ) ?>"></span> <?php _e( 'These log items are no longer editable.', ATUM_TEXT_DOMAIN ); ?></span>
		<?php endif;

		if ( wc_tax_enabled() && $log->is_editable() ) : ?>
			<button type="button" class="button add-log-tax"><?php _e( 'Add tax', ATUM_TEXT_DOMAIN ); ?></button>
		<?php endif;

		/*if ( 0 < $log->get_total() - $log->get_total_refunded() || 0 < absint( $log->get_item_count() - $log->get_item_count_refunded() ) ) : ?>
			<button type="button" class="button refund-items"><?php _e( 'Refund', ATUM_TEXT_DOMAIN ); ?></button>
		<?php endif;*/

		// allow adding custom buttons
		do_action( 'atum/inventory_logs/log/add_action_buttons', $log );

		if ( $log->is_editable() ) : ?>
			<button type="button" class="button button-primary calculate-action"><?php _e( 'Recalculate', ATUM_TEXT_DOMAIN ); ?></button>
		<?php endif; ?>

	</p>
</div>

<div class="atum-log-data-row atum-log-add-item atum-log-data-row-toggle" style="display:none;">
	<button type="button" class="button add-log-item"><?php _e( 'Add product(s)', ATUM_TEXT_DOMAIN ); ?></button>
	<button type="button" class="button add-log-fee"><?php _e( 'Add fee', ATUM_TEXT_DOMAIN ); ?></button>
	<button type="button" class="button add-log-shipping"><?php _e( 'Add shipping cost', ATUM_TEXT_DOMAIN ); ?></button>
	<?php
	// allow adding custom buttons
	do_action( 'atum/inventory_logs/log/add_line_buttons', $log ); ?>
	<button type="button" class="button cancel-action"><?php _e( 'Cancel', ATUM_TEXT_DOMAIN ); ?></button>
	<button type="button" class="button button-primary save-action"><?php _e( 'Save', ATUM_TEXT_DOMAIN ); ?></button>
</div>

<?php /*if ( 0 < $log->get_total() - $log->get_total_refunded() || 0 < absint( $log->get_item_count() - $log->get_item_count_refunded() ) ) : ?>

	<div class="atum-log-data-row atum-log-refund-items atum-log-data-row-toggle" style="display: none;">
		<table class="atum-log-totals">
			<tr style="display:none;">
				<td class="label"><label for="restock_refunded_items"><?php _e( 'Restock refunded items', ATUM_TEXT_DOMAIN ); ?>:</label></td>
				<td class="total"><input type="checkbox" id="restock_refunded_items" name="restock_refunded_items" checked="checked" /></td>
			</tr>
			<tr>
				<td class="label"><?php _e( 'Amount already refunded', ATUM_TEXT_DOMAIN ); ?>:</td>
				<td class="total">-<?php echo wc_price( $log->get_total_refunded(), array( 'currency' => $log->get_currency() ) ); ?></td>
			</tr>
			<tr>
				<td class="label"><?php _e( 'Total available to refund', ATUM_TEXT_DOMAIN ); ?>:</td>
				<td class="total"><?php echo wc_price( $log->get_total() - $log->get_total_refunded(), array( 'currency' => $log->get_currency() ) ); ?></td>
			</tr>
			<tr>
				<td class="label"><label for="refund_amount"><?php _e( 'Refund amount', ATUM_TEXT_DOMAIN ); ?>:</label></td>
				<td class="total">
					<input type="text" class="text" id="refund_amount" name="refund_amount" class="wc_input_price" />
					<div class="clear"></div>
				</td>
			</tr>
			<tr>
				<td class="label"><label for="refund_reason"><span class="atum-help-tip" data-toggle="tooltip" title="<?php esc_attr_e( 'Note: the refund reason will be visible by the customer.', ATUM_TEXT_DOMAIN ) ?>"></span> <?php _e( 'Reason for refund (optional):', ATUM_TEXT_DOMAIN ); ?></label></td>
				<td class="total">
					<input type="text" class="text" id="refund_reason" name="refund_reason" />
					<div class="clear"></div>
				</td>
			</tr>
		</table>

		<div class="clear"></div>

		<div class="refund-actions">
			<?php
			$refund_amount            = '<span class="atum-log-refund-amount">' . wc_price( 0, array( 'currency' => $log->get_currency() ) ) . '</span>';
			$gateway_supports_refunds = false !== $payment_gateway && $payment_gateway->supports( 'refunds' );
			$gateway_name             = false !== $payment_gateway ? ( ! empty( $payment_gateway->method_title ) ? $payment_gateway->method_title : $payment_gateway->get_title() ) : __( 'Payment gateway', ATUM_TEXT_DOMAIN );
			?>
			<button type="button" class="button <?php echo $gateway_supports_refunds ? 'button-primary do-api-refund' : 'disabled'; ?>" <?php echo $gateway_supports_refunds ? '' : 'data-toggle="tooltip" title="' . esc_attr__( 'The payment gateway used to place this order does not support automatic refunds.', ATUM_TEXT_DOMAIN ) . '"'; ?>><?php printf( __( 'Refund %1$s via %2$s', ATUM_TEXT_DOMAIN ), $refund_amount, $gateway_name ); ?></button>
			<button type="button" class="button button-primary do-manual-refund" data-toggle="tooltip" title="<?php esc_attr_e( 'You will need to manually issue a refund through your payment gateway after using this.', ATUM_TEXT_DOMAIN ); ?>"><?php printf( __( 'Refund %s manually', ATUM_TEXT_DOMAIN ), $refund_amount ); ?></button>
			<button type="button" class="button cancel-action"><?php _e( 'Cancel', ATUM_TEXT_DOMAIN ); ?></button>
			<div class="clear"></div>
		</div>

	</div>

<?php endif;*/ ?>

<script type="text/template" id="tmpl-atum-modal-add-products">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">

				<header class="wc-backbone-modal-header">
					<h1><?php _e( 'Add products', ATUM_TEXT_DOMAIN ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php _e('Close modal panel', ATUM_TEXT_DOMAIN) ?></span>
					</button>
				</header>

				<article>
					<form action="" method="post">
						<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="add_item_id" name="add_log_items[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', ATUM_TEXT_DOMAIN ); ?>"></select>
					</form>
				</article>

				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php _e( 'Add', ATUM_TEXT_DOMAIN ); ?></button>
					</div>
				</footer>

			</section>
		</div>
	</div>

	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/template" id="tmpl-atum-modal-add-tax">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">

				<header class="wc-backbone-modal-header">
					<h1><?php _e( 'Add tax', ATUM_TEXT_DOMAIN ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php _e('Close modal panel', ATUM_TEXT_DOMAIN) ?></span>
					</button>
				</header>

				<article>
					<form action="" method="post">
						<table class="widefat">
							<thead>
							<tr>
								<th>&nbsp;</th>
								<th><?php _e( 'Rate name', ATUM_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Tax class', ATUM_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Rate code', ATUM_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Rate %', ATUM_TEXT_DOMAIN ); ?></th>
							</tr>
							</thead>
							<?php
							$rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name LIMIT 100" );

							foreach ( $rates as $rate ) {
								echo '
									<tr>
										<td><input type="radio" id="add_log_tax_' . absint( $rate->tax_rate_id ) . '" name="add_log_tax" value="' . absint( $rate->tax_rate_id ) . '" /></td>
										<td><label for="add_log_tax_' . absint( $rate->tax_rate_id ) . '">' . WC_Tax::get_rate_label( $rate ) . '</label></td>
										<td>' . ( isset( $classes_options[ $rate->tax_rate_class ] ) ? $classes_options[ $rate->tax_rate_class ] : '-' ) . '</td>
										<td>' . WC_Tax::get_rate_code( $rate ) . '</td>
										<td>' . WC_Tax::get_rate_percent( $rate ) . '</td>
									</tr>
								';
							}
							?>
						</table>

						<?php if ( absint( $wpdb->get_var( "SELECT COUNT(tax_rate_id) FROM {$wpdb->prefix}woocommerce_tax_rates;" ) ) > 100 ) : ?>
							<p>
								<label for="manual_tax_rate_id"><?php _e( 'Or, enter tax rate ID:', ATUM_TEXT_DOMAIN ); ?></label><br/>
								<input type="number" name="manual_tax_rate_id" id="manual_tax_rate_id" step="1" placeholder="<?php esc_attr_e( 'Optional', ATUM_TEXT_DOMAIN ); ?>" />
							</p>
						<?php endif; ?>
					</form>
				</article>

				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php _e( 'Add', ATUM_TEXT_DOMAIN ); ?></button>
					</div>
				</footer>

			</section>
		</div>
	</div>

	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>