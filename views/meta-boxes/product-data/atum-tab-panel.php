<?php
/**
 * View for the ATUM tab panel within the WC Product Data meta box
 *
 * @since 1.4.5
 */

defined( 'ABSPATH' ) or die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers;

/**
 * @var string  $product_status
 * @var array   $checkbox_wrapper_classes
 * @var array   $control_button_classes
 */
?>
<div id="atum_product_data" class="atum-data-panel panel woocommerce_options_panel hidden">
	<div class="options_group">

		<?php
		woocommerce_wp_checkbox( array(
			'id'            => Globals::ATUM_CONTROL_STOCK_KEY,
			'name'          => 'atum_product_tab[' . Globals::ATUM_CONTROL_STOCK_KEY . ']',
			'value'         => $product_status == 'auto-draft' ? 'yes' : Helpers::get_atum_control_status($product_id),
			'class'         => 'js-switch',
			'wrapper_class' => implode(' ', $checkbox_wrapper_classes),
			'label'         => __( 'ATUM Control Switch', ATUM_TEXT_DOMAIN ),
			'description'   => __( 'Turn the switch ON or OFF to allow the ATUM plugin to include this product in its lists, counters and statistics.', ATUM_TEXT_DOMAIN ),
			'desc_tip'      => TRUE
		) );
		?>

		<p class="form-field product-tab-runner <?php echo implode(' ', $control_button_classes) ?>">
			<label for="stock_control_status"><?php _e("Variations' ATUM Control", ATUM_TEXT_DOMAIN ) ?></label>
			<select id="stock_control_status">
				<option value="controlled"><?php _e('Controlled', ATUM_TEXT_DOMAIN) ?></option>
				<option value="uncontrolled"><?php _e('Uncontrolled', ATUM_TEXT_DOMAIN) ?></option>
			</select>
			&nbsp;
			<button type="button" class="run-script button button-primary" data-action="atum_set_variations_control_status" data-confirm="<?php esc_attr_e( 'This will change the ATUM Control Switch for all the variations within this product to %s', ATUM_TEXT_DOMAIN ) ?>">
				<?php _e('Change Now!', ATUM_TEXT_DOMAIN) ?>
			</button>

			<?php echo wc_help_tip( __('Changes the ATUM Control switch for all the variations to the status set at once.', ATUM_TEXT_DOMAIN) ); ?>
		</p>

	</div>

	<?php
	// Allow other fields to be added to the ATUM panel
	do_action('atum/after_product_data_panel'); ?>
</div>

