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
?>
<div class="wrap">
	<div class="atum-settings-wrapper">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'ATUM Settings', ATUM_TEXT_DOMAIN ) ?></h1>
		<hr class="wp-header-end">
		
		<?php settings_errors(); ?>
		
		<nav class="atum-nav">
			<a class="atum-brand" href="https://www.stockmanagementlabs.com" target="_blank">
				<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/atum-icon.svg" title="<?php esc_attr_e( 'Visit ATUM Website', ATUM_TEXT_DOMAIN ) ?>">
			</a>

			<ul class="atum-nav-list">
				<?php foreach ( $tabs as $tab => $atts ) :

					if ( $tab === $active ) :
						$active_sections = $atts['sections'];
					endif; ?>

					<li class="atum-nav-item<?php if ( isset( $atts['no_submit'] ) && $atts['no_submit'] ) echo ' no-submit' ?>">
						<a href="?page=atum-settings&tab=<?php echo esc_attr( $tab ) ?>" rel="address:/<?php echo esc_attr( $tab ) ?>" data-tab="<?php echo esc_attr( $tab ) ?>" class="atum-nav-link<?php if ($tab === $active) echo ' active' ?>">
							<span class="menu-helper"><?php echo esc_html( $atts['tab_name'] ) ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	
		<form id="atum-settings" method="post" action="options.php" style="display: none">
			<div class="form-settings-wrapper">

				<?php
				global $wp_settings_sections, $wp_settings_fields;

				foreach ( array_keys( $active_sections ) as $active_section ) :

					// This prints out all hidden setting fields.
					settings_fields( ATUM_PREFIX . "setting_$active_section" );

					$page = ATUM_PREFIX . "setting_$active_section";

					if ( ! isset( $wp_settings_sections[ $page ] ) ) :
						continue;
					endif;

					foreach ( (array) $wp_settings_sections[ $page ] as $section ) : ?>

						<div id="<?php echo esc_attr( $section['id'] ) ?>" class="settings-section" data-section="<?php echo esc_attr( str_replace( [ ATUM_PREFIX, 'setting_' ], '', $section['id'] ) ) ?>">

							<?php if ( $section['title'] ) : ?>
								<div class="section-title">
									<h2><?php echo esc_html( $section['title'] ) ?></h2>
								</div>
							<?php endif; ?>

							<?php if ( $section['callback'] ) :
								call_user_func( $section['callback'], $section );
							endif; ?>

							<?php if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) :
								continue;
							endif; ?>

							<div class="section-fields">
								<table class="form-table">
									<?php do_settings_fields( $page, $section['id'] ); ?>
								</table>
							</div>

						</div>

					<?php endforeach;

				endforeach;
				?>

				<input type="hidden" id="atum_settings_section" name="<?php echo esc_attr( Settings::OPTION_NAME ) ?>[settings_section]" value="<?php echo esc_attr( $active ) ?>">

				<?php
				// Add a hidden field to restore WooCommerce manage_stock individual settings.
				if ( 'stock_central' === $active ) : ?>
					<input type="hidden" id="atum_restore_option_stock" name="<?php echo esc_attr( Settings::OPTION_NAME ) ?>[restore_option_stock]" value="no">
				<?php endif;

				submit_button( __( 'Update Settings', ATUM_TEXT_DOMAIN ) );
				?>

			</div>
		</form>
	</div>
</div>
