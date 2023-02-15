<?php
/**
 * View for the Addons page add-on elements
 *
 * @since 1.9.27
 *
 * @var array $addon
 * @var array $installed_addons
 */

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Westsworld\TimeAgo;

$addon_folder         = $addon['info']['folder'] ?? '';
$addon_status         = Addons::get_addon_status( $addon['info']['title'], $addon['info']['slug'], $addon_folder );
$more_details_link    = '<a href="' . $addon['info']['link'] . '" target="_blank">' . __( 'Add-on Details', ATUM_TEXT_DOMAIN ) . '</a>';
$is_coming_soon_addon = isset( $addon['info']['coming_soon'] ) && $addon['info']['coming_soon'];
$is_beta              = isset( $addon['info']['is_beta'] ) && $addon['info']['is_beta'];
$is_trial             = ! empty( $addon_status['is_trial'] ) && TRUE === $addon_status['is_trial'];
$pill_style           = isset( $addon['info']['primary_color'] ) ? " style='background-color:{$addon['info']['primary_color']}';" : '';
$notice               = '';
$notice_type          = 'info';
$actual_timestamp     = time();
$actual_date          = date_i18n( 'Y-m-d H:i:s', $actual_timestamp );
$expiration_timestamp = strtotime( $addon_status['expires'] ?? 'now' );
$expiration_date      = date_i18n( 'Y-m-d H:i:s', $expiration_timestamp );
$is_expired           = 'expired' === $addon_status['status'] || $expiration_timestamp <= $actual_timestamp;

$addon_classes = array();
$status_text   = '';

if ( $is_coming_soon_addon ) :
	$addon_status['status'] = $addon_classes[] = 'coming-soon';
	$status_text            = __( 'Coming Soon', ATUM_TEXT_DOMAIN );
elseif ( $is_trial ) :

	$addon_classes[] = 'trial';
	$status_text     = __( 'Trial', ATUM_TEXT_DOMAIN );

	if ( empty( $addon_status['key'] ) && $addon_status['installed'] ) :
		$notice = esc_html__( 'License key is missing! Please add your key to continue using this trial.', ATUM_TEXT_DOMAIN );
	elseif ( 'trial_used' === $addon_status['status'] ) :
		$notice = esc_html__( 'This trial has been already used on another site and is for a single use only.', ATUM_TEXT_DOMAIN );
	elseif ( ! $is_expired ) :
		$time_ago = new TimeAgo();
		/* translators: the time remaining */
		$notice = sprintf( esc_html__( 'Trial period: %s ', ATUM_TEXT_DOMAIN ), str_replace( 'ago', esc_html__( 'remaining', ATUM_TEXT_DOMAIN ), $time_ago->inWordsFromStrings( $expiration_date ) ) );
	else :
		$notice = esc_html__( 'Trial period expired. Purchase a license to unlock the full version.', ATUM_TEXT_DOMAIN );
	endif;

	$notice_type = 'warning';

elseif ( ! $addon_status['installed'] ) :
	$addon_classes[] = 'not-installed';
	$status_text     = __( 'Not Installed', ATUM_TEXT_DOMAIN );
elseif ( empty( $addon_status['key'] ) ) :
	$addon_classes[] = 'no-key';
	$status_text     = __( 'Missing License!', ATUM_TEXT_DOMAIN );
else :

	switch ( $addon_status['status'] ) :
		case 'valid':
			$addon_classes[] = 'valid';
			$status_text     = __( 'Activated', ATUM_TEXT_DOMAIN );
			break;

		case 'inactive':
		case 'site_inactive':
		case 'not-activated':
			$addon_classes[] = 'inactive';
			$status_text     = __( 'Not Activated', ATUM_TEXT_DOMAIN );
			break;

		case 'invalid':
		case 'disabled':
			$addon_classes[] = 'invalid';
			$status_text     = __( 'Invalid License', ATUM_TEXT_DOMAIN );
			break;

		case 'expired':
			$addon_classes[] = 'expired';
			$status_text     = __( 'Expired', ATUM_TEXT_DOMAIN );
			/* translators: opening and closing link tags */
			$notice      = sprintf( __( 'If you already have renewed the license, please click %1$shere%2$s', ATUM_TEXT_DOMAIN ), '<a class="alert-link refresh-status" href="#">', '</a>' );
			$notice_type = 'warning';
			break;
	endswitch;

