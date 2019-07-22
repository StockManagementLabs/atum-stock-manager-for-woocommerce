<?php
/**
 * View for the Settings admin page
 *
 * @since 0.0.1
 *
 * @var string $active
 * @var array  $active_sections
 */

defined( 'ABSPATH' ) || die;

use Atum\Settings\Settings;
use Atum\Components\AtumColors;

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
							<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/atum-icon.svg" title="<?php echo esc_attr( 'Visit ATUM Website', ATUM_TEXT_DOMAIN ) ?>">
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
										<?php echo esc_attr( $atts['tab_name'] ); ?>
									</span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="nav-footer">
					<div class="nav-footer-logo">
						<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/atum-icon.svg" title="<?php esc_attr( 'Visit ATUM Website', ATUM_TEXT_DOMAIN ) ?>">

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

			<form id="atum-settings" method="post" action="options.php" style="display: none">
				<div class="form-settings-wrapper">

					<?php
					global $wp_settings_sections, $wp_settings_fields;

					foreach ( array_keys( $active_sections ) as $active_section_key => $active_section ) :

						// Check if is last section.
						$last_section = count( $active_sections ) - 1 === $active_section_key ? TRUE : FALSE;

						// This prints out all hidden setting fields.
						settings_fields( ATUM_PREFIX . "setting_$active_section" );

						$page = ATUM_PREFIX . "setting_$active_section";

						if ( ! isset( $wp_settings_sections[ $page ] ) ) :
							continue;
						endif;

						foreach ( (array) $wp_settings_sections[ $page ] as $section ) : ?>

							<div id="<?php echo esc_attr( $section['id'] ) ?>" class="settings-section" data-section="<?php echo esc_attr( str_replace( [ ATUM_PREFIX, 'setting_' ], '', $section['id'] ) ) ?>">

								<?php if ( ! $last_section || 1 === count( $active_sections ) ) : ?>

									<div class="section-general-title">
										<?php
										$header_settings_title = NULL;

										switch ( $section['id'] ) :
											case 'atum_setting_general':
												$header_settings_title = __( 'General', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_company':
												$header_settings_title = __( 'Store Details', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_color_mode':
												$header_settings_title = __( 'Visual Settings', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_module_manager':
												$header_settings_title = __( 'Modules', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_stock_central':
												$header_settings_title = __( 'Stock Central', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_multi_inventory':
												$header_settings_title = __( 'Multi-Inventory', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_product_levels':
												$header_settings_title = __( 'Product Levels', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_tools':
												$header_settings_title = __( 'Tools', ATUM_TEXT_DOMAIN );
												break;
										endswitch;
										?>

										<h2><?php echo esc_attr( $header_settings_title ) ?></h2>

										<?php submit_button( __( 'Save Changes', ATUM_TEXT_DOMAIN ) ); ?>
									</div>
								<?php endif; ?>

								<?php if ( $section['title'] ) : ?>
									<div class="section-title">
										<h2>
											<?php if ( 'atum_setting_scheme_color' === $section['id'] ) :

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

									<?php if ( 'atum_setting_scheme_color' === $section['id'] ) : ?>
										<button class="btn btn-primary reset-default-colors" data-reset="1"
											type="button" data-value="<?php echo esc_attr( $theme ); ?>"><?php echo esc_html( __( 'Reset To Default', ATUM_TEXT_DOMAIN ) ) ?></button>
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
		</div>

	</div>
</div>
