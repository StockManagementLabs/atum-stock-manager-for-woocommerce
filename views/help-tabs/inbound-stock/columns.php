<?php
/**
 * View for the help tab at Inbound Stock page
 *
 * @since 1.3.0
 */

defined( 'ABSPATH' ) or die;

?>
<table class="widefat fixed striped">
	<thead>
		<tr>
			<td><strong><?php _e( 'COLUMN', ATUM_TEXT_DOMAIN) ?></strong></td>
			<td><strong><?php _e( 'DEFINITION', ATUM_TEXT_DOMAIN ) ?></strong></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><span class="dashicons dashicons-format-image" title="<?php _e('Thumbnail', ATUM_TEXT_DOMAIN) ?>"></span></td>
			<td><?php _e( 'Product small image preview.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Product Name', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'The first twenty characters of the product name. Hover your mouse over the name to see the full content.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'SKU', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "An SKU, or Stock Keeping Unit, is a code assigned to a product by the store admin to identify the price, product options and manufacturer of the merchandise. An SKU is used to track inventory in your retail store. They are critical in helping you maintain a profitable retail business. We recommend the introduction of SKUs in your store to take the full advantage of ATUM's features.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'ID', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "A WooCommerce Product ID is sometimes needed when using shortcodes, widgets and links. ATUM's stock central page will display the appropriate ID of the product in this column.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><span class="wc-type" title="<?php _e( 'Product Type', ATUM_TEXT_DOMAIN ) ?>"></span></td>
			<td><?php _e( 'This column displays the classification of individual products in WooCommerce. We specify product types by icons with a tooltip on hover.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Inbound Stock', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'Inbound stock counter represents the volume of products that have been ordered in, using the Purchase Order feature and are pending delivery.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Date Ordered', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'This is the date the product was added to Purchase Order and is pending delivery.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Date Expected', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'This is the date product is expected to arrive from suppliers.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'PO', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'This is the Purchase Order number this product belongs to.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>