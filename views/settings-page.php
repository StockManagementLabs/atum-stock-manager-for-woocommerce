<?php
/**
 * View for the Settings admin page
 *
 * @since 0.0.1
 *
 * @var string $active
 * @var array  $tabs
 */

defined( 'ABSPATH' ) || die;

use Atum\Settings\Settings;
use Atum\Components\AtumColors;

$active_sections = [];

?>
<div class="wrap">
	<div class="atum-settings-wrapper">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Settings', ATUM_TEXT_DOMAIN ) ?></h1>
		<hr class="wp-header-end">
		
		<?php settings_errors(); ?>

		<div class="atum-settings-container">

			<nav class="atum-nav">

				<div class="atum-nav-header">
					<a class="atum-brand-link" href="https://www.stockmanagementlabs.com" target="_blank">
						<div class="atum-brand">
							<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/atum-icon.svg" title="<?php echo esc_attr_e( 'Visit ATUM Website', ATUM_TEXT_DOMAIN ) ?>" alt="">
							<span><?php esc_html_e( 'ATUM', ATUM_TEXT_DOMAIN ) ?></span>
						</div>
					</a>

					<button class="toogle-menu">
						<span class="atum-menu atmi-menu"></span>
					</button>
				</div>

				<ul class="atum-nav-list">
					<?php foreach ( $tabs as $tab => $atts ) :

						if ( $tab === $active ) :
							$active_sections = $atts['sections'];
						endif; ?>

						<li class="atum-nav-item<?php if ( isset( $atts['no_submit'] ) && $atts['no_submit'] ) echo ' no-submit' ?>">
							<a href="?page=atum-settings&tab=<?php echo esc_attr( $tab ) ?>" rel="address:/<?php echo esc_attr( $tab ) ?>" data-tab="<?php echo esc_attr( $tab ) ?>" class="atum-nav-link<?php if ($tab === $active) echo ' active' ?>">
								<span class="menu-helper">
									<i class="<?php echo esc_attr( $atts['icon'] ); ?>"></i>
									<span>
										<?php echo esc_attr( $atts['label'] ); ?>
									</span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="nav-footer">
					<div class="nav-footer-logo">
						<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/atum-icon.svg" title="<?php esc_attr_e( 'Visit ATUM Website', ATUM_TEXT_DOMAIN ) ?>" alt="">

						<span><?php esc_html_e( 'ATUM', ATUM_TEXT_DOMAIN ) ?></span>
					</div>
					<p>
						<?php
						/* translators: the current ATUM version */
						echo esc_html( sprintf( __( 'Current version: %s', ATUM_TEXT_DOMAIN ), ATUM_VERSION ) );
						?>
					</p>
				</div>

			</nav>

			<?php if ( ! empty( $active_sections ) ) : ?>

				<form id="atum-settings" method="post" action="options.php" style="display: none">
					<div class="form-settings-wrapper">

						<?php
						global $wp_settings_sections, $wp_settings_fields;

						foreach ( array_keys( $active_sections ) as $active_section_index => $active_section ) :

							// This prints out all hidden setting fields.
							settings_fields( ATUM_PREFIX . "setting_$active_section" );

							$page = ATUM_PREFIX . "setting_$active_section";

							if ( ! isset( $wp_settings_sections[ $page ] ) ) :
								continue;
							endif;

							foreach ( (array) $wp_settings_sections[ $page ] as $section ) : ?>

								<div id="<?php echo esc_attr( $section['id'] ) ?>" class="settings-section" data-section="<?php echo esc_attr( str_replace( [ ATUM_PREFIX, 'setting_' ], '', $section['id'] ) ) ?>">

									<?php if ( 0 === $active_section_index ) : // Add the page header before the first section only. ?>

										<div class="section-general-title">
											<h2><?php echo esc_attr( ! empty( $tabs[ $active ]['label'] ) ? $tabs[ $active ]['label'] : '' ) ?></h2>

											<?php submit_button( __( 'Save Changes', ATUM_TEXT_DOMAIN ) ); ?>
										</div>
									<?php endif; ?>

									<?php if ( $section['title'] ) : ?>
										<div class="section-title">
											<h2>
												<?php if ( 'atum_setting_color_scheme' === $section['id'] ) :

													$theme = AtumColors::get_user_theme();

													if ( 'dark_mode' === $theme ) :
														$theme_style = __( 'Dark', ATUM_TEXT_DOMAIN );
													elseif ( 'hc_mode' === $theme ) :
														$theme_style = __( 'High Contrast', ATUM_TEXT_DOMAIN );
													else :
														$theme_style = __( 'Branded', ATUM_TEXT_DOMAIN );
													endif;
													?>

													<span>
														<?php echo esc_html( $theme_style ) ?>
													</span>
												<?php endif; ?>

												<?php echo esc_html( $section['title'] ) ?>
											</h2>
										</div>
								<?php endif; ?>

								<?php if ( $section['callback'] ) :
									call_user_func( $section['callback'], $section );
								endif; ?>

								<?php if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) :
									continue;
								endif; ?>

									<?php $theme = str_replace( '_', '-', AtumColors::get_user_theme() ); ?>

									<div class="section-fields">
										<table class="form-table" id="atum-table-color-settings" data-display="<?php echo esc_html( $theme ); ?>">
											<?php do_settings_fields( $page, $section['id'] ); ?>
										</table>

										<?php if ( 'atum_setting_color_scheme' === $section['id'] ) : ?>
											<button class="btn btn-primary reset-default-colors" data-reset="1"
												type="button" data-value="<?php echo esc_attr( $theme ); ?>"><?php echo esc_html( __( 'Reset to default', ATUM_TEXT_DOMAIN ) ) ?></button>
										<?php endif; ?>

									</div>

								</div>

							<?php endforeach;

						endforeach; ?>

						<input type="hidden" id="atum_settings_section" name="<?php echo esc_attr( Settings::OPTION_NAME ) ?>[settings_section]" value="<?php echo esc_attr( $active ) ?>">

						<?php
						// Add a hidden field to restore WooCommerce manage_stock individual settings.
						if ( 'stock_central' === $active ) : ?>
							<input type="hidden" id="atum_restore_option_stock" name="<?php echo esc_attr( Settings::OPTION_NAME ) ?>[restore_option_stock]" value="no">
						<?php endif ?>

					</div>
				</form>

			<?php else : ?>

				<div class="alert alert-danger">
					<i class="atum-icon atmi-warning"></i>
					<?php esc_html_e( "The setting page couldn't be loaded", ATUM_TEXT_DOMAIN ); ?>
				</div>

			<?php endif; ?>

		</div>

	</div>
</div>
