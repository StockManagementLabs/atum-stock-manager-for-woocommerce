<?php
/**
 * View for the Settings admin page
 *
 * @since 0.0.1
 */

defined( 'ABSPATH' ) or die;

use Atum\Settings\Settings;

?>
<div class="wrap">
	<div class="atum-settings-wrapper">
		<h1 class="wp-heading-inline"><?php _e('ATUM Settings', ATUM_TEXT_DOMAIN) ?></h1>
		<hr class="wp-header-end">
		
		<?php settings_errors(); ?>
		
		<nav class="atum-nav">
			<a class="atum-brand" href="https://www.stockmanagementlabs.com" target="_blank">
				<img src="<?php echo ATUM_URL ?>assets/images/atum-icon.svg" title="<?php _e('Visit ATUM Website', ATUM_TEXT_DOMAIN) ?>">
			</a>

			<ul class="atum-nav-list">
				<?php foreach ( $tabs as $tab => $atts ):

					if ($tab == $active) {
						$active_sections = $atts['sections'];
					}
					?>
					<li class="atum-nav-item">
						<a href="?page=atum-settings&tab=<?php echo $tab ?>" class="atum-nav-link<?php if ($tab == $active) echo ' active' ?>">
							<span class="menu-helper"><?php echo $atts['tab_name'] ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	
		<form id="atum-settings" method="post" action="options.php">
			<div class="form-settings-wrapper">

				<?php
				global $wp_settings_sections, $wp_settings_fields;

				foreach (array_keys($active_sections) as $active_section):
					// This prints out all hidden setting fields
					settings_fields( ATUM_PREFIX . "setting_$active_section" );

					$page = ATUM_PREFIX . "setting_$active_section";

					if ( ! isset( $wp_settings_sections[$page] ) ):
						continue;
					endif;

					foreach ( (array) $wp_settings_sections[$page] as $section ): ?>

						<div class="settings-section">

							<?php if ( $section['title'] ): ?>
								<div class="section-title">
									<h2><?php echo $section['title'] ?></h2>
								</div>
							<?php endif;

							if ( $section['callback'] ):
								call_user_func( $section['callback'], $section );
							endif;

							if ( ! isset( $wp_settings_fields ) || !isset( $wp_settings_fields[$page] ) || !isset( $wp_settings_fields[$page][$section['id']] ) ):
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

				<input type="hidden" id="atum_settings_section" name="<?php echo Settings::OPTION_NAME ?>[settings_section]" value="<?php echo $active ?>">

				<?php
				// Add a hidden field to restore WooCommerce manage_stock individual settings
				if ( $active == 'stock_central' ) : ?>
					<input type="hidden" id="atum_restore_option_stock" name="<?php echo Settings::OPTION_NAME ?>[restore_option_stock]" value="no">
				<?php endif;

				submit_button( __('Update Settings', ATUM_TEXT_DOMAIN) );
				?>

			</div>
		</form>
	</div>
</div>
