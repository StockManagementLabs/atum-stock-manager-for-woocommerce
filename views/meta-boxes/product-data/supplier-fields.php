<?php
/**
 * View for the Supplier's fields within the WC Product Data meta box
 *
 * @since 1.4.1
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

/**
 * Used variables
 *
 * @var array    $supplier_fields_classes
 * @var string   $supplier_field_id
 * @var string   $supplier_field_name
 * @var string   $supplier_sku_field_id
 * @var string   $supplier_sku_field_name
 * @var string   $supplier_sku
 * @var \WP_Post $variation
 * @var int      $loop
 */

if ( empty( $variation ) ) : ?>
<div class="options_group <?php echo implode( ' ', $supplier_fields_classes ) ?>">
<?php endif; ?>

	<p class="form-field _supplier_field<?php if ( ! empty( $variation ) ) echo ' form-row form-row-first' ?>">
		<label for="<?php echo $supplier_field_id ?>"><?php _e( 'Supplier', ATUM_TEXT_DOMAIN ) ?></label>

		<span class="atum-field input-group">
			<?php Helpers::atum_field_input_addon() ?>

			<select class="wc-product-search" id="<?php echo $supplier_field_id ?>" name="<?php echo $supplier_field_name ?>" style="width: <?php echo ( empty( $variation ) ) ? 80 : 100 ?>%"
				data-allow_clear="true" data-action="atum_json_search_suppliers" data-placeholder="<?php esc_attr_e( 'Search Supplier by Name or ID&hellip;', ATUM_TEXT_DOMAIN ); ?>"
				data-multiple="false" data-selected="" data-minimum_input_length="1"<?php echo apply_filters( 'atum/views/meta_boxes/supplier_fields/supplier', '', $variation, $loop ) ?>>

				<?php if ( ! empty( $supplier ) ) : ?>
				<option value=""></option>
				<option value="<?php echo esc_attr( $supplier->ID ) ?>" selected="selected"><?php echo $supplier->post_title ?></option>
				<?php endif; ?>

			</select>

			<?php echo wc_help_tip( __( 'Choose a supplier for this product.', ATUM_TEXT_DOMAIN ) ); ?>
		</span>
	</p>

	<p class="form-field _supplier_sku_field<?php if ( ! empty( $variation ) ) echo ' form-row form-row-last' ?>">
		<label for="<?php echo $supplier_sku_field_id ?>">
			<abbr title="<?php _e( "Supplier's Stock Keeping Unit", ATUM_TEXT_DOMAIN ) ?>"><?php _e( "Supplier's SKU", ATUM_TEXT_DOMAIN ) ?></abbr>
		</label>

		<span class="atum-field input-group">
			<?php Helpers::atum_field_input_addon() ?>

			<input type="text" class="short" style="" name="<?php echo $supplier_sku_field_name ?>" id="<?php echo $supplier_sku_field_id ?>" value="<?php echo $supplier_sku ?>"<?php echo apply_filters( 'atum/views/meta_boxes/supplier_fields/supplier_sku', '', $variation, $loop ) ?>>
			<?php echo wc_help_tip( __( "Supplier's SKU refers to a Stock-keeping unit coming from the product's supplier, a unique identifier for each distinct product and service that can be purchased.", ATUM_TEXT_DOMAIN ) ); ?>
		</span>
	</p>

<?php if ( empty( $variation ) ) : ?>
</div>
<?php endif;

