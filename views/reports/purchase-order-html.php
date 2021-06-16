<?php
/**
 * View for the Purchase Orders' PDF
 *
 * @since 1.4.0
 *
 * @var \Atum\PurchaseOrders\Exports\POExport $po
 * @var int                                   $desc_percent
 * @var float                                 $discount
 * @var int                                   $total_text_colspan
 * @var string                                $currency
 * @var array                                 $line_items_shipping
 * @var WP_Post_Type                          $post_type
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Inc\Helpers;
?>
<div class="po-wrapper content-header">
	<div class="float-left">
		<strong><?php echo preg_replace( '/<br/', '</strong><br', $po->get_company_address(), 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php $vat_number = $po->get_tax_number() ?>

		<?php if ( $vat_number ) : ?>
			<br>
			<?php
			/* translators: the VAT number */
			printf( esc_html__( 'Tax/VAT number: %s', ATUM_TEXT_DOMAIN ), $vat_number ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	</div>
	<div class="float-right">
		<h3 class="po-title"><?php esc_html_e( 'Purchase Order', ATUM_TEXT_DOMAIN ) ?></h3>
		<div class="content-header-po-data">
			<div class="row">
				<span class="label"><?php esc_html_e( 'Date:', ATUM_TEXT_DOMAIN ) ?>&nbsp;&nbsp;</span>
				<span class="field"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $po->date_created ) ) ) ?></span>
			</div>
			<div class="row">
				<span class="label"><?php esc_html_e( 'P.O. #:', ATUM_TEXT_DOMAIN ) ?>&nbsp;&nbsp;</span>
				<span class="field"><?php echo esc_html( $po->get_id() ) ?></span>
			</div>
		</div>
	</div>
	<div class="spacer" style="clear: both;"></div>
</div>
<div class="po-wrapper content-address">
	<div class="float-left">
		<h4><?php esc_html_e( 'Supplier', ATUM_TEXT_DOMAIN ) ?></h4>
		<p class="address">
			<?php echo wp_kses_post( $po->get_supplier_address() ) ?>
		</p>
	</div>
	<div class="float-right">
		<h4><?php esc_html_e( 'Ship To', ATUM_TEXT_DOMAIN ) ?></h4>
		<p class="address">
			<?php echo wp_kses_post( $po->get_shipping_address() ) ?>
		</p>
	</div>
	<div class="spacer" style="clear: both;"></div>
