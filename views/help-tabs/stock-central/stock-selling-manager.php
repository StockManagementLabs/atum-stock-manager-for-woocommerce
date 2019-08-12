<?php
/**
 * View for the Selling Manager help tab on Stock Central page
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
			<td><strong><?php esc_html_e( 'Sales Last X Days', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Users will value this performance indicator as a tool that allows them to see the actual sales of the product within the last X days (sold products are items included in \'completed\' and \'processing\' orders only. Ordered items, but pending payment will show in the \'Stock on Hold\' column instead). You can change the number of the days by simply clicking on its blue number.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Stock will Last (Days)', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "A feature that will tell the user the average amount of days that a product will keep 'in stock' status. By default, we base this indicator on sales for the past seven days (We do not include the current day sales).", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Out of Stock for (Days)', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "This indicator shows the number of days an item has the 'Out of Stock' status. Store owners can more accurately monitor the effectiveness of their ordering. Please, note, ATUM will only calculate products that go out of stock after the initial plugin install.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Lost Sales', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Our users will want to see this figure as close to zero as possible. The formula calculates the volume of products that store owner would sell if the item stayed in stock. By default, we take the average sales for the last seven days the item was in stock. We time the result by the number of days the item is out of stock.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Stock Indicator', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td>
				<p><i class="atum-icon atmi-checkmark-circle" style="color: #82C342"></i> <?php esc_html_e( 'Product In Stock and Managed by WC.', ATUM_TEXT_DOMAIN ) ?></p>
				<p><i class="atum-icon atmi-cross-circle" style="color: #FF4848"></i> <?php esc_html_e( 'Product Out of Stock and Managed by WC.', ATUM_TEXT_DOMAIN ) ?></p>
				<p><i class="atum-icon atmi-arrow-down-circle" style="color: #00B8DB"></i> <?php esc_html_e( 'Product is Low Stock and Managed by WC.', ATUM_TEXT_DOMAIN ) ?></p>
				<p><i class="atum-icon atmi-circle-minus" style="color: #EFAF00"></i> <?php esc_html_e( 'Product is Out of Stock, but Managed by WC and Set to Back Orders.', ATUM_TEXT_DOMAIN ) ?></p>
				<p><i class="atum-icon atmi-question-circle" style="color: #82C342"></i> <?php esc_html_e( 'Product In Stock and Not Managed by WC.', ATUM_TEXT_DOMAIN ) ?></p>
				<p><i class="atum-icon atmi-question-circle" style="color: #FF4848"></i> <?php esc_html_e( 'Product Out of Stock and Not Managed by WC.', ATUM_TEXT_DOMAIN ) ?></p>
				<p><i class="atum-icon atmi-question-circle" style="color: #EFAF00"></i> <?php esc_html_e( 'Product set to Back Orders Only and Not Managed by WC.', ATUM_TEXT_DOMAIN ) ?></p>
			</td>
		</tr>
	</tbody>
</table>
