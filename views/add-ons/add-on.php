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

$addon_folder         = $addon['info']['folder'] ?? '';
$addon_status         = Addons::get_addon_status( $addon['info']['title'], $addon['info']['slug'], $addon_folder );
$more_details_link    = '<a href="' . $addon['info']['link'] . '" target="_blank">' . __( 'Add-on Details', ATUM_TEXT_DOMAIN ) . '</a>';
$is_coming_soon_addon = isset( $addon['info']['coming_soon'] ) && $addon['info']['coming_soon'];
$is_beta              = isset( $addon['info']['is_beta'] ) && $addon['info']['is_beta'];
$is_trial             = ! empty( $addon_status->is_trial ) && TRUE === $addon_status->is_trial;
$pill_style           = isset( $addon['info']['primary_color'] ) ? " style='background-color:{$addon['info']['primary_color']}';" : '';
$is_expired           = ! empty( $addon_status->is_expired ) && TRUE === $addon_status->is_expired;

if ( $is_coming_soon_addon ) :
	$addon_status->status     = 'coming-soon';
	$addon_status->classes    = [ 'coming-soon' ];
	$addon_status->label_text = __( 'Coming Soon', ATUM_TEXT_DOMAIN );
elseif ( $addon_status->installed ) :
	$current_version = Addons::get_installed_version( $addon['info']['title'] );
endif;
?>

<div class="atum-addon <?php echo esc_attr( $addon_status->status ) ?><?php if ( $addon_status->installed && 'valid' === $addon_status->status ) echo ' active' ?>
	<?php if ( $addon_status->key ) echo ' with-key' ?>" data-addon="<?php echo esc_attr( $addon['info']['title'] ) ?>"
	data-addon-slug="<?php echo esc_attr( $addon['info']['slug'] ) ?>"
