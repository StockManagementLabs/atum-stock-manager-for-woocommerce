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

$decimals           = \Atum\Inc\Globals::get_prices_decimals();
$decimal_separator  = wc_get_price_decimal_separator();
$thousand_separator = wc_get_price_thousand_separator();

$invalid_purchase_price = apply_filters( 'atum/meta_boxes/purchase_price/invalid', ( $field_value > $price ), $product_id );
$purchase_price         = is_numeric( $field_value ) ? number_format( $field_value, $decimals, $decimal_separator, $thousand_separator ) : '';

?>
<p class="form-field <?php echo esc_attr( $wrapper_class ) ?>">
	<label for="<?php echo esc_attr( $field_id ) ?>"><?php echo esc_html( $field_title ) ?></label>

	<span class="atum-field input-group<?php if ($invalid_purchase_price) echo ' invalid' ?>">
		<?php Helpers::atum_field_input_addon() ?>

		<input type="text" class="short wc_input_price<?php if ( $invalid_purchase_price ) echo ' tips' ?>" name="<?php echo esc_attr( $field_name ) ?>"
			id="<?php echo esc_attr( $field_id ) ?>" value="<?php echo esc_attr( $purchase_price ) ?>" placeholder=""
			<?php if ( $invalid_purchase_price ) echo ' data-tip="' . esc_attr__( "The Purchase Price set is greater than the product's active price", ATUM_TEXT_DOMAIN ) . '"' ?>
			<?php echo apply_filters( 'atum/views/meta_boxes/purchase_price_field_extra_atts', '', $variation, $loop, $product_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
	</span>
</p>
