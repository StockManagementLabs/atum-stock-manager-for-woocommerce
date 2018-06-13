<?php
/**
 * Set an individual out of stock threshold on stock managed at product level items
 *
 * @since 1.4.8
 */

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;
use Atum\Inc\Globals;

// TODO 1.4.8 (* duplicate purchase-price-field / supplier-field-sku)


if ( Helpers::get_option( 'out_stock_threshold', 'no' ) == 'yes' ):?>
<div id="_out_stock_threshold_field_div">
<?php
    if ( empty($variation) ): ?>
    <div class="options_group <?php echo implode(' ', $out_stock_threshold_classes) ?>">
    <?php endif; ?>

        <p class="form-field _out_stock_threshold_field <?php if ( ! empty($variation) ) echo ' show_if_variation_manage_stock form-row form-row-first' ?>">
            <label for="<?php echo $out_stock_threshold_field_id ?>">
                <abbr title="<?php _e( "\"Individual Out of stock Threshold", ATUM_TEXT_DOMAIN ) ?>"><?php _e( "Out of stock Threshold", ATUM_TEXT_DOMAIN ) ?></abbr>
            </label>

            <span class="atum-field input-group">
                    <?php Helpers::atum_field_input_addon() ?>

                <input type="number" class="short" style="" step="1"
                       name="<?php echo $out_stock_threshold_field_name ?>"
                       id="<?php echo $out_stock_threshold_field_id ?>"
                       value="<?php echo $out_stock_threshold ?>" placeholder=""
                       data-onload-product-type="<?php echo $product_type ?>">
                <?php echo wc_help_tip( __( "When enabled on one product with Atum managing their stock, this limit will override the general WooComerce 'Out of stock' value.", ATUM_TEXT_DOMAIN ) ); ?>
                </span>
        </p>

<?php if ( empty($variation) ): ?>
    </div>
<?php endif; ?>
</div>
<?php
else:
    // TODO 1.4.8 remove this!
    echo "edit_out_stock_threshold is not set";
endif;
?>
