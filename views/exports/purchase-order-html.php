<?php
/**
 * View for the Purchase Order reports
 *
 * @since 1.4.0
 */
?>
<div class="po-wrapper content-header">
	<div class="float-left">
		<strong><?php echo preg_replace( '/<br/', '</strong><br', $po->get_company_address(), 1 ) ?>
	</div>
	<div class="float-right">
		<h3 class="po-title"><?php _e( 'Purchase Order', ATUM_TEXT_DOMAIN ) ?></h3>
		<div class="content-header-po-data">
			<div class="row">
				<span class="label"><?php _e('Date:', ATUM_TEXT_DOMAIN) ?>&nbsp;&nbsp;</span>
				<span class="field"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $po->get_date() ) ) ?></span>
			</div>
			<div class="row">
				<span class="label"><?php _e('P.O. #:', ATUM) ?>&nbsp;&nbsp;</span>
				<span class="field"><?php echo $po->get_id() ?></span>
			</div>
		</div>
	</div>
	<div class="spacer" style="clear: both;"></div>
</div>
<div class="po-wrapper content-address">
	<div class="float-left">
		<h4><?php _e( 'Vendor', ATUM_TEXT_DOMAIN ) ?></h4>
		<p class="address">
			<?php echo $po->get_supplier_address() ?>
		</p>
	</div>
	<div class="float-right">
		<h4><?php _e( 'Ship To', ATUM_TEXT_DOMAIN ) ?></h4>
		<p class="address">
			<?php echo $po->get_shipping_address() ?>
		</p>
	</div>
	<div class="spacer" style="clear: both;"></div>
