<?php
/**
 * View for the help tab at Purchase Orders page
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
			<td><strong><?php esc_html_e( 'PO', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'The number of Purchase Order given by WP post counting engine. This number cannot be edited.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Date', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'This is the date the Purchase Order was last edited/modified.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'PO Status', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( "This is the status of existing Purchase Orders. Orange clock means Purchase Order is in 'pending' status. When this status is active all products within these POs will show in Inbound Stock list and reflect their totals in Stock Central. The green tick means PO is 'completed,' products were delivered and added to stock by the user. When this status is active, all products within are no longer listed in Inbound Stock list and their totals not reflected in Stock Central’s Inbound Stock column.", ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Supplier', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'The name of supplier fulfilling relevant Purchase Order.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Date Expected', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'This is the date products within the PO are expected to arrive from suppliers.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Total', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'The total monetary order value (purchase price) of all products within the PO.', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Actions', ATUM_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Purchase Order actions (Complete PO and View | Edit PO | Export to PDF).', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>
