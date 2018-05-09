<?php
/**
 * View for the ATUM Dashboard Videos widget
 *
 * @since 1.4.0
 */
?>

<div class="videos-widget" data-widget="videos">

	<?php if( ! empty($videos) ): ?>
		<div class="video-list" data-view="list">
			<div class="video-filter">

				<div class="filter-controls">
					<select class="video-filter-by left">
						<option value="" data-display="<?php _e('Filter by', ATUM_TEXT_DOMAIN) ?>"><?php _e('Show All', ATUM_TEXT_DOMAIN) ?></option>
						<?php foreach ($video_tags as $video_tag => $tag_label): ?>
							<option value="<?php echo $video_tag ?>"><?php echo $tag_label ?></option>
						<?php endforeach; ?>
					</select>

					<select class="video-sort-by left">
						<option value="date" data-display="<?php _e('Sort by date added', ATUM_TEXT_DOMAIN) ?>"><?php _e('Date added (newest)', ATUM_TEXT_DOMAIN) ?></option>
						<option value="rating" data-display="<?php _e('Sort by rating', ATUM_TEXT_DOMAIN) ?>"><?php _e('Rating', ATUM_TEXT_DOMAIN) ?></option>
						<option value="relevance" data-display="<?php _e('Sort by relevance', ATUM_TEXT_DOMAIN) ?>"><?php _e('Relevance', ATUM_TEXT_DOMAIN) ?></option>
						<option value="title" data-display="<?php _e('Sort by title', ATUM_TEXT_DOMAIN) ?>"><?php _e('Title', ATUM_TEXT_DOMAIN) ?></option>
						<option value="viewCount" data-display="<?php _e('Sort by view count', ATUM_TEXT_DOMAIN) ?>"><?php _e('View Count', ATUM_TEXT_DOMAIN) ?></option>
					</select>
				</div>

				<div class="video-list-layout">

					<a class="active" href="#" title="<?php _e('List View', ATUM_TEXT_DOMAIN) ?>" data-view="list">
						<img src="<?php echo ATUM_URL ?>assets/images/dashboard/icon-view-list.svg">
					</a>

					<a href="#" title="<?php _e('Grid View', ATUM_TEXT_DOMAIN) ?>" data-view="grid">
						<img src="<?php echo ATUM_URL ?>assets/images/dashboard/icon-view-grid.svg">
					</a>

				</div>

			</div>

			<div class="video-list-wrapper">
				<div class="carousel-nav-prev disabled"><i class="lnr lnr-chevron-left"></i></div>

				<div class="scroll-box">
					<?php foreach ($videos as $index => $video):

						$video_snippet = $video->snippet;
						$tags = array_map('sanitize_title', $video_snippet->tags); ?>

						<article class="<?php echo implode(' ', $tags) ?><?php if ( (! defined('DOING_AJAX') || !DOING_AJAX) && $index == 0 ) echo ' active' ?>" data-video="<?php echo $video->id ?>">

							<a href="#" class="video-thumb">

								<img src="<?php echo $video_snippet->thumbnails->medium->url ?>" alt="">

								<time>
									<?php
									$start = new \DateTime('@0'); // Unix epoch
									$start->add( new \DateInterval($video->contentDetails->duration) );
									echo $start->format('i:s');
									?>
								</time>

							</a>

							<div class="video-details">

								<a href="#" class="video-title" title="<?php echo $video_snippet->title ?>"><?php echo $video_snippet->title ?></a>

								<div class="video-meta">
									<?php
									printf( __('%d Views', ATUM_TEXT_DOMAIN), $video->statistics->viewCount );

									$timeAgo = new \Westsworld\TimeAgo();
									echo ' · ' . $timeAgo->inWords( $video_snippet->publishedAt );
									?>
								</div>

								<div class="video-desc">
									<?php echo $video_snippet->description ?>
								</div>

							</div>

						</article>

					<?php endforeach; ?>
				</div>

				<div class="carousel-nav-next"><i class="lnr lnr-chevron-right"></i></div>
			</div>
		</div>

		<div class="video-preview">

			<div class="channel-info">

				<span>
					<a href="https://www.youtube.com/channel/UCcTNwTCU4X_UrIj_5TUkweA" target="_blank">
						<img src="<?php echo $channel->snippet->thumbnails->default->url ?>" alt="">
						<h3>
							<?php echo $channel->snippet->title ?>
							<span class="subscriptions"><?php printf( '%d Subscriptions', $channel->statistics->subscriberCount ) ?></span>
						</h3>
					</a>
				</span>

				<a href="https://www.youtube.com/channel/UCcTNwTCU4X_UrIj_5TUkweA" class="btn btn-primary btn-pill channel-subscribe" target="_blank"><?php _e('Subscribe Now', ATUM_TEXT_DOMAIN) ?></a>
			</div>

			<div class="video-player">

				<div class="embed-responsive embed-responsive-16by9">
					<iframe class="embed-responsive-item" src="//www.youtube.com/embed/<?php echo $first_video->id ?>?rel=0&modestbranding=1" allowfullscreen></iframe>
				</div>

				<h3 class="video-title"><?php echo $first_video->snippet->title ?></h3>

				<div class="video-meta">
					<?php
					printf( __('%d Views', ATUM_TEXT_DOMAIN), $first_video->statistics->viewCount );

					$timeAgo = new \Westsworld\TimeAgo();
					echo ' · ' . $timeAgo->inWords( $first_video->snippet->publishedAt );
					?>
				</div>

				<div class="video-desc">
					<?php echo $first_video->snippet->description ?>
				</div>
			</div>

		</div>
	<?php else: ?>
		<p class="error"><?php _e("The ATUM's Youtube videos could not be loaded. Please try again later.", ATUM_TEXT_DOMAIN) ?></p>
	<?php endif; ?>

</div>