>

	<a class="more-details" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank">

		<span class="label <?php echo esc_attr( implode( ' ', $addon_status->classes ) ) ?>"><?php echo esc_attr( $addon_status->label_text ); ?></span>

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
				<span class="label"<?php echo $pill_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> title="<?php esc_attr_e( 'Latest Version Available', ATUM_TEXT_DOMAIN ) ?>"><?php echo 'v' . esc_attr( $addon['licensing']['version'] ) ?><?php if ( $is_trial ) echo ' &ndash; ' . __( 'Trial', ATUM_TEXT_DOMAIN ) ?></span>
			<?php endif; ?>
		</div>

		<div class="addon-description">
			<?php if ( ! empty( $addon['info']['excerpt'] ) ) : ?>
				<p><?php echo wp_kses_post( $addon['info']['excerpt'] ) ?></p>
			<?php endif; ?>
		</div>

		<div class="addon-footer">

			<?php if ( ! empty( $addon_status->notice ) ) : ?>
				<div class="alert alert-<?php echo esc_attr( $addon_status->notice_type ); ?>">
					<i class="atum-icon atmi-<?php echo esc_attr( in_array( $addon_status->notice_type, [ 'warning', 'danger' ] ) ? 'warning' : 'info' ) ?>"></i>
					<span><?php echo $addon_status->notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $addon_status->installed && ! empty( $current_version ) && version_compare( $current_version, $addon['licensing']['version'], '<' ) ) : ?>
				<div class="alert alert-primary">
					<i class="atum-icon atmi-info"></i>
					<span>
						<?php
						/* translators: open and closing link tags */
						printf( esc_html__( 'There is a new version available. We recommend you %1$supdate%2$s it as soon as possible.', ATUM_TEXT_DOMAIN ), '<a href="' . esc_url( self_admin_url( 'update-core.php' ) ) . '">', '</a>' ); ?>
					</span>
				</div>
			<?php endif; ?>

			<div class="actions <?php echo esc_attr( implode( ' ', $addon_status->classes ) ) ?>">

				<div class="actions__buttons"<?php echo ( ! $addon_status->installed && ! empty( $addon_status->key ) ) ? ' style="display:none"' : '' ?>>
					<?php if ( $is_coming_soon_addon ) : ?>

						<a class="more-details btn btn-outline-primary" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'More info', ATUM_TEXT_DOMAIN ); ?></a>

					<?php elseif ( $is_trial ) : ?>

						<?php
						$query_args = array(
							'key' => $addon_status->key,
							'url' => home_url(),
						);

						$purchase_url = add_query_arg( $query_args, Addons::ADDONS_STORE_URL . 'my-upgrades/' ) ?>
						<a class="more-details btn btn-primary" href="<?php echo esc_url( $purchase_url ) ?>" target="_blank">
							<?php esc_html_e( 'Upgrade', ATUM_TEXT_DOMAIN ) ?>
						</a>

						<?php if ( $is_expired && empty( $addon_status->extended ) && ! empty( $addon_status->key ) && 'trial_used' !== $addon_status->status ) : ?>
							<button type="button" class="btn btn-outline-primary extend-atum-trial" data-key="<?php echo esc_attr( $addon_status->key ) ?>"><?php esc_html_e( 'Extend trial', ATUM_TEXT_DOMAIN ); ?></button>
						<?php endif; ?>

					<?php else : ?>

						<?php if ( ! $addon_status->installed ) :
							$add_trial_to_cart_url = add_query_arg( [
								'edd_action'         => 'add_to_cart',
								'download_id'        => $addon['info']['id'],
								'edd_options[trial]' => 1,
							], Addons::ADDONS_STORE_URL . 'checkout/' ) ?>
							<a class="more-details btn btn-primary" href="<?php echo $add_trial_to_cart_url // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" target="_blank">
								<?php esc_html_e( 'Start free trial', ATUM_TEXT_DOMAIN ); ?>
							</a>
						<?php endif; ?>

						<?php if ( ! $addon_status->installed || empty( $addon_status->key ) ) : ?>
							<a class="more-details btn btn-primary" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'Purchase', ATUM_TEXT_DOMAIN ); ?></a>
						<?php elseif ( ( 'invalid' === $addon_status->status && $addon_status->installed ) || in_array( $addon_status->status, [ 'disabled', 'expired' ] ) ) : ?>
							<a class="more-details btn btn-success" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank"><?php esc_html_e( 'Renew License', ATUM_TEXT_DOMAIN ); ?></a>
						<?php endif; ?>

					<?php endif; ?>

					<?php if ( ! $is_coming_soon_addon && current_user_can( 'install_plugins' ) ) : ?>

						<?php if ( empty( $addon_status->key ) || 'inactive' === $addon_status->status ) : ?>
							<button type="button" class="more-details btn btn-success show-key"><?php esc_html_e( 'Enter License', ATUM_TEXT_DOMAIN ); ?></button>
						<?php else : ?>
							<button type="button" class="more-details btn btn-outline-success show-key"><?php esc_html_e( 'View License', ATUM_TEXT_DOMAIN ); ?></button>
						<?php endif; ?>

					<?php endif; ?>
				</div>

				<?php if ( ! $is_coming_soon_addon && current_user_can( 'install_plugins' ) ) : ?>
					<div class="addon-key"<?php echo ( ! $addon_status->installed && ! empty( $addon_status->key ) ) ? ' style="display:block"' : '' ?>>
						<div class="wrapper">
							<?php if ( ! $addon_status->installed || empty( $addon_status->key ) ) : ?>

								<input type="text" autocomplete="false" spellcheck="false" class="license-key
									<?php echo $addon_status->key ? esc_attr( $addon_status->status ) : '' ?>"
									value="<?php echo esc_attr( $addon_status->key ) ?>"
									placeholder="<?php esc_attr_e( 'Enter the add-on license key...', ATUM_TEXT_DOMAIN ) ?>"
								>

								<?php if ( ! empty( $addon_status->button_text ) && ! empty( $addon_status->button_action ) ) : ?>
									<button type="button" class="btn btn-primary <?php echo esc_attr( $addon_status->button_class ?? '' ) ?>"
										data-action="<?php echo esc_attr( $addon_status->button_action ) ?>"
									><?php echo esc_html( $addon_status->button_text ) ?></button>
								<?php endif; ?>

								<button type="button" class="btn cancel-action"><?php esc_html_e( 'Cancel', ATUM_TEXT_DOMAIN ); ?></button>

							<?php else : ?>

								<div class="license-info">
									<div class="license-label"><?php esc_html_e( 'License key', ATUM_TEXT_DOMAIN ); ?></div>
									<div class="license-key"><?php echo esc_html( $addon_status->key ) ?></div>
								</div>

								<?php if ( ! empty( $addon_status->expires ) ) : ?>
									<div class="license-info">
										<div class="license-label"><?php esc_html_e( 'Expiration date', ATUM_TEXT_DOMAIN ); ?></div>
										<div class="expires<?php echo ( ( $is_trial && $is_expired ) || ( ! $is_trial && $is_expired && 'valid' !== $addon_status->status ) ) ? esc_attr( ' expired' ) : '' ?>">
											<?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $addon_status->expires ) ) ) ?>
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
