<?php
/**
 * View for the Product Details help tab on Stock Central page
 *
 * @since 0.0.5
 */

defined( 'ABSPATH' ) or die;

?>
<table class="widefat fixed striped">
	<thead>
		<tr>
			<td><strong><?php _e( 'COLUMN', ATUM_TEXT_DOMAIN) ?></strong></td>
			<td><strong><?php _e( 'DEFINITION', ATUM_TEXT_DOMAIN ) ?></strong></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><strong><?php _e( 'Checkbox', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php printf( __( "Select an item you would like to work with, amend or filter. We introduce new filters and functions often, however, if you think we should add one urgently, don't hesitate to let us know %shere%s.", ATUM_TEXT_DOMAIN ), '<a href="http://www.stockmanagementlabs.com/feature-request" target="_blank">', '</a>' ) ?></td>
		</tr>
		<tr>
			<td><span class="dashicons dashicons-format-image"></span></td>
			<td><?php _e( 'Product small image preview.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Product Name', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'The first twenty characters of the product name. Hover your mouse over the name to see the full content. (PRO users will be able to choose the amount of characters displayed. This option is editable in Settings).', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'SKU', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "An SKU, or Stock Keeping Unit, is a number assigned to a product by the store admin to identify the price, product options and manufacturer of the merchandise. An SKU is used to track inventory in your retail store. They are critical in helping you maintain a profitable retail business. It is not necessary to use SKUs in the basic/free version of ATUM. However, we recommend the introduction of SKUs in your store to take the full advantage of ATUM's features.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'ID', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "A WooCommerce Product ID is sometimes needed when using shortcodes, widgets and links. ATUM's stock central page will display the appropriate ID of the product in this column.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Product Type', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'This column displays the classification of individual products in WooCommerce. We specify product types by icons with a tooltip on hover.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>