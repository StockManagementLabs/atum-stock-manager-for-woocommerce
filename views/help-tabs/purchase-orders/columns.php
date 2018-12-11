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
			<td>
				<?php esc_html_e( 'This is the status of existing Purchase Orders. You can select from these PO status options:', ATUM_TEXT_DOMAIN ) ?>
				<ul>
					<li>
						<?php esc_html_e( 'Pending: When create the PO but is not sent. All the products within these POs will show in the Inbound Stock list and reflect their totals in Stock Central.', ATUM_TEXT_DOMAIN ) ?>
					</li>
					<li>
						<?php esc_html_e( 'Ordered: When the PO is sent to the supplier (The products will keep in the Inbound Stock list).', ATUM_TEXT_DOMAIN ) ?>
					</li>
					<li>
						<?php esc_html_e( 'On the way in: When products are on the way (The products will keep in the Inbound Stock list).', ATUM_TEXT_DOMAIN ) ?>
					</li>
					<li>
						<?php esc_html_e( 'Receiving: When products were delivered but have not been registered yet (The products will keep in the Inbound Stock list).', ATUM_TEXT_DOMAIN ) ?>
					</li>
					<li>
						<?php esc_html_e( 'Received: When products were delivered and registered in the warehouse. When this status is activated the products will be added to your stock automatically and are no longer listed in Inbound Stock list and their totals not reflected in Stock Central’s Inbound Stock column.', ATUM_TEXT_DOMAIN ) ?>
					</li>
				</ul>
			</td>
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
			<td><?php esc_html_e( 'Purchase Order actions (Mark as received | Export to PDF).', ATUM_TEXT_DOMAIN ) ?></td>
		</tr>
	</tbody>
</table>
