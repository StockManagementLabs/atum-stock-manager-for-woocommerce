<?php
/**
 * View for the ATUM Order notes meta box
 *
 * @since 1.2.9
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\AtumComments;

global $post;

$args = array(
	'post_id' => $post->ID,
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

			foreach ( $notes as $note ) :

				$note_classes   = array( 'note' );
				$note_classes[] = ( 'ATUM' === $note->comment_author ) ? 'system-note' : '';
				$note_classes   = apply_filters( 'atum/atum_order/note_class', array_filter( $note_classes ), $note );
				?>

				<li rel="<?php echo absint( $note->comment_ID ); ?>" class="<?php echo esc_attr( implode( ' ', $note_classes ) ); ?>">
					<div class="note_content">
						<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ) ?>
					</div>

					<p class="meta">
						<abbr class="exact-date" title="<?php echo esc_attr( $note->comment_date ) ?>">
							<?php
							/* translators: first one is the date added and second is the time */
							printf( esc_html__( 'added on %1$s at %2$s', ATUM_TEXT_DOMAIN ), esc_html( date_i18n( wc_date_format(), strtotime( $note->comment_date ) ) ), esc_html( date_i18n( wc_time_format() ), esc_html( strtotime( $note->comment_date ) ) ) );
							?>
						</abbr>

						<?php if ( 'ATUM' !== $note->comment_author ) :
							/* translators: the note author */
							printf( ' ' . esc_html__( 'by %s', ATUM_TEXT_DOMAIN ), esc_html( $note->comment_author ) );
						endif; ?>

						<a href="#" class="delete_note" role="button"><?php esc_html_e( 'Delete note', ATUM_TEXT_DOMAIN ); ?></a>
					</p>
				</li>

			<?php endforeach;

		else : ?>

			<li><?php esc_html_e( 'There are no notes yet.', ATUM_TEXT_DOMAIN ) ?></li>

		<?php endif; ?>

	</ul>

	<div class="add_note">
		<p>
			<label for="add_atum_order_note"><?php esc_html_e( 'Add Note', ATUM_TEXT_DOMAIN ); ?> <span class="atum-help-tip" data-toggle="tooltip" title="<?php esc_attr_e( 'Add a note for your reference', ATUM_TEXT_DOMAIN ) ?>"></span></label>
			<textarea type="text" name="atum_order_note" id="add_atum_order_note" class="input-text" cols="20" rows="5"></textarea>
		</p>

		<p>
			<button type="button" class="add_note button"><?php esc_html_e( 'Add', ATUM_TEXT_DOMAIN ); ?></button>
		</p>
	</div>

</div>
