<?php
/**
 * View for the ATUM Order items meta box
 *
 * @since 1.2.4
 *
 * @var \Atum\PurchaseOrders\Models\PurchaseOrder|\Atum\InventoryLogs\Models\Log $atum_order
 */

defined( 'ABSPATH' ) || die;

use Atum\PurchaseOrders\PurchaseOrders;

global $wpdb;

// Get line items.
$line_items          = $atum_order->get_items( apply_filters( 'atum/atum_order/item_types', 'line_item' ) );
$line_items_fee      = $atum_order->get_items( 'fee' );
$line_items_shipping = $atum_order->get_items( 'shipping' );

if ( wc_tax_enabled() ) {
	$taxes            = $atum_order->get_taxes();
	$tax_classes      = WC_Tax::get_tax_classes();
	$classes_options  = wc_get_product_tax_class_options();
	$show_tax_columns = 1 === count( $taxes );
}

$currency    = $atum_order->currency;
$post_type   = get_post_type_object( get_post_type( $atum_order->get_id() ) );
$post_search = PurchaseOrders::get_post_type() === $post_type->name ? 'data-limit="' . $atum_order->get_id() . '"' : '';
?>

<div class="atum-meta-box <?php echo esc_attr( $post_type->name ) ?>_items">

	<?php do_action( 'atum/atum_order/before_items_meta_box', $atum_order ) ?>

	<?php if ( PurchaseOrders::POST_TYPE === $post_type->name ) : ?>
		<?php // ! wp_doing_ajax allow add taxes in still non-created order items.
		$add_blocker = ( ! $atum_order->get_status() && ! wp_doing_ajax() ) || ( ! $atum_order->has_multiple_suppliers() && ! $atum_order->supplier ); ?>
		<div class="items-blocker<?php if ( ! $add_blocker ) echo ' unblocked' ?>">
			<h3><?php echo esc_attr( $atum_order->get_block_message() ) ?></h3>
		</div>
	<?php endif; ?>

	<div class="atum_order_items_wrapper">
		<table cellpadding="0" cellspacing="0" class="atum_order_items">

			<thead>
				<tr>
					<th class="item sortable" colspan="2" data-sort="string-ins">
						<?php esc_html_e( 'Item', ATUM_TEXT_DOMAIN ); ?>
					</th>

					<?php do_action( 'atum/atum_order/item_headers', $atum_order ); ?>

					<th class="item_location sortable" data-sort="string-ins">
						<?php esc_html_e( 'Location', ATUM_TEXT_DOMAIN ) ?>
					</th>

					<th class="item_cost sortable" data-sort="float">
						<?php esc_html_e( 'Cost', ATUM_TEXT_DOMAIN ); ?>
					</th>

					<th class="quantity sortable" data-sort="int">
						<?php esc_html_e( 'Qty', ATUM_TEXT_DOMAIN ); ?>
					</th>

					<th class="line_cost sortable" data-sort="float">
						<?php esc_html_e( 'Total', ATUM_TEXT_DOMAIN ); ?>
					</th>

					<?php if ( ! empty( $taxes ) ) :

						foreach ( $taxes as $tax_id => $tax_item ) :

							$tax_class      = wc_get_tax_class_by_tax_id( $tax_item['rate_id'] );
							$tax_class_name = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax', ATUM_TEXT_DOMAIN );
							$column_label   = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', ATUM_TEXT_DOMAIN );
							/* translators: first one is the tax name and second is the tax class name */
							$column_tip = sprintf( esc_html__( '%1$s (%2$s)', ATUM_TEXT_DOMAIN ), $tax_item['name'], $tax_class_name );
							?>
							<th class="line_tax atum-tooltip" data-tip="<?php echo esc_attr( $column_tip ); ?>">
								<?php echo esc_attr( $column_label ); ?>
								<input type="hidden" class="atum-order-tax-id" name="atum_order_taxes[<?php echo esc_attr( $tax_id ) ?>]" value="<?php echo esc_attr( $tax_item['rate_id'] ); ?>">
								<a class="delete-atum-order-tax" href="#" data-rate_id="<?php echo esc_attr( $tax_id ) ?>"></a>
							</th>
							<?php

						endforeach;

					endif; ?>

					<th class="atum-order-edit-line-item" style="width: 1%">&nbsp;</th>
				</tr>
			</thead>

			<tbody id="atum_order_line_items">
				<?php
				foreach ( $line_items as $item_id => $item ) :

					do_action( 'atum/atum_order/before_item_' . $item->get_type() . '_html', $item_id, $item, $atum_order );
					include 'item.php';
					do_action( 'atum/atum_order/after_item_' . $item->get_type() . '_html', $item_id, $item, $atum_order );

				endforeach;

				do_action( 'atum/atum_order/after_line_items', $atum_order->get_id() );
				?>
			</tbody>

			<tbody id="atum_order_shipping_line_items">
				<?php
				$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();
				foreach ( $line_items_shipping as $item_id => $item ) :
					include 'item-shipping.php';
				endforeach;

				do_action( 'atum/atum_order/after_shipping', $atum_order->get_id() );
				?>
			</tbody>

			<tbody id="atum_order_fee_line_items">
				<?php
				foreach ( $line_items_fee as $item_id => $item ) :
					include 'item-fee.php';
				endforeach;

				do_action( 'atum/atum_order/after_fees', $atum_order->get_id() );
				?>
			</tbody>

		</table>
	</div>

	<?php if ( version_compare( WC()->version, '3.5.0', '<' ) || PurchaseOrders::get_post_type() !== $post_type->name ) : // Only allow bulk edit before 3.5.0 VC Version. ?>
	<div class="atum-order-data-row atum-order-item-bulk-edit" style="display:none;">
		<button type="button" class="button bulk-delete-items"><?php esc_html_e( 'Delete selected row(s)', ATUM_TEXT_DOMAIN ); ?></button>
		<?php do_action( 'atum/atum_order/item_bulk_controls', $atum_order ); ?>
	</div>
	<?php endif; ?>

	<div class="atum-order-data-row atum-order-totals-items">

		<table class="atum-order-totals">
			
			<?php if ( ! empty( $line_items_fee ) ) : ?>
				<tr>
					<td class="label">
						<?php esc_html_e( 'Fees:', ATUM_TEXT_DOMAIN ); ?>
					</td>
					<td style="width: 1%"></td>
					<td class="total">
						<?php echo wc_price( $atum_order->get_total_fees(), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
			<?php endif; ?>

			<?php if ( ! empty( $line_items_shipping ) ) : ?>
				<tr>
					<td class="label">
						<?php /* translators: the post type name */ ?>
						<span class="atum-help-tip atum-tooltip" data-tip="<?php esc_attr_e( sprintf( 'This is the shipping and handling total costs for this %s.', strtolower( $post_type->labels->singular_name ) ), ATUM_TEXT_DOMAIN ) ?>"></span> <?php esc_html_e( 'Shipping:', ATUM_TEXT_DOMAIN ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?>
					</td>
					<td style="width: 1%"></td>
					<td class="total">
						<?php echo wc_price( $atum_order->shipping_total, [ 'currency' => $currency ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>

				<?php do_action( 'atum/atum_order/totals_after_shipping', $atum_order->get_id() ); ?>
			<?php endif; ?>


			<?php if ( wc_tax_enabled() ) :

				$tax_totals = $atum_order->get_tax_totals();

				if ( ! empty( $tax_totals ) ) :

					foreach ( $tax_totals as $code => $tax ) : ?>
						<tr>
							<td class="label"><?php echo esc_html( $tax->label ) ?>:</td>
							<td style="width: 1%"></td>
							<td class="total"><?php echo $tax->formatted_amount; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						</tr>
					<?php endforeach; ?>

					<tr>
						<td class="label"><?php esc_html_e( 'Subtotal', ATUM_TEXT_DOMAIN ) ?>:</td>
						<td style="width: 1%"></td>
						<td class="total">
							<?php echo $atum_order->get_formatted_total( '', TRUE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>

				<?php endif;

			endif; ?>

			<?php do_action( 'atum/atum_order/totals_after_tax', $atum_order->get_id() ); ?>

			<tr>
				<td class="label">
					<?php
					/* translators: the post type name */
					printf( esc_html__( '%s Total', ATUM_TEXT_DOMAIN ), esc_html( $post_type->labels->singular_name ) ) ?>:
				</td>
				<td style="width: 1%"></td>
				<td class="total">
					<?php echo $atum_order->get_formatted_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>

			<?php do_action( 'atum/atum_order/totals_after_total', $atum_order->get_id() ); ?>

		</table>

		<div class="clear"></div>
	</div>

	<div class="atum-order-data-row atum-order-bulk-actions atum-order-data-row-toggle">
		<p class="add-items">

			<?php if ( $atum_order->is_editable() ) : ?>
				<button type="button" class="button add-line-item"><?php esc_html_e( 'Add item(s)', ATUM_TEXT_DOMAIN ); ?></button>
			<?php else : ?>
				<span class="description">
					<?php /* translators: the post type name */ ?>
					<span class="atum-help-tip atum-tooltip" data-tip="<?php printf( esc_attr__( "To edit %s items change the status back to 'Pending'", ATUM_TEXT_DOMAIN ), esc_attr( strtolower( $post_type->labels->singular_name ) ) ) ?>"></span> <?php printf( esc_html__( 'These %s items are no longer editable.', ATUM_TEXT_DOMAIN ), esc_attr( strtolower( $post_type->labels->singular_name ) ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?>
				</span>
			<?php endif;

			// Allow adding custom buttons.
			do_action( 'atum/atum_order/add_action_buttons', $atum_order ); ?>

			<?php if ( $atum_order->is_editable() ) : ?>
				<button type="button" class="button button-primary calculate-action"><?php esc_html_e( 'Recalculate', ATUM_TEXT_DOMAIN ); ?></button>
			<?php endif; ?>

		</p>
	</div>

	<div class="atum-order-data-row atum-order-add-item atum-order-data-row-toggle" style="display:none;">
		<button type="button" class="button add-atum-order-item"><?php esc_html_e( 'Add product(s)', ATUM_TEXT_DOMAIN ); ?></button>
		<button type="button" class="button add-atum-order-fee"><?php esc_html_e( 'Add fee', ATUM_TEXT_DOMAIN ); ?></button>
		<button type="button" class="button add-atum-order-shipping"><?php esc_html_e( 'Add shipping cost', ATUM_TEXT_DOMAIN ); ?></button>

		<?php if ( wc_tax_enabled() && $atum_order->is_editable() ) : ?>
		<button type="button" class="button add-atum-order-tax"><?php esc_html_e( 'Add tax', ATUM_TEXT_DOMAIN ); ?></button>
		<?php endif;

		// Allow adding custom buttons.
		do_action( 'atum/atum_order/add_line_buttons', $atum_order ); ?>
		<button type="button" class="button cancel-action"><?php esc_html_e( 'Cancel', ATUM_TEXT_DOMAIN ); ?></button>
		<button type="button" class="button button-primary save-action"><?php esc_html_e( 'Save', ATUM_TEXT_DOMAIN ); ?></button>
	</div>

	<script type="text/template" id="tmpl-atum-modal-add-products">
		<div class="wc-backbone-modal">
			<div class="wc-backbone-modal-content">
				<section class="wc-backbone-modal-main" role="main">

					<header class="wc-backbone-modal-header">
						<h1><?php esc_html_e( 'Add products', ATUM_TEXT_DOMAIN ); ?></h1>
						<button class="modal-close modal-close-link">
							<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', ATUM_TEXT_DOMAIN ) ?></span>
						</button>
					</header>

					<article>
						<?php do_action( 'atum/atum_order/before_product_search_modal', $atum_order ); ?>
						<form action="" method="post">
							<select class="wc-product-search atum-enhanced-select" multiple="multiple" style="width: 50%;" id="add_item_id" name="add_atum_order_items[]"
								data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', ATUM_TEXT_DOMAIN ); ?>" data-action="atum_json_search_products"
								<?php echo $post_search; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></select>
						</form>
					</article>

					<footer>
						<div class="inner">
							<button id="btn-ok" class="button button-primary button-large"><?php esc_html_e( 'Add', ATUM_TEXT_DOMAIN ); ?></button>
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
						<h1><?php esc_html_e( 'Add tax', ATUM_TEXT_DOMAIN ); ?></h1>
						<button class="modal-close modal-close-link">
							<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', ATUM_TEXT_DOMAIN ) ?></span>
						</button>
					</header>

					<article>
						<form action="" method="post">
							<table class="widefat">
								<thead>
								<tr>
									<th>&nbsp;</th>
									<th><?php esc_html_e( 'Rate name', ATUM_TEXT_DOMAIN ); ?></th>
									<th><?php esc_html_e( 'Tax class', ATUM_TEXT_DOMAIN ); ?></th>
									<th><?php esc_html_e( 'Rate code', ATUM_TEXT_DOMAIN ); ?></th>
									<th><?php esc_html_e( 'Rate %', ATUM_TEXT_DOMAIN ); ?></th>
								</tr>
								</thead>
								<?php
								$rates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name LIMIT 100" );

								foreach ( $rates as $rate ) : ?>
									<tr>
										<td><input type="radio" id="add_atum_order_tax_<?php echo absint( $rate->tax_rate_id ) ?>" name="add_atum_order_tax" value="<?php echo absint( $rate->tax_rate_id ) ?>" /></td>
										<td><label for="add_atum_order_tax_<?php echo absint( $rate->tax_rate_id ) ?>"><?php echo esc_html( WC_Tax::get_rate_label( $rate ) ) ?></label></td>
										<td><?php echo esc_html( isset( $classes_options[ $rate->tax_rate_class ] ) ? $classes_options[ $rate->tax_rate_class ] : '-' ) ?></td>
										<td><?php echo esc_html( WC_Tax::get_rate_code( $rate ) ) ?></td>
										<td><?php echo esc_html( WC_Tax::get_rate_percent( $rate ) ) ?></td>
									</tr>
								<?php endforeach; ?>
							</table>

							<?php if ( absint( $wpdb->get_var( "SELECT COUNT(tax_rate_id) FROM {$wpdb->prefix}woocommerce_tax_rates;" ) ) > 100 ) : ?>
								<p>
									<label for="manual_tax_rate_id"><?php esc_html_e( 'Or, enter tax rate ID:', ATUM_TEXT_DOMAIN ); ?></label><br/>
									<input type="number" name="manual_tax_rate_id" id="manual_tax_rate_id" step="1" placeholder="<?php esc_attr_e( 'Optional', ATUM_TEXT_DOMAIN ); ?>" />
								</p>
							<?php endif; ?>
						</form>
					</article>

					<footer>
						<div class="inner">
							<button id="btn-ok" class="button button-primary button-large"><?php esc_html_e( 'Add', ATUM_TEXT_DOMAIN ); ?></button>
						</div>
					</footer>

				</section>
			</div>
		</div>

		<div class="wc-backbone-modal-backdrop modal-close"></div>
	</script>

	<?php do_action( 'atum/atum_order/after_items_meta_box', $atum_order ) ?>

</div>
