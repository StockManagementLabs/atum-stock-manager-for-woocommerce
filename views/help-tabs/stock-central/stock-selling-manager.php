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
			<td><?php _e( 'Users will value this performance indicator as a tool that allows them to see the actual sales of the product within the last 14 days (We do not include the current day sales).', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Sales Last 7 Days', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'Users will value this performance indicator as a tool that allows them to see the actual sales of the product within the last seven days (We do not include the current day sales).', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Stock will Last (Days)', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "A feature that will tell the user the average amount of days that a product will keep 'in stock' status. By default, we base this indicator on sales for the past seven days (We do not include the current day sales).", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Out of Stock for (Days)', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "This indicator shows the number of days an item has the 'Out of Stock' status. Store owners can more accurately monitor the effectiveness of their ordering. Please, note, ATUM will only calculate products that go out of stock after the initial plugin install.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Lost Sales', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "Our users will want to see this figure as close to zero as possible. The formula calculates the volume of products that store owner would sell if the item stayed in stock. By default, we take the average sales for the last seven days the item was in stock. We time the result by the number of days the item is out of stock.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Stock Indicator', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "Very similar indicator to the default feature of WooCommerce. The user can see green, yellow or red icon according to the stock level of an item. Product with sufficient amount of stock will show a green icon. The yellow icon will mark any items running low in stock, and the red icon will indicate all the items that have the 'out of stock' status. We use the average sales for the past 7 days as default for calculation.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>