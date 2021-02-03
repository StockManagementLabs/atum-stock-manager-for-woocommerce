<?php
/**
 * View for the Product Details help tab on Stock Central page
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
			<td><span class="atum-icon atmi-picture" title="<?php esc_attr_e( 'Thumbnail', ATUM_TEXT_DOMAIN ) ?>"></span></td>
			<td><?php esc_html_e( 'Product small image preview.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Product Name', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'The first twenty characters of the product name. Hover your mouse over the name to see the full content.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Supplier', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'This is the name of the suppliers that supplies the products for your store.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'SKU', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "An SKU, or Stock Keeping Unit, is a code assigned to a product by the store admin to identify the price, product options and manufacturer of the merchandise. An SKU is used to track inventory in your retail store. They are critical in helping you maintain a profitable retail business. We recommend the introduction of SKUs in your store to take the full advantage of ATUM's features.<br>You can set the SKU in this column. After you click the 'Set' button and 'Save Data', the SKU will update automatically in your store.", ATUM_TEXT_DOMAIN ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Supplier SKU', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "The stock keeping unit code of the product within your supplier's product list.<br>You can set the Supplier SKU in this column. After you click the 'Set' button and 'Save Data', the Supplier SKU will update automatically in your store.", ATUM_TEXT_DOMAIN ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'ID', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "A WooCommerce Product ID is sometimes needed when using shortcodes, widgets and links. ATUM's stock central page will display the appropriate ID of the product in this column.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><span class="atum-icon atmi-tag" title="<?php esc_attr_e( 'Product Type', ATUM_TEXT_DOMAIN ) ?>"></span></td>
			<td><?php esc_html_e( 'This column displays the classification of individual products in WooCommerce. We specify product types by icons with a tooltip on hover.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Regular Price', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "You can configure the product price in this column. After you click the 'Set' button and 'Save Data', the product price will update automatically in your store.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Sale Price', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "You can configure the sale price of your product. Enter the date range for your sale or leave the date empty for a continuous sale price. After clicking the 'Set' button and 'Save Data', the change will automatically update in your store.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Purchase Price', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "You can configure the purchase price of the product. After you click the 'Set' button and 'Save Data', the product purchase price will update automatically in your store.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Gross Profit', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "The gross profit is the difference between the regular price and the purchase price. It's being represented as percentages and also as monetary values. When shown in red, it means that is below the profit margin specified in ATUM Settings.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Weight', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "You can configure the product weight in this column. After you click the 'Set' button and 'Save Data', the product weight will update automatically in your store.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><span class="atum-icon atmi-map-marker" title="<?php esc_attr_e( 'Location', ATUM_TEXT_DOMAIN ) ?>"></span></td>
			<td><?php esc_html_e( "Shows the product's Location hierarchy. Grey icon means that there are no locations set and blue icon means that there. Click the icon to view and manage the locations hierarchy in a popup.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>

		<?php do_action( 'atum/help_tabs/stock_central/after_product_details' ) ?>
	</tbody>
</table>