endif; ?>

<div class="atum-addon <?php echo esc_attr( $addon_status['status'] ) ?><?php if ( $addon_status['installed'] && 'valid' === $addon_status['status'] ) echo ' active' ?>
	<?php if ( $addon_status['key'] ) echo ' with-key' ?>" data-addon="<?php echo esc_attr( $addon['info']['title'] ) ?>"
	data-addon-slug="<?php echo esc_attr( $addon['info']['slug'] ) ?>"
>

	<a class="more-details" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank">

		<span class="label <?php echo esc_attr( implode( ' ', $addon_classes ) ) ?>"><?php echo esc_attr( $status_text ); ?></span>

		<div class="addon-thumb<?php echo empty( $addon['info']['thumbnail'] ) ? ' blank' : '' ?>"
			<?php echo ! empty( $addon['info']['thumbnail'] ) ? 'style="background-image: url(' . esc_url( $addon['info']['thumbnail'] ) . ')"' : '' ?>
		></div>

	</a>

	<div class="addon-details">

		<div class="addon-header">
			<h2 class="addon-title">
				<?php echo esc_html( $addon['info']['title'] ); ?>
			</h2>

			<?php if ( $is_beta ) : ?>
				<span class="label"<?php echo $pill_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Beta', ATUM_TEXT_DOMAIN ) ?></span>
			<?php elseif ( ! $is_coming_soon_addon && ! empty( $addon['licensing']['version'] ) ) : ?>
				<span class="label"<?php echo $pill_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo 'v' . esc_attr( $addon['licensing']['version'] ) ?><?php if ( $is_trial ) echo ' &ndash; ' . __( 'Trial', ATUM_TEXT_DOMAIN ) ?></span>
			<?php endif; ?>
		</div>

		<div class="addon-description">
			<?php if ( ! empty( $addon['info']['excerpt'] ) ) : ?>
				<p><?php echo wp_kses_post( $addon['info']['excerpt'] ) ?></p>
			<?php endif; ?>
		</div>

		<div class="addon-footer">

			<?php if ( ! empty( $notice ) ) : ?>
				<div class="alert alert-<?php echo esc_attr( $notice_type ); ?>">
					<i class="atum-icon atmi-warning"></i>
					<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>

			<div class="actions <?php echo esc_attr( implode( ' ', $addon_classes ) ) ?>">

				<div class="actions__buttons">
					<?php if ( $is_coming_soon_addon ) : ?>

						<a class="more-details btn btn-outline-primary" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'More info', ATUM_TEXT_DOMAIN ); ?></a>

					<?php elseif ( $is_trial ) : ?>

						<?php
						$purchase_url = add_query_arg( [
							'edd_action'  => 'add_to_cart',
							'download_id' => $addon['info']['id'],
						], Addons::ADDONS_STORE_URL . 'checkout/' ) ?>
						<a class="more-details btn btn-primary" href="<?php echo esc_url( $purchase_url ) ?>" target="_blank"><?php esc_html_e( 'Purchase', ATUM_TEXT_DOMAIN ); ?></a>

						<?php if ( $is_expired && empty( $addon_status['extended'] ) && ! empty( $addon_status['key'] ) ) : ?>
							<button type="button" class="btn btn-outline-primary extend-atum-trial" data-key="<?php echo esc_attr( $addon_status['key'] ) ?>"><?php esc_html_e( 'Extend trial', ATUM_TEXT_DOMAIN ); ?></button>
						<?php endif; ?>

					<?php else : ?>

						<?php if ( ! $addon_status['installed'] ) :
							$add_trial_to_cart_url = add_query_arg( [
								'edd_action'         => 'add_to_cart',
								'download_id'        => $addon['info']['id'],
								'edd_options[trial]' => 1,
							], Addons::ADDONS_STORE_URL . 'checkout/' ) ?>
							<a class="more-details btn btn-primary" href="<?php echo $add_trial_to_cart_url // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" target="_blank">
								<?php esc_html_e( 'Start free trial', ATUM_TEXT_DOMAIN ); ?>
							</a>
						<?php endif; ?>

						<?php if ( ! $addon_status['installed'] || empty( $addon_status['key'] ) ) : ?>
							<a class="more-details btn btn-primary" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'Purchase', ATUM_TEXT_DOMAIN ); ?></a>
						<?php elseif ( ( 'invalid' === $addon_status['status'] && $addon_status['installed'] ) || in_array( $addon_status['status'], [ 'disabled', 'expired' ] ) ) : ?>
							<a class="more-details btn btn-success" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'Renew License', ATUM_TEXT_DOMAIN ); ?></a>
						<?php endif; ?>

					<?php endif; ?>

					<?php if ( ! $is_coming_soon_addon ) : ?>

						<?php if ( empty( $addon_status['key'] ) || 'inactive' === $addon_status['status'] ) : ?>
							<button type="button" class="more-details btn btn-success show-key"><?php esc_html_e( 'Enter License', ATUM_TEXT_DOMAIN ); ?></button>
						<?php else : ?>
							<button type="button" class="more-details btn btn-outline-success show-key"><?php esc_html_e( 'View License', ATUM_TEXT_DOMAIN ); ?></button>
						<?php endif; ?>

					<?php endif; ?>
				</div>

				<?php if ( ! $is_coming_soon_addon ) : ?>
					<div class="addon-key">
						<div class="wrapper">
							<?php if ( ! $addon_status['installed'] || empty( $addon_status['key'] ) ) : ?>

								<input type="text" autocomplete="false" spellcheck="false" class="license-key
									<?php echo $addon_status['key'] ? esc_attr( $addon_status['status'] ) : '' ?>"
									value="<?php echo esc_attr( $addon_status['key'] ?? '' ) ?>"
									placeholder="<?php esc_attr_e( 'Enter the add-on license key...', ATUM_TEXT_DOMAIN ) ?>"
								>

								<?php if ( ! empty( $addon_status['button_text'] ) && ! empty( $addon_status['button_action'] ) ) : ?>
									<button type="button" class="btn btn-primary <?php echo esc_attr( $addon_status['button_class'] ?? '' ) ?>"
										data-action="<?php echo esc_attr( $addon_status['button_action'] ) ?>"
									><?php echo esc_html( $addon_status['button_text'] ) ?></button>
								<?php endif; ?>

								<button type="button" class="btn cancel-action"><?php esc_html_e( 'Cancel', ATUM_TEXT_DOMAIN ); ?></button>

							<?php else : ?>

								<div class="license-info">
									<div class="license-label"><?php esc_html_e( 'License key', ATUM_TEXT_DOMAIN ); ?></div>
									<div class="license-key"><?php echo esc_html( $addon_status['key'] ) ?></div>
								</div>

								<?php if ( ! empty( $addon_status['expires'] ) ) : ?>
									<div class="license-info">
										<div class="license-label"><?php esc_html_e( 'Expiration date', ATUM_TEXT_DOMAIN ); ?></div>
										<div class="expires<?php echo ( ( $is_trial && $is_expired ) || ( ! $is_expired && $is_expired && 'valid' !== $addon_status['status'] ) ) ? esc_attr( ' expired' ) : '' ?>">
											<?php echo esc_html( $addon_status['expires'] ) ?>
										</div>
									</div>
								<?php endif; ?>

								<button type="button" class="btn btn-outline-danger remove-license"
									data-action="atum_remove_license"
								><?php esc_html_e( 'Remove license', ATUM_TEXT_DOMAIN ); ?></button>

								<button type="button" class="btn cancel-action"><?php esc_html_e( 'Cancel', ATUM_TEXT_DOMAIN ); ?></button>

							<?php endif; ?>

						</div>
					</div>

				<?php endif; ?>

			</div>

		</div>

	</div>

</div>
