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
		
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $tab => $atts ):

				if ($tab == $active) {
					$active_sections = $atts['sections'];
				}
				?>
				<a href="?page=atum-settings&tab=<?php echo $tab ?>" class="nav-tab<?php if ($tab == $active) echo ' nav-tab-active' ?>"><?php echo $atts['tab_name'] ?></a>
			<?php endforeach; ?>
		</h2>
	
		<form id="atum-settings" method="post" action="options.php">

			<?php
			foreach (array_keys($active_sections) as $active_section):
				// This prints out all hidden setting fields
				settings_fields( ATUM_PREFIX . "setting_$active_section" );

				do_settings_sections( ATUM_PREFIX . "setting_$active_section" );
			endforeach;
			?>
			
			<input type="hidden" id="atum_settings_section" name="<?php echo Settings::OPTION_NAME ?>[settings_section]" value="<?php echo $active ?>">
			
			<?php
			// Add a hidden field to restore WooCommerce manage_stock individual settings
			if ( $active == 'stock_central' ) : ?>
				<input type="hidden" id="atum_restore_option_stock" name="<?php echo Settings::OPTION_NAME ?>[restore_option_stock]" value="no">
			<?php endif;
			
			submit_button();
			?>
		</form>
	</div>
</div>
