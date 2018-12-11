<?php
/**
 * View for the help tab at Inbound Stock page
 *
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || die;

?>
<table class="widefat fixed striped">
	<thead>
		<tr>
			<td><strong><?php esc_html_e( 'COLUMN', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><strong><?php esc_html_e( 'DEFINITION', ATUM_TEXT_DOMAIN ) ?></strong></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><span class="atum-icon atmi-picture" title="<?php esc_attr_e( 'Thumbnail', ATUM_TEXT_DOMAIN ) ?>"></span></td>
			<td><?php esc_html_e( 'Product small image preview.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Product Name', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'The first twenty characters of the product name. Hover your mouse over the name to see the full content.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'SKU', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "An SKU, or Stock Keeping Unit, is a code assigned to a product by the store admin to identify the price, product options and manufacturer of the merchandise. An SKU is used to track inventory in your retail store. They are critical in helping you maintain a profitable retail business. We recommend the introduction of SKUs in your store to take the full advantage of ATUM's features.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><span class="atum-icon atmi-tag" title="<?php esc_attr_e( 'Product Type', ATUM_TEXT_DOMAIN ) ?>"></span></td>
			<td><?php esc_html_e( 'This column displays the classification of individual products in WooCommerce. We specify product types by icons with a tooltip on hover.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Inbound Stock', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Inbound stock counter represents the volume of products that have been ordered in, using the Purchase Order feature and are pending delivery.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Date Ordered', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'This is the date the product was added to Purchase Order and is pending delivery.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Date Expected', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'This is the date product is expected to arrive from suppliers.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'PO', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'This is the Purchase Order number this product belongs to.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>
