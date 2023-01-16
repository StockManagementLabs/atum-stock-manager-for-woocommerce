<?php
/**
 * View for the Addons page
 *
 * @since 1.2.0
 *
 * @var array|bool $addons
 */

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Inc\Helpers;

?>
<div class="atum-addons" data-nonce="<?php echo esc_attr( wp_create_nonce( ATUM_PREFIX . 'manage_license' ) ) ?>">

	<section class="atum-addons__header">

		<div class="atum-addons__header-logo">
			<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/add-ons/atum-logo-addons.svg" alt="ATUM Premium Add-ons">
			<h3><?php esc_html_e( 'Bring your e-commerce to the next level and get the complete control over ready to sell inventory in one beautifully designed stock management package.', ATUM_TEXT_DOMAIN ) ?></h3>
		</div>

		<div class="atum-addons__header-buttons">
			<a href="<?php echo esc_url( Addons::ADDONS_STORE_URL ) ?>addons/" target="_blank" type="button" class="btn btn-primary"><?php esc_html_e( 'Visit add-ons store', ATUM_TEXT_DOMAIN ) ?></a>
			<a href="#atum-addons-list" class="btn btn-outline-primary"><?php esc_html_e( 'View add-ons info', ATUM_TEXT_DOMAIN ) ?></a>
		</div>

		<div class="atum-addons__header-notice">
			<?php echo wp_kses_post( Helpers::get_rating_text() ) ?>
		</div>
	</section>

	<?php if ( ! empty( $addons ) && is_array( $addons ) ) : ?>

		<div class="atum-addons-wrap" data-nonce="<?php echo esc_attr( wp_create_nonce( 'atum-addon-action' ) ) ?>">

			<div class="list-table-header">
				<div class="nav-container-box">
					<nav class="nav-with-scroll-effect dragscroll">
						<ul class="subsubsub extend-list-table">
							<li class="all" data-status="all"><span class="active"><?php esc_html_e( 'All', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="activated" data-status="valid"><span><?php esc_html_e( 'Activated', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="not-activated" data-status="not-activated"><span><?php esc_html_e( 'Not activated', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="expired" data-status="expired"><span><?php esc_html_e( 'Expired', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="not-installed" data-status="not-installed"><span><?php esc_html_e( 'Not installed', ATUM_TEXT_DOMAIN ) ?></span></li>
							<li class="invalid" data-status="invalid"><span><?php esc_html_e( 'Invalid', ATUM_TEXT_DOMAIN ) ?></span></li>
						</ul>

						<div class="overflow-opacity-effect-right"></div>
						<div class="overflow-opacity-effect-left"></div>
					</nav>
				</div>
			</div>


			<div id="atum-addons-list">
				<?php foreach ( $addons as $addon ) :

					$addon_folder = isset( $addon['info']['folder'] ) ? $addon['info']['folder'] : '';
					$addon_status = Addons::get_addon_status( $addon['info']['title'], $addon['info']['slug'], $addon_folder );
					$more_details_link = '<a href="' . $addon['info']['link'] . '" target="_blank">' . __( 'Add-on Details', ATUM_TEXT_DOMAIN ) . '</a>';
					$is_coming_soon_addon = isset( $addon['info']['coming_soon'] ) && $addon['info']['coming_soon'];
					$is_beta = isset( $addon['info']['is_beta'] ) && $addon['info']['is_beta'];
					$pill_style = isset( $addon['info']['primary_color'] ) ? " style='background-color:{$addon['info']['primary_color']}';" : '';
					$notice = '';
					$notice_type = 'info';


					$addon_classes = array();
					$status_text = '';

					if ( $is_coming_soon_addon ) :
						$addon_status['status'] = $addon_classes[] = 'coming-soon';
						$status_text            = __( 'Coming Soon', ATUM_TEXT_DOMAIN );
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

					<div class="atum-addon <?php echo esc_attr( $addon_status['status'] ) ?><?php if ( $addon_status['installed'] && 'valid' === $addon_status['status'] ) echo ' active' ?><?php if ( $addon_status['key'] ) echo ' with-key' ?>"
						data-addon="<?php echo esc_attr( $addon['info']['title'] ) ?>" data-addon-slug="<?php echo esc_attr( $addon['info']['slug'] ) ?>">

						<a class="more-details" href="<?php echo esc_url( $addon['info']['link'] ) ?>" target="_blank">

							<span class="label <?php echo esc_attr( implode( ' ', $addon_classes ) ) ?>"><?php echo esc_attr( $status_text ); ?></span>

							<?php if ( ! empty( $addon['info']['thumbnail'] ) ) : ?>
							<div class="addon-thumb" style="background-image: url(<?php echo esc_url( $addon['info']['thumbnail'] ) ?>)">
								<?php else : ?>
							<div class="addon-thumb blank">
								<?php endif; ?>

							</div>

						</a>

						<div class="addon-details">

							<div class="addon-header">
								<h2 class="addon-title">
									<?php echo esc_html( $addon['info']['title'] ); ?>
								</h2>

								<?php if ( $is_beta ) : ?>
									<span class="label"<?php echo $pill_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Beta', ATUM_TEXT_DOMAIN ) ?></span>
								<?php elseif ( ! $is_coming_soon_addon && ! empty( $addon['licensing']['version'] ) ) : ?>
									<span class="label"<?php echo $pill_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo 'v' . esc_attr( $addon['licensing']['version'] ) ?></span>
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

									<?php else : // ! $is_coming_soon_addon ?>

										<?php /*// Disabled until Trials are available. ?>
										<?php if ( ! $addon_status['installed'] ) : ?>
											<a class="more-details btn btn-primary" href="<?php echo $addon['info']['link'] ?>" target="_blank"><?php esc_html_e( 'Install trial', ATUM_TEXT_DOMAIN ); ?></a>
										<?php endif;*/ ?>

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

													<button type="button" class="btn cancel-action"><?php esc_html_e( 'Cancel', ATUM_TEXT_DOMAIN ); ?></button>

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

				<?php endforeach; ?>

			</div>
		</div>

	<?php endif; ?>
</div>
