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
		'The following add-on has an expired trial license. ATUM has blocked their use but did not delete the work progress.',
		'The following add-ons have an expired trial license. ATUM has blocked their use but did not delete the work progress.',
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
					<?php echo esc_html( trim( str_replace( 'Trial', '', $expired_trial['name'] ) ) ) ?> <i class="atum-icon atmi-lock"></i><br>
					<small>
						<?php if ( empty( $expired_trial['expires'] ) || 'now' === $expired_trial['expires'] ) :
							esc_html_e( 'Trial expired!', ATUM_TEXT_DOMAIN );
						else :

							$time_ago = new \Westsworld\TimeAgo();
							/* translators: the expiration date */
							printf( esc_html__( 'Trial expired %s', ATUM_TEXT_DOMAIN ), esc_html( $time_ago->inWordsFromStrings( $expired_trial['expires'] ) ) );

						endif; ?>
					</small>
				</span>

			</span>

			<span class="atum-trial-list__item-buttons atum-addon" data-addon="<?php echo esc_attr( $expired_trial['name'] ) ?>">
				<a href="<?php echo esc_url( $expired_trial['addon_url'] ) ?>" class="btn btn-primary" target="_blank"><?php esc_html_e( 'Purchase', ATUM_TEXT_DOMAIN ); ?></a>

				<?php if ( ! $expired_trial['extended'] ) : ?>
					<button type="button" class="btn btn-outline-primary extend-atum-trial" data-key="<?php echo esc_attr( $expired_trial['key'] ?? '' ) ?>" ><?php esc_html_e( 'Extend Trial', ATUM_TEXT_DOMAIN ); ?></button>
				<?php endif; ?>
			</span>

		</li>
	<?php endforeach; ?>
</ul>
