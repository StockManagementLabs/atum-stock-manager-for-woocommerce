<?php
/**
 * View for the ATUM Dashboard News widget
 *
 * @since        1.4.0
 *
 * @var array $rss_items
 * @var int   $max_items
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;
?>
<div class="news-widget" data-widget="news">

	<?php if ( $max_items > 0 ) : ?>

		<div class="scroll-box">
			<?php foreach ( $rss_items as $item ) : ?>
				<article>

					<a href="<?php echo esc_url( $item->get_permalink() ) ?>" class="post-thumb">
						<?php
						$attachment = $item->get_enclosure();

						if ( ! empty( $attachment ) && ! empty( $attachment->link ) ) : ?>
							<div class="thumb">
								<div style="background-image: url('<?php echo esc_url( $attachment->link ) ?>')"></div>
							</div>
						<?php endif ?>
					</a>

					<div class="post-details">

						<a href="<?php echo esc_url( $item->get_permalink() ) ?>" class="post-title" title="<?php echo esc_attr( $item->get_title() ) ?>" target="_blank"><?php echo esc_html( $item->get_title() ) ?></a>

						<div class="post-meta">
							<?php
							echo esc_html( $item->get_author()->name );
							echo ' · ' . esc_html( Helpers::get_relative_date( $item->get_date() ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
							?>
						</div>

						<div class="post-excerpt">
							<?php echo wp_kses_post( $item->get_content() ) ?>
						</div>

					</div>

				</article>
			<?php endforeach; ?>
		</div>

	<?php else : ?>
		<p class="error"><?php esc_html_e( 'The Stock Management Labs news could not be loaded. Please try again later.', ATUM_TEXT_DOMAIN ) ?></p>
	<?php endif ?>

</div>
