<?php
/**
 * View for the Stock Negatives help tab on Stock Central page
 *
 * @since 0.0.5
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
			<td><strong><?php esc_html_e( 'Customer Returns', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "We don't think there is a store owner that wants to see products returning to the warehouse. However, in case there is an item coming back from the customer store owner can use the Inventory Logs add-on to create a log entry in the 'Customer Returns' tab within the 'Inventory Log' menu. Depending on the item condition, the ATUM plugin will then add the item back in for further sale or create an 'Unsellable Return' entry.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Warehouse Damages', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "In the unlikely event of a warehouse or in-house damages, the Inventory Logs add-on have the option to create an entry under the 'Warehouse Damages' tab within the 'Inventory Log' menu. The ATUM plugin will, per the log, remove the damaged stock from the product's stock value.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Lost in Post', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "Inventory Logs add-on can create a log entry under the 'Lost in Post' tab within the 'Inventory Log' menu for all items that have gone missing on the route to the customer. This indicator helps to control the performance of postal carriers as well as the work of dispatch departments.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>
