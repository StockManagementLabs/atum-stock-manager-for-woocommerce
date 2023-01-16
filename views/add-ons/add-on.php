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


$addon_folder         = isset( $addon['info']['folder'] ) ?? '';
$addon_status         = Addons::get_addon_status( $addon['info']['title'], $addon['info']['slug'], $addon_folder );
$more_details_link    = '<a href="' . $addon['info']['link'] . '" target="_blank">' . __( 'Add-on Details', ATUM_TEXT_DOMAIN ) . '</a>';
$is_coming_soon_addon = isset( $addon['info']['coming_soon'] ) && $addon['info']['coming_soon'];
$is_beta              = isset( $addon['info']['is_beta'] ) && $addon['info']['is_beta'];
$is_trial             = ! empty( $addon_status['is_trial'] ) && TRUE === $addon_status['is_trial'];
$pill_style           = isset( $addon['info']['primary_color'] ) ? " style='background-color:{$addon['info']['primary_color']}';" : '';
$notice               = '';
$notice_type          = 'info';

$addon_classes = array();
$status_text   = '';

if ( $is_coming_soon_addon ) :
	$addon_status['status'] = $addon_classes[] = 'coming-soon';
	$status_text            = __( 'Coming Soon', ATUM_TEXT_DOMAIN );
elseif ( $is_trial ) :
	$addon_classes[] = 'trial';
	$status_text     = __( 'Trial', ATUM_TEXT_DOMAIN );
elseif ( ! $addon_status['installed'] ) :
	$addon_classes[] = 'not-installed';
	$status_text     = __( 'Not Installed', ATUM_TEXT_DOMAIN );
elseif ( empty( $addon_status['key'] ) ) :
	$addon_classes[] = 'no-key';
	$status_text     = __( 'Not Activated', ATUM_TEXT_DOMAIN );
else :

	switch ( $addon_status['status'] ) :
		case 'valid':
			$addon_classes[] = 'valid';
			$status_text     = __( 'Activated', ATUM_TEXT_DOMAIN );
			break;

		case 'inactive':
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
			$notice      = sprintf( __( 'If you already renewed the license, please click %1$shere%2$s', ATUM_TEXT_DOMAIN ), '<a class="alert-link refresh-status" href="#">', '</a>' );
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

				<?php if ( $is_coming_soon_addon ) : ?>

					<a class="more-details btn btn-outline-primary" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'More info', ATUM_TEXT_DOMAIN ); ?></a>

				<?php elseif ( $is_trial ) : ?>

					<div class="alert alert-warning">
						<i class="atum-icon atmi-warning"></i>

						<?php // TODO: ADD LOGIC... ?>
						<?php if ( TRUE ) : ?>
							<?php
							$time_ago = new TimeAgo();
							/* translators: the time remaining */
							printf( esc_html__( 'Trial period: %s ', ATUM_TEXT_DOMAIN ), str_replace( 'ago', esc_html__( 'remaining', ATUM_TEXT_DOMAIN ), $time_ago->inWordsFromStrings( '2023-01-30 12:55' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<?php esc_html_e( 'Trial period ended. Purchase a license to unlock the full version.', ATUM_TEXT_DOMAIN ) ?>
						<?php endif; ?>

					</div>

					<a class="more-details btn btn-primary" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'Purchase', ATUM_TEXT_DOMAIN ); ?></a>

				<?php else : ?>

					<?php if ( ! $addon_status['installed'] ) : ?>
						<a class="more-details btn btn-primary" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'Install trial', ATUM_TEXT_DOMAIN ); ?></a>
					<?php endif; ?>

					<?php if ( ! $addon_status['installed'] || empty( $addon_status['key'] ) ) : ?>
						<a class="more-details btn btn-primary" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'Purchase', ATUM_TEXT_DOMAIN ); ?></a>
					<?php elseif ( ( 'invalid' === $addon_status['status'] && $addon_status['installed'] ) || in_array( $addon_status['status'], [ 'disabled', 'expired' ] ) ) : ?>
						<a class="more-details btn btn-tertiary" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'Renew License', ATUM_TEXT_DOMAIN ); ?></a>
					<?php endif; ?>

					<?php if ( empty( $addon_status['key'] ) || 'inactive' === $addon_status['status'] ) : ?>
						<a class="more-details btn btn-tertiary show-key" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'Enter License', ATUM_TEXT_DOMAIN ); ?></a>
					<?php else : ?>
						<a class="more-details btn btn-outline-tertiary show-key" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'View License', ATUM_TEXT_DOMAIN ); ?></a>
					<?php endif; ?>

					<div class="addon-key">
						<div class="wrapper">
							<?php if ( ! $addon_status['installed'] || empty( $addon_status['key'] ) ) : ?>

								<input type="text" autocomplete="false" spellcheck="false" class="license-key
									<?php echo $addon_status['key'] ? esc_attr( $addon_status['status'] ) : '' ?>"
									value="" placeholder="<?php esc_attr_e( 'Enter the add-on license key...', ATUM_TEXT_DOMAIN ) ?>"
								>

								<button type="button" class="btn btn-primary <?php echo esc_attr( $addon_status['button_class'] ) ?>"
									data-action="<?php echo esc_attr( $addon_status['button_action'] ) ?>"
								><?php echo esc_html( $addon_status['button_text'] ) ?></button>

								<button type="button" class="btn cancel-action"><?php esc_html_e( 'Cancel', ATUM_EXPORT_TEXT_DOMAIN ); ?></button>

							<?php else : ?>

								<div class="license-info">
									<div class="license-label"><?php esc_html_e( 'License key', ATUM_TEXT_DOMAIN ); ?></div>
									<div class="license-key"><?php echo esc_html( $addon_status['key'] ) ?></div>
								</div>

								<div class="license-info">
									<div class="license-label"><?php esc_html_e( 'Expiration date', ATUM_TEXT_DOMAIN ); ?></div>
									<div class="expires"><?php echo esc_html( $addon_status['expires'] ) ?></div>
								</div>

								<button type="button" class="btn btn-outline-danger remove-license"
									data-action="<?php echo esc_attr( $addon_status['button_action'] ) ?>"
								><?php esc_html_e( 'Remove license', ATUM_EXPORT_TEXT_DOMAIN ); ?></button>
								<button type="button" class="btn cancel-action"><?php esc_html_e( 'Cancel', ATUM_EXPORT_TEXT_DOMAIN ); ?></button>

							<?php endif; ?>

						</div>
					</div>

				<?php endif; ?>

			</div>

		</div>

	</div>

</div>