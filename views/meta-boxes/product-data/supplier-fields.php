<?php
/**
 * View for the Supplier's fields within the WC Product Data meta box
 *
 * @since 1.4.1
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

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

$classes = ' ' . implode( ' ', $supplier_fields_classes );

if ( empty( $variation ) ) : ?>
<div class="options_group<?php echo esc_attr( $classes ) ?>">
<?php endif; ?>

	<p class="form-field _supplier_field<?php if ( ! empty( $variation ) ) echo ' form-row form-row-first ' ?><?php echo esc_attr( $classes ) ?>">
		<label for="<?php echo esc_attr( $supplier_field_id ) ?>"><?php esc_html_e( 'Supplier', ATUM_TEXT_DOMAIN ) ?></label>

		<span class="atum-field input-group">
			<?php Helpers::atum_field_input_addon() ?>

			<select class="wc-product-search atum-enhanced-select" id="<?php echo esc_attr( $supplier_field_id ) ?>" name="<?php echo esc_attr( $supplier_field_name ) ?>" style="width: <?php echo ( empty( $variation ) ) ? 80 : 100 ?>%"
				data-allow_clear="true" data-action="atum_json_search_suppliers" data-placeholder="<?php esc_attr_e( 'Search Supplier by Name or ID&hellip;', ATUM_TEXT_DOMAIN ); ?>"
				data-multiple="false" data-selected="" data-minimum_input_length="1" data-container-css="atum-enhanced-select"
				<?php echo apply_filters( 'atum/views/meta_boxes/supplier_fields/supplier_extra_atts', '', $variation, $loop ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>

				<?php if ( ! empty( $supplier ) ) : ?>
				<option value=""></option>
				<option value="<?php echo esc_attr( $supplier->ID ) ?>" selected="selected"><?php echo esc_html( $supplier->post_title ) ?></option>
				<?php endif; ?>

			</select>

			<?php echo wc_help_tip( esc_attr__( 'Choose a supplier for this product.', ATUM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>
	</p>

	<p class="form-field _supplier_sku_field<?php if ( ! empty( $variation ) ) echo ' form-row form-row-last' ?><?php echo esc_attr( $classes ) ?>">
		<label for="<?php echo esc_attr( $supplier_sku_field_id ) ?>">
			<abbr title="<?php esc_attr_e( "Supplier's Stock Keeping Unit", ATUM_TEXT_DOMAIN ) ?>"><?php esc_html_e( "Supplier's SKU", ATUM_TEXT_DOMAIN ) ?></abbr>
		</label>

		<span class="atum-field input-group">
			<?php Helpers::atum_field_input_addon() ?>

			<input type="text" class="short" style="" name="<?php echo esc_attr( $supplier_sku_field_name ) ?>"
				id="<?php echo esc_attr( $supplier_sku_field_id ) ?>" value="<?php echo esc_attr( $supplier_sku ) ?>"
				<?php echo apply_filters( 'atum/views/meta_boxes/supplier_fields/supplier_sku_extra_atts', '', $variation, $loop ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
			<?php echo wc_help_tip( esc_html__( "Supplier's SKU refers to a Stock-keeping unit coming from the product's supplier, a unique identifier for each distinct product and service that can be purchased.", ATUM_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>
	</p>

<?php if ( empty( $variation ) ) : ?>
</div>
<?php endif;
