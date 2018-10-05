<?php
/**
 * View for the Settings admin page
 *
 * @since 0.0.1
 */

defined( 'ABSPATH' ) or die;

use Atum\Settings\Settings;

/**
 * @var string $active
 * @var array  $active_sections
 */
$menu_theme = get_user_meta( get_current_user_id(), 'menu_settings_theme', TRUE );
?>
<div class="wrap">
	<div class="atum-settings-wrapper">
		<div class="switch-interface-style">
			<?php wp_nonce_field( 'add_atum_nonce_field', 'menu-theme-nonce' ); ?>
			Menu Dark <input type="checkbox" class="js-switch-menu" name="interface_style" <?php echo isset( $menu_theme ) && 'light' === $menu_theme ? 'checked' : '' ?>> Menu Light
		</div>
		<h1 class="wp-heading-inline"><?php _e('ATUM Settings', ATUM_TEXT_DOMAIN) ?></h1>
		<hr class="wp-header-end">
		
		<?php settings_errors(); ?>

		<div class="atum-settings-container">
			<nav class="atum-nav <?php echo isset( $menu_theme ) && 'light' === $menu_theme ? 'atum-nav-light' : '' ?>">

				<a class="atum-brand-link" href="https://www.stockmanagementlabs.com" target="_blank">
					<div class="atum-brand">
						<img src="<?php echo ATUM_URL ?>assets/images/atum-icon.svg" title="<?php _e('Visit ATUM Website', ATUM_TEXT_DOMAIN) ?>">
						<span>
							<?php echo esc_attr(__('ATUM', ATUM_TEXT_DOMAIN) ) ?>
						</span>
					</div>
				</a>

				<ul class="atum-nav-list">

					<?php foreach ( $tabs as $tab => $atts ):

						if ($tab == $active):
							$active_sections = $atts['sections'];
						endif; ?>

						<li class="atum-nav-item<?php if ( isset( $atts['no_submit'] ) && $atts['no_submit'] ) echo ' no-submit' ?>">
							<a href="?page=atum-settings&tab=<?php echo $tab ?>" rel="address:/<?php echo $tab ?>" data-tab="<?php echo $tab ?>" class="atum-nav-link<?php if ($tab == $active) echo ' active' ?>">
								<span class="menu-helper">
									<i class="<?php echo $atts['icon']; ?>"></i>
									<?php echo $atts['tab_name'] ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="nav-footer">
					<div class="nav-footer-logo">
						<img src="<?php echo ATUM_URL ?>assets/images/atum-icon.svg" title="<?php _e('Visit ATUM Website', ATUM_TEXT_DOMAIN) ?>">
						<span>
						<?php echo esc_attr(__('ATUM', ATUM_TEXT_DOMAIN) ) ?>
						</span>
					</div>
					<p>
						<?php echo esc_attr(__('Current version: ' . ATUM_VERSION, ATUM_TEXT_DOMAIN) ) ?>
						<a href="#"><?php echo esc_attr(__('Check updates', ATUM_TEXT_DOMAIN) ) ?></a>
					</p>
				</div>
			</nav>

			<form id="atum-settings" method="post" action="options.php" style="display: none">
				<div class="form-settings-wrapper">

					<?php
					global $wp_settings_sections, $wp_settings_fields;

					foreach ( array_keys($active_sections) as $active_section ):

						// This prints out all hidden setting fields
						settings_fields( ATUM_PREFIX . "setting_$active_section" );

						$page = ATUM_PREFIX . "setting_$active_section";

						if ( ! isset( $wp_settings_sections[$page] ) ):
							continue;
						endif;

						foreach ( (array) $wp_settings_sections[$page] as $section ): ?>

							<div id="<?php echo $section['id'] ?>" class="settings-section" data-section="<?php echo str_replace([ATUM_PREFIX, 'setting_'], '', $section['id']) ?>">
								<?php if ( 'atum_setting_shipping' !== $section['id'] && 'atum_setting_manufacturing_central' !== $section['id']) : ?>
									<?php $menu_theme = get_user_meta( get_current_user_id(), 'menu_settings_theme', TRUE ); ?>
									<div class="section-general-title <?php echo isset( $menu_theme ) && 'light' === $menu_theme ? 'section-general-title-light' : '' ?>">
										<?php

										$header_settings_title = null;
										switch ( $section['id'] ) {
											case 'atum_setting_general':
												$header_settings_title = __( 'General', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_company':
												$header_settings_title = __( 'Store Details', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_module_manager':
												$header_settings_title = __( 'Modules', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_stock_central':
												$header_settings_title = __( 'Stock Central', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_multi_inventory':
												$header_settings_title = __( 'MULTI-INVENTORY', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_product_levels':
												$header_settings_title = __( 'PRODUCT LEVELS', ATUM_TEXT_DOMAIN );
												break;
											case 'atum_setting_tools':
												$header_settings_title = __( 'Tools', ATUM_TEXT_DOMAIN );
												break;
										}

										?>
										<h2><?php echo esc_attr( $header_settings_title ) ?></h2>
										<?php
	
										submit_button( __('Save Settings', ATUM_TEXT_DOMAIN) );

										?>
									</div>
								<?php endif; ?>
								<?php if ( $section['title'] ): ?>
									<div class="section-title">
										<h2><?php echo $section['title'] ?></h2>
									</div>
								<?php endif;

								if ( $section['callback'] ):
									call_user_func( $section['callback'], $section );
								endif;

								if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[$page] ) || ! isset( $wp_settings_fields[$page][$section['id']] ) ):
									continue;
								endif; ?>

								<div class="section-fields">
									<table class="form-table">
										<?php do_settings_fields( $page, $section['id'] ); ?>
									</table>

									<?php

									submit_button( __('Save Settings', ATUM_TEXT_DOMAIN) );

									?>

								</div>

							</div>

						<?php endforeach;

					endforeach;

					?>

					<input type="hidden" id="atum_settings_section" name="<?php echo Settings::OPTION_NAME ?>[settings_section]" value="<?php echo $active ?>">

					<?php
					// Add a hidden field to restore WooCommerce manage_stock individual settings
					if ( $active == 'stock_central' ) : ?>
						<input type="hidden" id="atum_restore_option_stock" name="<?php echo Settings::OPTION_NAME ?>[restore_option_stock]" value="no">
					<?php endif;

					?>

				</div>
			</form>
		</div>

	</div>
</div>
