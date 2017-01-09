<?php
/**
 * View for the Selling Manager help tab on Stock Central page
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
			<td><strong><?php _e( 'Sales Last 14 Days', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "Our Premium and PRO users will value this performance indicator as a tool that allows them to see the actual sales of the product within the last 14 days (current day not included).", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Sales Last 7 Days', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "Our Premium and PRO users will value this performance indicator as a tool that allows them to see the actual sales of the product within the last 7 days (current day not included).", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Stock will Last (Days)', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "Very valuable Premium and PRO feature that will tell the user the average amount of days that a product will keep 'in stock' status. By default, we base this indicator on sales for the past seven days (current day not included). However, the Premium and PRO users have the option to set the number of days under the 'Stock Control' within the 'Settings' menu.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Out of Stock for (Days)', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "This indicator shows the number of days the item has the 'Out of Stock' status. Store owners can more accurately monitor the effectiveness of their ordering. PRO users will have the option to let the ATUM plugin take care of all order management.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Lost Sales', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "Our users will want to see this figure as close to zero as possible. The formula calculates the volume of sales that store owner would make if the item stayed in stock. By default, we take the average sales for the last seven days the item was in stock and time them by the number of days the item is out of stock. Premium and PRO users have the option to set the number of days for the 'average sales' calculation under the 'Settings' menu.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Stock Indicator', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "Very similar indicator to the default feature of WooCommerce. The user can see green, yellow or red icon according to the stock level of an item. Product with sufficient amount of stock will show a green icon. The yellow icon will mark any items running low in stock, and the red icon will indicate all the items that have the 'out of stock' status.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>