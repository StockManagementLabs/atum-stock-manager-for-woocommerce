<?php
/**
 * View for the Inventory Logs' refunds
 *
 * @since 1.2.4
 *
 * @var object $refund The refund object.
 */

defined( 'ABSPATH' ) or die;

$who_refunded = new \WP_User( $refund->get_refunded_by() );
?>
<tr class="refund <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-log_refund_id="<?php echo $refund->get_id(); ?>">
	<td class="thumb"><div></div></td>

	<td class="name">
		<?php
			/* translators: 1: refund id 2: date */
			printf( __( 'Refund #%1$s - %2$s', ATUM_TEXT_DOMAIN ), $refund->get_id(), wc_format_datetime( $refund->get_date_created(), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) ) );

			if ( $who_refunded->exists() ) {
				echo ' ' . esc_attr_x( 'by', 'Ex: Refund - $date >by< $username', ATUM_TEXT_DOMAIN ) . ' ' . '<abbr class="refund_by" title="' . sprintf( esc_attr__( 'ID: %d', ATUM_TEXT_DOMAIN ), absint( $who_refunded->ID ) ) . '">' . esc_attr( $who_refunded->display_name ) . '</abbr>' ;
			}
		?>

		<?php if ( $refund->get_reason() ) : ?>
			<p class="description"><?php echo esc_html( $refund->get_reason() ); ?></p>
		<?php endif; ?>

		<input type="hidden" class="log_refund_id" name="log_refund_id[]" value="<?php echo esc_attr( $refund->get_id() ); ?>" />
	</td>

	<?php do_action( 'atum/inventory_logs/log/refund_item_values', null, $refund, $refund->get_id() ); ?>

	<td class="item_cost" width="1%">&nbsp;</td>
	<td class="quantity" width="1%">&nbsp;</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php echo wc_price( '-' . $refund->get_amount() ); ?>
		</div>
	</td>

	<?php if ( wc_tax_enabled() ) : $total_taxes = count( $order_taxes ); ?>
		<?php for ( $i = 0;  $i < $total_taxes; $i++ ) : ?>
			<td class="line_tax" width="1%"></td>
		<?php endfor; ?>
	<?php endif; ?>

	<td class="atum-log-edit-line-item">
		<div class="atum-log-edit-line-item-actions">
			<a class="delete_refund" href="#"></a>
		</div>
	</td>
</tr>
