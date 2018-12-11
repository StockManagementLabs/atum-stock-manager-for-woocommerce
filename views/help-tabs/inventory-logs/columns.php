<?php
/**
 * View for the help tab at Inventory Logs page
 *
 * @since 1.3.0
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
			<td><strong><?php esc_html_e( 'Log', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'The number of Inventory Log given by WP post counting engine. This number cannot be edited.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Log Status', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "This is the status of existing Inventory Logs. When 'pending' status is activated, all products within these ILs will reflect their totals in Stock Central. When 'completed' status is activated products were rectified and stocks amended by the user. When this status is active, all products within are no longer reflected in Stock Central’s columns.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Type', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'The type of Inventory Log set by the user.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Date', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'This is the date the Inventory Log was last edited/modified.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Order', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'If an order is attached to the Inventory Log, the number is shown here.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Total', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'The total monetary value (sale price) of all products within the Inventory Log.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Actions', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Inventory Log actions (Mark as completed).', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>
