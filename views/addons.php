<?php
/**
 * View for the Addons page
 *
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;

?>
<div class="wrap atum-addons" data-nonce="<?php echo esc_attr( wp_create_nonce( ATUM_PREFIX . 'manage_license' ) ) ?>">

	<h1>
		<?php esc_html_e( 'ATUM Add-ons', ATUM_TEXT_DOMAIN ) ?>
		<span class="title-count theme-count"><?php echo esc_attr( ! empty( $addons ) ? count( $addons ) : 0 ) ?></span>
		<a href="<?php echo esc_url( Addons::ADDONS_STORE_URL ) ?>addons/" class="page-title-action" target="_blank"><?php esc_html_e( 'Visit Add-ons Store', ATUM_TEXT_DOMAIN ) ?></a>
	</h1>

	<?php if ( ! empty( $addons ) ) : ?>

		<div class="theme-browser rendered" data-nonce="<?php echo esc_attr( wp_create_nonce( 'atum-addon-action' ) ) ?>">
			<div class="themes wp-clearfix">
				<?php foreach ( $addons as $addon ) :

					$addon_folder         = isset( $addon['info']['folder'] ) ? $addon['info']['folder'] : '';
					$addon_status         = Addons::get_addon_status( $addon['info']['title'], $addon['info']['slug'], $addon_folder );
					$more_details_link    = '<a class="more-details" href="' . $addon['info']['link'] . '" target="_blank">' . __( 'Add-on Details', ATUM_TEXT_DOMAIN ) . '</a>';
					$is_coming_soon_addon = isset( $addon['info']['coming_soon'] ) && $addon['info']['coming_soon'];
					$is_beta              = isset( $addon['info']['is_beta'] ) && $addon['info']['is_beta'];
					?>

					<div class="theme <?php echo esc_attr( $addon_status['status'] ) ?><?php if ( $addon_status['installed'] && 'valid' === $addon_status['status'] ) echo ' active' ?><?php if ( $addon_status['key'] ) echo ' with-key' ?>"
						data-addon="<?php echo esc_attr( $addon['info']['title'] ) ?>" data-addon-slug="<?php echo esc_attr( $addon['info']['slug'] ) ?>"
					>

						<?php if ( ! empty( $addon['info']['thumbnail'] ) ) : ?>
						<div class="theme-screenshot" style="background-image: url(<?php echo esc_url( $addon['info']['thumbnail'] ) ?>)">
						<?php else : ?>
						<div class="theme-screenshot blank">
						<?php endif ?>

							<?php if ( $is_beta ) : ?>
								<span class="label label-warning"><?php esc_html_e( 'Beta', ATUM_TEXT_DOMAIN ) ?></span>
							<?php elseif ( ! $is_coming_soon_addon && ! empty( $addon['licensing']['version'] ) ) : ?>
								<span class="label"><?php echo 'v' . esc_attr( $addon['licensing']['version'] ) ?></span>
							<?php endif; ?>

							<?php if ( ! empty( $addon['info']['excerpt'] ) ) : ?>
							<div class="addon-details">
								<p><?php echo wp_kses_post( $addon['info']['excerpt'] ) ?></p>

								<?php echo $more_details_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<?php else :
								echo $more_details_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							endif ?>

						</div>

						<h2 class="theme-name">

							<?php
							echo esc_html( $addon['info']['title'] );

							$addon_classes = array();

							if ( $is_coming_soon_addon ) :
								$addon_classes[] = 'coming-soon';
							else :
								$addon_classes[] = $addon_status['key'] ? $addon_status['status'] : 'no-key';
							endif;

							if ( 'valid' === $addon_status['status'] && ! $addon_status['installed'] ) :
								$addon_classes[] = 'not-installed';
							endif;
							?>

							<div class="theme-actions <?php echo esc_attr( implode( ' ', $addon_classes ) ) ?>">

								<?php if ( $is_coming_soon_addon ) : ?>

									<span><?php esc_html_e( 'Coming Soon', ATUM_TEXT_DOMAIN ) ?></span>

								<?php elseif ( 'valid' === $addon_status['status'] ) : ?>

									<?php if ( ! $addon_status['installed'] ) : ?>
										<button type="button" title="<?php esc_attr_e( 'Click to install', ATUM_TEXT_DOMAIN ) ?>" class="button install-addon"><?php esc_html_e( 'Install', ATUM_TEXT_DOMAIN ) ?></button>
									<?php else : ?>
										<span><?php esc_html_e( 'Installed', ATUM_TEXT_DOMAIN ) ?></span>
									<?php endif ?>

								<?php elseif ( 'inactive' === $addon_status['status'] ) : ?>

									<span>
										<?php esc_html_e( 'Inactive Key', ATUM_TEXT_DOMAIN ) ?>

										<?php if ( $addon_status['key'] ) : ?>
											<a href="#" class="remove-key atum-tooltip" title="<?php esc_attr_e( 'Remove Key', ATUM_TEXT_DOMAIN ); ?>" data-bs-placement="top">
												<i class="atum-icon atmi-cross-circle"></i>
											</a>
										<?php endif; ?>
									</span>

								<?php elseif ( 'invalid' === $addon_status['status'] && $addon_status['key'] ) : ?>

									<span>
										<?php esc_html_e( 'Invalid Key', ATUM_TEXT_DOMAIN ) ?>
										<a href="#" class="remove-key atum-tooltip" title="<?php esc_attr_e( 'Remove Key', ATUM_TEXT_DOMAIN ); ?>" data-bs-placement="top">
											<i class="atum-icon atmi-cross-circle"></i>
										</a>
									</span>

								<?php endif ?>
							</div>

							<?php if ( ! $is_coming_soon_addon ) : ?>
								<div class="show-key" title="<?php esc_attr_e( 'Show/Hide the license key', ATUM_TEXT_DOMAIN ) ?>">
									<i class="atum-icon atmi-license"></i>
								</div>
							<?php endif ?>

						</h2>

						<?php if ( ! $is_coming_soon_addon ) : ?>
							<div class="addon-key">
								<div class="wrapper">
									<input type="text" autocomplete="false" spellcheck="false" class="<?php if ( $addon_status['key'] ) echo esc_attr( $addon_status['status'] ) ?>"
										value="<?php echo esc_attr( $addon_status['key'] ) ?>" placeholder="<?php esc_attr_e( 'Enter the addon license key...', ATUM_TEXT_DOMAIN ) ?>">
									<button type="button" class="button <?php echo esc_attr( $addon_status['button_class'] ) ?>"
										data-action="<?php echo esc_attr( $addon_status['button_action'] ) ?>"><?php echo esc_html( $addon_status['button_text'] ) ?></button>
								</div>
							</div>
						<?php endif ?>

					</div>

				<?php endforeach; ?>

			</div>
		</div>

	<?php endif; ?>
</div>
