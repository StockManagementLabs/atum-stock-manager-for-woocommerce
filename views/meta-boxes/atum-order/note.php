<?php
/**
 * View for the ATUM Order note
 *
 * @since 1.6.9
 *
 * @var \WP_Comment $note_comment
 */

defined( 'ABSPATH' ) || die;

$note_classes   = array( 'note' );
$note_classes[] = 'ATUM' === $note_comment->comment_author ? 'system-note' : '';
$note_classes   = apply_filters( 'atum/atum_order/note_class', array_filter( $note_classes ), $note_comment );
?>
<li rel="<?php echo absint( $note_comment->comment_ID ); ?>" class="<?php echo esc_attr( implode( ' ', $note_classes ) ); ?>">
	<div class="note_content">
		<?php echo wp_kses_post( wpautop( wptexturize( $note_comment->comment_content ) ) ) ?>
	</div>

	<p class="meta">
		<abbr class="exact-date" title="<?php echo esc_attr( $note_comment->comment_date ) ?>">
			<?php
			/* translators: first one is the date added and second is the time */
			printf( esc_html__( '%1$s at %2$s', ATUM_TEXT_DOMAIN ), esc_html( date_i18n( wc_date_format(), strtotime( $note_comment->comment_date ) ) ), esc_html( date_i18n( wc_time_format(), strtotime( $note_comment->comment_date ) ) ) );
			?>
		</abbr>

		<?php if ( 'ATUM' !== $note_comment->comment_author ) :
			/* translators: the note author */
			printf( ' ' . esc_html__( 'by %s', ATUM_TEXT_DOMAIN ), esc_html( $note_comment->comment_author ) );
		endif; ?>

		<a href="#" class="delete_note" role="button"><?php esc_html_e( 'Delete note', ATUM_TEXT_DOMAIN ); ?></a>
	</p>
</li>
