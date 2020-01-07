<?php
/**
 * View for the ATUM Order actions meta box
 *
 * @since   1.2.4
 * @package AtumOrders
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;

?>

<div class="atum-meta-box">
	<ul class="atum_order_actions submitbox">

		<?php do_action( 'atum/atum_order/actions_meta_box_start', $post->ID ); ?>

		<li class="wide">
			<div id="delete-action">

				<?php if ( current_user_can( 'delete_post', $post->ID ) ) :
					$delete_text = ! EMPTY_TRASH_DAYS ? __( 'Delete permanently', ATUM_TEXT_DOMAIN ) : __( 'Move to trash', ATUM_TEXT_DOMAIN ); ?>
					<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_html( $delete_text ) ?></a>
				<?php endif; ?>
			</div>

			<input type="submit" class="button save-atum-order button-primary" name="save" value="<?php echo 'auto-draft' === $post->post_status ? esc_attr__( 'Create', ATUM_TEXT_DOMAIN ) : esc_attr__( 'Update', ATUM_TEXT_DOMAIN ); ?>" />
		</li>

		<?php if ( PurchaseOrders::POST_TYPE === $post->post_type && AtumCapabilities::current_user_can( 'export_data' ) && ModuleManager::is_module_active( 'data_export' ) ) : ?>
		<li class="wide">
			<a class="pdf-link button button-secondary" href="<?php echo PurchaseOrders::get_pdf_generation_link( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" target="_blank">
				<i class="atum-icon atmi-pdf"></i>
				<?php esc_html_e( 'Generate PDF', ATUM_TEXT_DOMAIN ) ?>
			</a>
		</li>
		<?php endif; ?>

		<?php do_action( 'atum/atum_order/actions_meta_box_end', $post->ID ); ?>

	</ul>
</div>
