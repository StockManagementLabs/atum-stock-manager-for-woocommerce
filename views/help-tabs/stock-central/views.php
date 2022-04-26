<?php
/**
 * View for the Views help tab on Stock Central page
 *
 * @since 1.9.16
 */

defined( 'ABSPATH' ) || die;

?>
<table class="widefat fixed striped">
	<thead>
		<tr>
			<td><strong><?php esc_html_e( 'VIEW', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><strong><?php esc_html_e( 'DEFINITION', ATUM_TEXT_DOMAIN ) ?></strong></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><strong><?php esc_html_e( 'ALL', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'You can see all your products here. To hide any of them from this table, please, deactivate the ATUM Control Switch (Product Edit >> Product Data >> ATUM Inventory).', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'IN STOCK', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Products that WooCommerce is showing as &quot;In Stock&quot;.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'OUT OF STOCK', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Products you have set to &quot;Backorder&quot;', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'RESTOCK STATUS', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Product in restock status. This is an ATUM feature (not related to WooCommerce &quot;Low Stock threshold&quot; feature). You can edit the restock settings from ATUM Settings >> Stock Central >> Days to re-order.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'UNMANAGED BY WC', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "Products with the WooCommerce's &quot;Manage Stock&quot; option disabled.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>