</div>
<div class="po-wrapper content-lines">
	<table class="">
		<thead>
			<tr class="po-li-head">
				<th class="description" style="width:<?php echo $desc_percent ?>%"><?php _e( 'Item', ATUM_TEXT_DOMAIN ) ?></th>
				<th class="qty"><?php _e( 'Qty', ATUM_TEXT_DOMAIN ) ?></th>
				<th class="price"><?php _e( 'Unit Price', ATUM_TEXT_DOMAIN ) ?></th>

				<?php if ( $discount ): ?>
					<th class="discount"><?php _e( 'Discount', ATUM_TEXT_DOMAIN ) ?></th>
				<?php endif; ?>

				<?php if ( ! empty( $taxes ) ) :

					foreach ( $taxes as $tax_id => $tax_item ) :
						$column_label = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', ATUM_TEXT_DOMAIN ); ?>
						<th class="tax">
							<?php echo esc_attr( $column_label ); ?>
						</th>
					<?php endforeach;

				endif; ?>
				<th class="total"><?php _e( 'Total', ATUM_TEXT_DOMAIN ) ?></th>
			</tr>
		</thead>
		<tbody class="po-lines">
			<?php foreach ( $po->get_items() as $item ):?>
				<tr class="po-line">
					<td class="description"><?php echo $item->get_name() ?>
						<?php
						$product = $item->get_product();
						if ( $product && \Atum\Components\AtumCapabilities::current_user_can( 'read_supplier' ) ):
							$supplier_sku = get_post_meta( $product->get_id(), '_supplier_sku', TRUE );
							
							if ( $supplier_sku ): ?>
								<br>
								<span class="atum-order-item-sku" style="font-style: italic; color: #888; font-size: 12px ">
									<?php _e( 'Supplier SKU:', ATUM_TEXT_DOMAIN ) ?> <?php echo esc_html( $supplier_sku ) ?>
								</span>
							<?php endif;
						endif;
						?>
						
					</td>
					<td class="qty"><?php echo $item->get_quantity() ?></td>
					<td class="price"><?php echo wc_price( $po->get_item_subtotal( $item, FALSE, FALSE ), array( 'currency' => $currency ) ); ?></td>
					<?php if ( $discount ): ?>
						<td class="discount">
							<?php if ( $item->get_subtotal() != $item->get_total() ): ?>
								-<?php echo wc_price( wc_format_decimal( $item->get_subtotal() - $item->get_total(), '' ), array( 'currency' => $currency ) ) ?>
							<?php endif; ?>
						</td>
					<?php endif; ?>

					<?php
					if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :

						foreach ( $po->get_taxes() as $tax_item ) :
							$tax_item_id = $tax_item->get_rate_id();
							$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : ''; ?>
							<td class="tax">
								<?php if ( '' != $tax_item_total ):
									echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) );
								else:
									echo '&ndash;';
								endif; ?>
							</td>
						<?php endforeach;

					endif; ?>
					<td class="total"><?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ) ?></td>
				</tr>
			<?php endforeach; ?>

			<?php if ( $line_items_shipping ): ?>
				<?php foreach ( $line_items_shipping as $item_id => $item ): ?>
					<tr class="po-line content-shipping">
						<td class="description"><?php echo esc_html( $item->get_name() ?: __( 'Shipping', ATUM_TEXT_DOMAIN ) ); ?></td>
						<td class="qty">&nbsp;</td>
						<td class="price">&nbsp;</td>

						<?php if ( $discount ): ?>
							<td class="discount">&nbsp;</td>
						<?php endif; ?>

						<?php if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) :

							foreach ( $po->get_taxes() as $tax_item ) :
								$tax_item_id = $tax_item->get_rate_id();
								$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : ''; ?>
								<td class="tax">
									<?php if ( '' != $tax_item_total ):
										echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) );
									else:
										echo '&ndash;';
									endif; ?>
								</td>
							<?php endforeach;

						endif; ?>
						<td class="total"><?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ) ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php if ( $line_items_fee ): ?>
				<?php foreach ( $line_items_fee as $item_id => $item ): ?>
					<tr class="po-line content-fees">
						<td class="description kk"><?php echo esc_html( $item->get_name() ?: __( 'Fee', ATUM_TEXT_DOMAIN ) ); ?></td>
						<td class="qty">&nbsp;</td>
						<td class="price">&nbsp;</td>

						<?php if ( $discount ): ?>
							<td class="discount">&nbsp;</td>
						<?php endif; ?>

						<?php if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ):

							foreach ( $po->get_taxes() as $tax_item ) :
								$tax_item_id = $tax_item->get_rate_id();
								$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : ''; ?>
								<td class="tax">
									<?php if ( '' != $tax_item_total ):
										echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $currency ) );
									else:
										echo '&ndash;';
									endif; ?>
								</td>
							<?php endforeach;

						endif; ?>
						<td class="total"><?php echo wc_price( $item->get_total(), array( 'currency' => $currency ) ) ?></td>
					</tr>
				<?php endforeach; ?>

			<?php endif; ?>
		</tbody>
		<tbody class="content-totals">

		<tr class="subtotal">
			<td class="label" colspan="<?php echo $total_text_colspan ?>">
				<?php _e( 'Subtotal', ATUM_TEXT_DOMAIN ) ?>:
			</td>
			<td class="total">
				<?php echo $po->get_formatted_total( '', TRUE ) ?>
			</td>
		</tr>

		<?php if ( $discount ): ?>
			<tr>
				<td class="label" colspan="<?php echo $total_text_colspan ?>">
					<?php _e( 'Discount', ATUM_TEXT_DOMAIN ) ?>:
				</td>
				<td class="total">
					-<?php echo wc_price( $po->get_total_discount(), array( 'currency' => $currency ) ) ?>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( $line_items_shipping ): ?>
			<tr>
				<td class="label" colspan="<?php echo $total_text_colspan ?>">
					<?php _e( 'Shipping', ATUM_TEXT_DOMAIN ) ?>:
				</td>
				<td class="total">
					<?php echo wc_price( $po->get_shipping_total(), array( 'currency' => $currency ) ) ?>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( wc_tax_enabled() ) :

			$tax_totals = $po->get_tax_totals();

			if ( ! empty( $tax_totals ) ):

				foreach ( $tax_totals as $code => $tax ) : ?>
					<tr>
						<td class="label" colspan="<?php echo $total_text_colspan ?>">
							<?php echo $tax->label; ?>:
						</td>
						<td class="total">
							<?php echo $tax->formatted_amount; ?>
						</td>
					</tr>
				<?php endforeach; ?>

			<?php endif;

		endif; ?>

		<tr class="po-total">
			<td colspan="<?php echo $total_text_colspan - 2 ?>"></td>
			<td class="label" colspan="2">
				<?php printf( __( '%s Total', ATUM_TEXT_DOMAIN ), $post_type->labels->singular_name ); ?>:
			</td>
			<td class="total">
				<?php echo $po->get_formatted_total(); ?>
			</td>
		</tr>

		</tbody>
	</table>
</div>

<div class="po-wrapper content-description">
	<div class="label">
		<?php _e( 'Description', ATUM_TEXT_DOMAIN ) ?>
	</div>
	<div class="po-content">
		<?php echo apply_filters( 'the_content', $po->get_description() ) ?>
	</div>
</div>
