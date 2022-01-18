<?php
/**
 * View for the ATUM Order notes meta box
 *
 * @since 1.2.9
 *
 * @var int $post_id
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\AtumComments;

global $post;

$args = array(
	'post_id' => $post_id ?? $post->ID,
	'orderby' => 'comment_ID',
	'order'   => 'DESC',
	'approve' => 'approve',
	'type'    => AtumComments::NOTES_KEY,
);

// Bypass the AtumComments filter to get rid of ATUM Order notes comments from queries.
$atum_comments = AtumComments::get_instance();

remove_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );
$notes = get_comments( $args );
add_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );

?>
<div class="atum-meta-box">

	<ul class="atum_order_notes">

		<?php if ( $notes ) :

			foreach ( $notes as $note_comment ) :
				include 'note.php';
			endforeach; ?>

		<?php else : ?>

			<li><?php esc_html_e( 'There are no notes yet.', ATUM_TEXT_DOMAIN ) ?></li>

		<?php endif; ?>

	</ul>

	<div class="add_note">
		<p>
			<label for="add_atum_order_note"><?php esc_html_e( 'Add Note', ATUM_TEXT_DOMAIN ); ?> <span class="atum-help-tip atum-tooltip" data-tip="<?php esc_attr_e( 'Add a note for your reference', ATUM_TEXT_DOMAIN ) ?>"></span></label>
			<textarea type="text" name="atum_order_note" id="add_atum_order_note" class="input-text" cols="20" rows="5"></textarea>
		</p>

		<p>
			<button type="button" class="add_note button"><?php esc_html_e( 'Add', ATUM_TEXT_DOMAIN ); ?></button>
		</p>
	</div>

</div>
