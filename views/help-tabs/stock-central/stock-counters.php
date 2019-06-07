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
			<td><?php esc_html_e( "The current stock represents the number of products available to order (values update on the Stock Central refresh). If the WC’s manage stock option is enabled, you can set the current stock in this column. After you click the 'Set' button and 'Save Data', the current stock will update automatically in your store.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Out of stock threshold', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "When stock quantity reaches the Out of Stock Threshold the stock status will change to 'Out of Stock'. You can set the Out of Stock Threshold in this column. After you click the 'Set' button and 'Save Data', the Out of Stock Threshold will update automatically in your store.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Inbound Stock', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Inbound stock counter represents the volume of products that have been ordered in, using the Purchase Order feature and are pending delivery.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Stock on Hold', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'All products within paid orders that are still in the warehouse but not yet shipped (order status processing or on hold).', ATUM_TEXT_DOMAIN ) ?></td>
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
