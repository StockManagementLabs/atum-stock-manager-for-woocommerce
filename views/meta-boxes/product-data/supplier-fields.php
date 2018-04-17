<?php
/**
 * View for the Supplier's fields within the WC Product Data meta box
 *
 * @since 1.4.1
 */

defined( 'ABSPATH' ) or die;

if ( empty($variation) ): ?>
<div class="options_group <?php echo implode(' ', $supplier_fields_classes) ?>">
<?php endif; ?>

	<p class="form-field _supplier_field<?php if ( ! empty($variation) ) echo ' form-row form-row-first' ?>">
		<label for="_supplier"><?php _e('Supplier', ATUM_TEXT_DOMAIN) ?></label>

		<span class="atum-field input-group">
			<?php \Atum\Inc\Helpers::atum_field_input_addon() ?>

			<select class="wc-product-search" id="_supplier" name="<?php echo $suplier_field_name ?>" style="width: <?php echo ( empty($variation) ) ? 80 : 100 ?>%" data-allow_clear="true"
					data-action="atum_json_search_suppliers" data-placeholder="<?php esc_attr_e( 'Search Supplier by Name or ID&hellip;', ATUM_TEXT_DOMAIN ); ?>"
					data-multiple="false" data-selected="" data-minimum_input_length="1">
							<?php if ( ! empty($supplier) ): ?>
								<option value=""></option>
								<option value="<?php echo esc_attr( $supplier->ID ) ?>" selected="selected"><?php echo $supplier->post_title ?></option>
							<?php endif; ?>
						</select>

			<?php echo wc_help_tip( __( 'Choose a supplier for this product.', ATUM_TEXT_DOMAIN ) ); ?>
		</span>
	</p>

	<p class="form-field _supplier_sku_field<?php if ( ! empty($variation) ) echo ' form-row form-row-last' ?>">
		<label for="_supplier_sku">
			<abbr title="<?php _e( "Supplier's Stock Keeping Unit", ATUM_TEXT_DOMAIN ) ?>"><?php _e( "Supplier's SKU", ATUM_TEXT_DOMAIN ) ?></abbr>
		</label>

		<span class="atum-field input-group">
			<?php \Atum\Inc\Helpers::atum_field_input_addon() ?>

			<input type="text" class="short" style="" name="<?php echo $supplier_sku_field_name ?>" id="_supplier_sku" value="<?php echo $supplier_sku ?>" placeholder="">
			<?php echo wc_help_tip( __( "Supplier's SKU refers to a Stock-keeping unit coming from the product's supplier, a unique identifier for each distinct product and service that can be purchased.", ATUM_TEXT_DOMAIN ) ); ?>
		</span>
	</p>

<?php if ( empty($variation) ): ?>
</div>
<?php endif;

