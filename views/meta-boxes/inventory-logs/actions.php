<?php
/**
 * View for the Inventory Logs actions meta box
 *
 * @since 1.2.4
 */

defined( 'ABSPATH' ) or die;

?>

<ul class="log_actions submitbox">

	<?php do_action( 'atum/inventory_logs/actions_start', $post->ID ); ?>

	<li class="wide">
		<div id="delete-action">

			<?php if ( current_user_can( 'delete_post', $post->ID ) ):

				$delete_text = ( ! EMPTY_TRASH_DAYS ) ? __( 'Delete permanently', ATUM_TEXT_DOMAIN ) : __( 'Move to trash', ATUM_TEXT_DOMAIN ); ?>
				<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo $delete_text; ?></a>
			<?php endif; ?>
		</div>

		<input type="submit" class="button save_log button-primary" name="save" value="<?php echo 'auto-draft' === $post->post_status ? esc_attr__( 'Create', ATUM_TEXT_DOMAIN ) : esc_attr__( 'Update', ATUM_TEXT_DOMAIN ); ?>" />
	</li>

	<?php do_action( 'atum/inventory_logs/actions_start', $post->ID ); ?>

</ul>
