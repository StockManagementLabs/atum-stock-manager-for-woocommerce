<?php
/**
 * Template for the Addons trial expiration modal
 *
 * @since 1.9.27
 *
 * @var array $expired_trials
 */

defined( 'ABSPATH' ) || die;

?>
<p>
	<?php
	echo esc_html( _n(
		'The following add-on with a expired trial license has been blocked. Please check its status below.',
		'The following add-ons with a expired trial license have been blocked. Please check their status below.',
		count( $expired_trials ),
		ATUM_TEXT_DOMAIN
	) ) ?>
</p>
<ul class="atum-trial-list">
	<?php foreach ( $expired_trials as $slug => $expired_trial ) : ?>
		<li>
			<span class="atum-trial-list__item">

				<span class="atum-trial-list__item-thumb">
					<?php $addon_slug = str_replace( '-trial', '', str_replace( '_', '-', $slug ) ) ?>
					<img src="<?php echo esc_url( ATUM_URL . 'assets/images/add-ons/icon-' . $addon_slug . '.svg' ) ?>" alt="<?php echo esc_html( $expired_trial['name'] ) ?>">
				</span>
				<span class="atum-trial-list__item-name">
					<?php echo esc_html( $expired_trial['name'] ) ?> <i class="atum-icon atmi-lock"></i><br>
					<small>
						<?php
						$time_ago = new \Westsworld\TimeAgo();
						/* translators: the expiration date */
						printf( esc_html__( 'Trial has expired %s', ATUM_TEXT_DOMAIN ), esc_html( $time_ago->inWordsFromStrings( '2023-01-30 12:55' ) ) ); ?>
					</small>
				</span>

			</span>

			<span class="atum-trial-list__item-buttons">
				<a href="<?php echo esc_url( $expired_trial['addon_url'] ) ?>" class="btn btn-primary" target="_blank"><?php esc_html_e( 'Purchase', ATUM_TEXT_DOMAIN ); ?></a>
				<button type="button" class="btn btn-outline-primary"><?php esc_html_e( 'Extend Trial', ATUM_TEXT_DOMAIN ); ?></button>
			</span>

		</li>
	<?php endforeach; ?>
</ul>
