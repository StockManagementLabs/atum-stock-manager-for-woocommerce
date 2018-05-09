<?php
/**
 * View for the ATUM tab panel within the WC Product Data meta box in variations
 *
 * @since 1.4.5
 */

defined( 'ABSPATH' ) or die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;
?>
<div class="atum-data-panel">
	<h3 class="atum-section-title"><?php _e('ATUM Inventory', ATUM_TEXT_DOMAIN) ?></h3>

	<div class="options_group">
		<?php
		woocommerce_wp_checkbox( array(
			'id'          => Globals::ATUM_CONTROL_STOCK_KEY . '_' . $loop,
			'name'        => "variation_atum_tab[" . Globals::ATUM_CONTROL_STOCK_KEY . "][$loop]",
			'value'       => Helpers::get_atum_control_status( $variation->ID ),
			'class'       => 'js-switch',
			'label'       => __( 'ATUM Control Switch', ATUM_TEXT_DOMAIN ),
			'description' => __( "Turn the switch ON or OFF to allow the ATUM plugin to include this product in its lists, counters and statistics.", ATUM_TEXT_DOMAIN ),
			'desc_tip'    => TRUE
		) );
		?>
	</div>

	<?php
	// Allow other fields to be added to the ATUM panel
	do_action('atum/after_variation_product_data_panel', $loop, $variation_data, $variation); ?>
</div>

