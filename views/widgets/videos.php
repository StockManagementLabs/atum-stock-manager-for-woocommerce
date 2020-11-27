<?php
/**
 * View for the ATUM Dashboard Videos widget
 *
 * @since 1.4.0
 *
 * @var array  $video_tags
 * @var object $channel
 * @var object $first_video
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;
?>

<div class="videos-widget" data-widget="videos">

	<?php if ( ! empty( $videos ) ) : ?>
		<div class="video-list" data-view="list">
			<div class="video-filter">

				<div class="filter-controls">
					<select class="video-filter-by left">
						<option value="" data-display="<?php esc_attr_e( 'Filter by', ATUM_TEXT_DOMAIN ) ?>"><?php esc_html_e( 'Show All', ATUM_TEXT_DOMAIN ) ?></option>
						<?php foreach ( $video_tags as $video_tag => $tag_label ) : ?>
							<option value="<?php echo esc_attr( $video_tag ) ?>"><?php echo esc_html( $tag_label ) ?></option>
						<?php endforeach; ?>
					</select>

					<select class="video-sort-by left">
						<option value="date" data-display="<?php esc_attr_e( 'Sort by date added', ATUM_TEXT_DOMAIN ) ?>"><?php esc_html_e( 'Date added (newest)', ATUM_TEXT_DOMAIN ) ?></option>
						<option value="rating" data-display="<?php esc_attr_e( 'Sort by rating', ATUM_TEXT_DOMAIN ) ?>"><?php esc_html_e( 'Rating', ATUM_TEXT_DOMAIN ) ?></option>
						<option value="relevance" data-display="<?php esc_attr_e( 'Sort by relevance', ATUM_TEXT_DOMAIN ) ?>"><?php esc_html_e( 'Relevance', ATUM_TEXT_DOMAIN ) ?></option>
						<option value="title" data-display="<?php esc_attr_e( 'Sort by title', ATUM_TEXT_DOMAIN ) ?>"><?php esc_html_e( 'Title', ATUM_TEXT_DOMAIN ) ?></option>
						<option value="viewCount" data-display="<?php esc_attr_e( 'Sort by view count', ATUM_TEXT_DOMAIN ) ?>"><?php esc_html_e( 'View Count', ATUM_TEXT_DOMAIN ) ?></option>
					</select>
				</div>

				<div class="video-list-layout">

					<a class="active" href="#" title="<?php esc_attr_e( 'List View', ATUM_TEXT_DOMAIN ) ?>" data-view="list">
						<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/icon-view-list.svg" alt="">
					</a>

					<a href="#" title="<?php esc_attr_e( 'Grid View', ATUM_TEXT_DOMAIN ) ?>" data-view="grid">
						<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/dashboard/icon-view-grid.svg" alt="">
					</a>

				</div>

			</div>

			<div class="video-list-wrapper">
				<div class="carousel-nav-prev disabled"><i class="atum-icon atmi-chevron-left"></i></div>

				<div class="scroll-box">
					<?php foreach ( $videos as $index => $video ) :

						$video_snippet = $video->snippet;
						$tags          = array_map( 'sanitize_title', property_exists( $video_snippet, 'tags' ) ? $video_snippet->tags : [] );
						?>

						<article class="<?php echo esc_attr( implode( ' ', $tags ) ) ?><?php if ( ! wp_doing_ajax() && 0 === $index ) echo ' active' ?>" data-video="<?php echo esc_attr( $video->id ) ?>">

							<a href="#" class="video-thumb">

								<img src="<?php echo esc_url( $video_snippet->thumbnails->medium->url ) ?>" alt="">

								<time>
									<?php
									$start = new \DateTime( '@0' ); // Unix epoch.
									try {
										$start->add( new \DateInterval( $video->contentDetails->duration ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
									} catch ( \Exception $e ) {

										if ( ATUM_DEBUG ) {
											error_log( __METHOD__ . '::' . $e->getCode() . '::' . $e->getMessage() );
										}

									}
									echo esc_html( $start->format( 'i:s' ) );
									?>
								</time>

							</a>

							<div class="video-details">

								<a href="#" class="video-title" title="<?php echo esc_attr( $video_snippet->title ) ?>"><?php echo esc_html( $video_snippet->title ) ?></a>

								<div class="video-meta">
									<?php
									/* translators: the number of video views */
									printf( esc_html__( '%d Views', ATUM_TEXT_DOMAIN ), esc_attr( $video->statistics->viewCount ) );
									echo ' · ' . esc_html( Helpers::get_relative_date( $video_snippet->publishedAt ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
									?>
								</div>

								<div class="video-desc">
									<?php echo esc_html( $video_snippet->description ) ?>
								</div>

							</div>

						</article>

					<?php endforeach; ?>
				</div>

				<div class="carousel-nav-next"><i class="atum-icon atmi-chevron-right"></i></div>
			</div>
		</div>

		<div class="video-preview">

			<div class="channel-info">

				<span>
					<a href="https://www.youtube.com/channel/UCcTNwTCU4X_UrIj_5TUkweA" target="_blank">
						<img src="<?php echo esc_url( $channel->snippet->thumbnails->default->url ) ?>" alt="">
						<h3>
							<?php echo esc_html( $channel->snippet->title ) ?>
							<span class="subscriptions">
								<?php
								/* translators: the number of subscriptions */
								printf( esc_html__( '%d Subscriptions', ATUM_TEXT_DOMAIN ), esc_attr( $channel->statistics->subscriberCount ) ) ?>
							</span>
						</h3>
					</a>
				</span>

				<a href="https://www.youtube.com/channel/UCcTNwTCU4X_UrIj_5TUkweA" class="btn btn-primary channel-subscribe" target="_blank"><?php esc_attr_e( 'Subscribe Now', ATUM_TEXT_DOMAIN ) ?></a>
			</div>

			<div class="video-player">

				<div class="embed-responsive embed-responsive-16by9">
					<iframe class="embed-responsive-item" src="//www.youtube.com/embed/<?php echo esc_attr( $first_video->id ) ?>?rel=0&modestbranding=1" allowfullscreen></iframe>
				</div>

				<h3 class="video-title"><?php echo esc_html( $first_video->snippet->title ) ?></h3>

				<div class="video-meta">
					<?php
					/* translators: the number of video views */
					printf( esc_html__( '%d Views', ATUM_TEXT_DOMAIN ), esc_attr( $first_video->statistics->viewCount ) );
					echo ' · ' . esc_html( Helpers::get_relative_date( $first_video->snippet->publishedAt ) );
					?>
				</div>

				<div class="video-desc">
					<?php echo esc_attr( $first_video->snippet->description ) ?>
				</div>
			</div>

		</div>
	<?php else : ?>
		<p class="error"><?php esc_html_e( "The ATUM's Youtube videos could not be loaded. Please try again later.", ATUM_TEXT_DOMAIN ) ?></p>
	<?php endif; ?>

</div>