</div>
<div class="po-wrapper content-lines">
	<table class="">
		<thead>
			<tr class="po-li-head">
				<th class="description" style="width:<?php echo esc_attr( $desc_percent ) ?>%"><?php esc_html_e( 'Item', ATUM_TEXT_DOMAIN ) ?></th>
				<th class="qty"><?php esc_html_e( 'Qty', ATUM_TEXT_DOMAIN ) ?></th>
				<th class="price"><?php esc_html_e( 'Unit Price', ATUM_TEXT_DOMAIN ) ?></th>

				<?php if ( $discount ) : ?>
					<th class="discount"><?php esc_html_e( 'Discount', ATUM_TEXT_DOMAIN ) ?></th>
				<?php endif; ?>

				<?php if ( ! empty( $taxes ) ) :

					foreach ( $taxes as $tax_id => $tax_item ) :
						$column_label = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', ATUM_TEXT_DOMAIN ); ?>
						<th class="tax">
							<?php echo esc_attr( $column_label ); ?>
						</th>
					<?php endforeach;

				endif; ?>
				<th class="total"><?php esc_html_e( 'Total', ATUM_TEXT_DOMAIN ) ?></th>
			</tr>
		</thead>
		<tbody class="po-lines">
			<?php foreach ( $po->get_items() as $item ) :

				/**
				 * Variable definition
				 *
				 * @var \WC_Order_Item_Product $item
				 */
				?>
				<tr class="po-line">
					<td class="description"><?php echo esc_html( $item->get_name() ) ?>

						<?php
						$product = Helpers::get_atum_product( $item->get_product() );

						if ( $product instanceof \WC_Product && AtumCapabilities::current_user_can( 'read_supplier' ) ) :

							$supplier_sku = (array) apply_filters( 'atum/atum_order/po_report/supplier_sku', [ $product->get_supplier_sku() ], $item );

							if ( ! empty( $supplier_sku ) ) : ?>
								<div class="atum-order-item-sku">
									<?php echo esc_html( _n( 'Supplier SKU:', 'Supplier SKUs:', count( $supplier_sku ), ATUM_TEXT_DOMAIN ) . ' ' . implode( ', ', $supplier_sku ) ) ?>
								</div>
							<?php endif;

							$sku = (array) apply_filters( 'atum/atum_order/po_report/sku', [ $product->get_sku() ], $item );

							if ( ! empty( $sku ) ) : ?>
								<div class="atum-order-item-sku">
									<?php echo esc_html( _n( 'SKU:', 'SKUs:', count( $sku ), ATUM_TEXT_DOMAIN ) . ' ' . implode( ', ', $sku ) ) ?>
								</div>
							<?php endif;

							do_action( 'atum/atum_order/po_report/after_item_product', $item, $item->get_order() );

						endif;


						// Show the custom meta.
						$hidden_item_meta = apply_filters( 'atum/atum_order/po_report/hidden_item_meta', array(
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

						$meta_data = $item->get_formatted_meta_data( '' ); ?>

						<?php foreach ( $meta_data as $meta_id => $meta ) :

							if ( in_array( $meta->key, $hidden_item_meta, TRUE ) ) :
								continue;
							endif;

							$meta_label = $meta->display_key;

							if ( '_order_id' === $meta->display_key ) :
								$meta_label = esc_html__( 'Order ID', ATUM_TEXT_DOMAIN );
							endif;
							?>
							<br>
							<span class="atum-order-item-<?php echo esc_attr( $meta->display_key ) ?>" style="color: #888; font-size: 12px;">
								<?php echo esc_html( $meta_label ) ?>: <?php echo esc_html( wp_strip_all_tags( $meta->display_value ) ) ?>
							</span>

						<?php endforeach; ?>

					</td>
					<td class="qty"><?php echo esc_html( $item->get_quantity() ) ?></td>
					<td class="price"><?php echo wc_price( $po->get_item_subtotal( $item, FALSE, FALSE ), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<?php if ( $discount ) : ?>
						<td class="discount">
							<?php if ( $item->get_subtotal() != $item->get_total() ) : // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison ?>
								-<?php echo wc_price( wc_format_decimal( $item->get_subtotal() - $item->get_total(), '' ), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						</td>
					<?php endif; ?>

					<?php
					if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :

						foreach ( $po->get_taxes() as $tax_item ) :
							$tax_item_id    = $tax_item->get_rate_id();
							$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : ''; ?>
							<td class="tax">
								<?php if ( '' !== $tax_item_total ) :
									echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								else :
									echo '&ndash;';
								endif; ?>
							</td>
						<?php endforeach;

					endif; ?>
					<td class="total"><?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
			<?php endforeach; ?>

			<?php if ( ! empty( $line_items_shipping ) ) : ?>

				<?php foreach ( $line_items_shipping as $item_id => $item ) : ?>
					<tr class="po-line content-shipping">
						<td class="description"><?php echo esc_html( $item->get_name() ?: __( 'Shipping', ATUM_TEXT_DOMAIN ) ); ?></td>
						<td class="qty">&nbsp;</td>
						<td class="price">&nbsp;</td>

						<?php if ( $discount ) : ?>
							<td class="discount">&nbsp;</td>
						<?php endif; ?>

						<?php if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :

							foreach ( $po->get_taxes() as $tax_item ) :
								$tax_item_id    = $tax_item->get_rate_id();
								$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : ''; ?>
								<td class="tax">
									<?php if ( '' !== $tax_item_total ) :
										echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									else :
										echo '&ndash;';
									endif; ?>
								</td>
							<?php endforeach;

						endif; ?>
						<td class="total"><?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				<?php endforeach; ?>

			<?php endif; ?>

			<?php if ( ! empty( $line_items_fee ) ) : ?>

				<?php foreach ( $line_items_fee as $item_id => $item ) : ?>
					<tr class="po-line content-fees">
						<td class="description kk"><?php echo esc_html( $item->get_name() ?: __( 'Fee', ATUM_TEXT_DOMAIN ) ); ?></td>
						<td class="qty">&nbsp;</td>
						<td class="price">&nbsp;</td>

						<?php if ( $discount ) : ?>
							<td class="discount">&nbsp;</td>
						<?php endif; ?>

						<?php if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :

							foreach ( $po->get_taxes() as $tax_item ) :
								$tax_item_id    = $tax_item->get_rate_id();
								$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : ''; ?>
								<td class="tax">
									<?php if ( '' !== $tax_item_total ) :
										echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									else :
										echo '&ndash;';
									endif; ?>
								</td>
							<?php endforeach;

						endif; ?>
						<td class="total"><?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				<?php endforeach; ?>

			<?php endif; ?>
		</tbody>
		<tbody class="content-totals">

		<tr class="subtotal">
			<td class="label" colspan="<?php echo esc_attr( $total_text_colspan ) ?>">
				<?php esc_html_e( 'Subtotal', ATUM_TEXT_DOMAIN ) ?>:
			</td>
			<td class="total">
				<?php echo $po->get_formatted_total( '', TRUE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>

		<?php if ( $discount ) : ?>
			<tr>
				<td class="label" colspan="<?php echo esc_attr( $total_text_colspan ) ?>">
					<?php esc_html_e( 'Discount', ATUM_TEXT_DOMAIN ) ?>:
				</td>
				<td class="total">
					-<?php echo wc_price( $po->discount_total, [ 'currency' => $currency ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( $line_items_shipping ) : ?>
			<tr>
				<td class="label" colspan="<?php echo esc_attr( $total_text_colspan ) ?>">
					<?php esc_html_e( 'Shipping', ATUM_TEXT_DOMAIN ) ?>:
				</td>
				<td class="total">
					<?php echo wc_price( $po->shipping_total, [ 'currency' => $currency ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( wc_tax_enabled() ) :

			$tax_totals = $po->get_tax_totals();

			if ( ! empty( $tax_totals ) ) :

				foreach ( $tax_totals as $code => $tax ) : ?>
					<tr>
						<td class="label" colspan="<?php echo esc_attr( $total_text_colspan ) ?>">
							<?php echo esc_html( $tax->label ) ?>:
						</td>
						<td class="total">
							<?php echo $tax->formatted_amount; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</td>
					</tr>
				<?php endforeach; ?>

			<?php endif;

		endif; ?>

		<tr class="po-total">
			<td colspan="<?php echo esc_attr( $total_text_colspan - 2 ) ?>"></td>
			<td class="label" colspan="2">
				<?php
				/* translators: the purchase order's post type name */
				printf( esc_html__( '%s Total', ATUM_TEXT_DOMAIN ), esc_html( $post_type->labels->singular_name ) ) ?>:
			</td>
			<td class="total">
				<?php echo $po->get_formatted_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>

		</tbody>
	</table>
</div>

<?php $description = $po->get_description() ?>
<?php if ( $description ) : ?>
<div class="po-wrapper content-description">
	<div class="label">
		<?php esc_html_e( 'Notes', ATUM_TEXT_DOMAIN ) ?>
	</div>
	<div class="po-content">
		<?php echo wp_kses_post( apply_filters( 'the_content', $description ) ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
	</div>
</div>
<?php endif;
