<?php
/**
 * View for the Stock Negatives help tab on Stock Central page
 *
 * @since 0.0.5
 */

defined( 'ABSPATH' ) or die;

?>
<table class="widefat fixed striped">
	<thead>
		<tr>
			<td><strong><?php _e( 'COLUMN', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><strong><?php _e( 'DEFINITION', ATUM_TEXT_DOMAIN ) ?></strong></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><strong><?php _e( 'Customer Returns', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( " We don't think there is a store owner that wants to see products returning to the warehouse. However, in case there is an item coming back from the customer our Premium, and PRO users have the option to create a log entry in the 'Customer Returns' tab within the 'Stock Log' menu. Depending on the item condition, the ATUM plugin will then add the item back in for further sale or create an 'Unsellable Return' entry.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Warehouse Damages', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "n the unlikely event of a warehouse or in-house damage, the Premium, or PRO user has the option to create an entry under the 'Warehouse Damages' tab within the 'Stock Log' menu. The ATUM plugin will, according to the log, remove the stock amount from the 'Current Stock' indicator.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Lost in Post', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "Premium and PRO user can create a log entry under the 'Lost in Post' tab within the 'Stock Log' menu for all items that have gone missing on the route to the customer. This indicator represents a valuable control of the performance of postal carriers.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>