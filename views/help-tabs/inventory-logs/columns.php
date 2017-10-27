<?php
/**
 * View for the help tab at Inventory Logs page
 *
 * @since 1.3.0
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
			<td><strong><?php _e( 'Log Status', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( "This is the status of existing Inventory Logs. Orange clock means IL is in 'pending' status. When this status is active all products within these ILs will reflect their totals in Stock Central. The green tick means IL is 'completed,' products were rectified and stocks amended by the user. When this status is active, all products within are no longer reflected in Stock Central’s columns.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Log', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'The number of Inventory Log given by WP post counting engine. This number cannot be edited.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Type', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'The type of Inventory Log set by the user.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Order', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'If an order is attached to the Inventory Log, the number is shown here.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Log Notes', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'Notes entered by the user within the Inventory Log interface.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Date', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'This is the date the Inventory Log was last edited/modified.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Total', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'The total monetary value (sale price) of all products within the Inventory Log.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php _e( 'Actions', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php _e( 'Regular WC actions (Complete Log and View/Edit Log).', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>