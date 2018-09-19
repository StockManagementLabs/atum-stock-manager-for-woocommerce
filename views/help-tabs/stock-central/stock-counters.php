<?php
/**
 * View for the Stock Counters help tab on Stock Central page
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
			<td><strong><?php esc_html_e( 'Current Stock', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'The current stock represents the number of products available to order (values update on the Stock Central refresh).', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Inbound Stock', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Inbound stock counter represents the volume of products that have been ordered in, using the Purchase Order feature and are pending delivery.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Stock on Hold', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "The 'Stock on Hold' value is an important indicator for stores that allow customers to add items to their baskets and leave them there unattended for a while. Products left in baskets are still physically in the warehouse, but not included in the 'Current Stock' indicator. You can set the time of the product being held by a customer under 'WooCommerce' - 'Settings' - 'Products' and the 'Inventory' tab.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Reserved Stock', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "There are times when the store owner needs to reserve or set aside a small or significant amount of stock. Occasions like special events, customer reservations or quality checks will find this feature very handy. Inventory Logs add-on can automatically deduct the reserved stock value from the 'Current Stock' indicator.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Back Orders', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "Every WooCommerce product has an 'Allow Back Orders' option within the product page. It will give customers the opportunity to place an order even when the product is out of stock. The 'Back Orders' indicator will display the amount of items required by customers.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Sold today', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "The value represents the number of products sold during the day. Sold products are items included in 'completed' and 'processing' orders only. Items already sold, but pending payment will show in the 'Stock on Hold' column instead.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>
