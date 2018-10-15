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
			<td><span class="dashicons dashicons-format-image" title="<?php esc_attr_e( 'Thumbnail', ATUM_TEXT_DOMAIN ) ?>"></span></td>
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
			<td><?php esc_html_e( "An SKU, or Stock Keeping Unit, is a code assigned to a product by the store admin to identify the price, product options and manufacturer of the merchandise. An SKU is used to track inventory in your retail store. They are critical in helping you maintain a profitable retail business. We recommend the introduction of SKUs in your store to take the full advantage of ATUM's features.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Supplier SKU', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "The stock keeping unit code of the product within your supplier's product list.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'ID', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "A WooCommerce Product ID is sometimes needed when using shortcodes, widgets and links. ATUM's stock central page will display the appropriate ID of the product in this column.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><span class="wc-type" title="<?php esc_attr_e( 'Product Type', ATUM_TEXT_DOMAIN ) ?>"></span></td>
			<td><?php esc_html_e( 'This column displays the classification of individual products in WooCommerce. We specify product types by icons with a tooltip on hover.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Regular Price', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "You can configure the product price in this column. After you click the 'Set' button, the product price will update automatically in your store.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Sale Price', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "You can configure the sale price of your product. Enter the date range for your sale or leave the date empty for a continuous sale price. After clicking the 'Set' button, the change will automatically update in your store.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Purchase Price', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "You can configure the purchase price of the product. After you click the 'Set' button, the product price will update automatically in your store.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><span class="dashicons dashicons-store" title="<?php esc_attr_e( 'Location', ATUM_TEXT_DOMAIN ) ?>"></span></td>
			<td><?php esc_html_e( "Shows the product's Location hierarchy. Click ones to open the hierarchy in a popup.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>

		<?php do_action( 'atum/help_tabs/stock_central/after_product_details' ) ?>
	</tbody>
</table>
