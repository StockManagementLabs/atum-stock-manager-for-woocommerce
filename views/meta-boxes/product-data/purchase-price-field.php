<?php
/**
 * View for the Purchase Price field within the WC Product Data meta box
 *
 * @since 1.4.1
 *
 * @var string   $wrapper_class
 * @var string   $field_title
 * @var float    $field_value
 * @var float    $price
 * @var string   $field_name
 * @var string   $field_id
 * @var \WP_Post $variation
 * @var int      $loop
 * @var int      $product_id
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

$invalid_purchase_price = apply_filters( 'atum/meta_boxes/purchase_price/invalid', ( $field_value > $price ), $product_id );
$decimals               = apply_filters( 'atum/meta_boxes/purchase_price/allowed_decimals', '' ); // If blank, it will add the number of decimals specified in WC settings by default.
$purchase_price         = is_numeric( $field_value ) ? wc_format_localized_price( wc_format_decimal( $field_value, $decimals ) ) : '';

?>
<p class="form-field <?php echo esc_attr( $wrapper_class ) ?>">
	<label for="<?php echo esc_attr( $field_id ) ?>"><?php echo esc_html( $field_title ) ?></label>

	<span class="atum-field input-group<?php echo $invalid_purchase_price ? ' invalid' : '' ?>">
		<?php Helpers::atum_field_input_addon() ?>

		<input type="text" class="short wc_input_price<?php if ( $invalid_purchase_price ) echo ' tips' ?>" name="<?php echo esc_attr( $field_name ) ?>"
			id="<?php echo esc_attr( $field_id ) ?>" value="<?php echo esc_attr( $purchase_price ) ?>" placeholder=""
			<?php echo $invalid_purchase_price ? ' data-tip="' . esc_attr__( "The Purchase Price set is greater than the product's active price", ATUM_TEXT_DOMAIN ) . '"' : '' ?>
			<?php echo apply_filters( 'atum/views/meta_boxes/purchase_price_field_extra_atts', '', $variation, $loop, $product_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
	</span>
</p>
