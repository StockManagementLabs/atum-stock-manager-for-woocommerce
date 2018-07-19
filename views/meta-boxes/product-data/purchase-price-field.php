<?php
/**
 * View for the Purchase Price field within the WC Product Data meta box
 *
 * @since 1.4.1
 */

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;
use Atum\Inc\Globals;

/**
 * Used variables
 *
 * @var string $wrapper_class
 * @var string $field_title
 * @var float  $field_value
 * @var float  $price
 * @var string $field_name
 * @var string $field_id
 */
?>
<p class="form-field <?php echo $wrapper_class ?>">
	<label for="<?php echo Globals::PURCHASE_PRICE_KEY ?>"><?php echo $field_title ?></label>

	<span class="atum-field input-group<?php if ($field_value > $price) echo ' invalid' ?>">
		<?php Helpers::atum_field_input_addon() ?>
		<input type="text" class="short wc_input_price<?php if ($field_value > $price) echo ' tips' ?>" name="<?php echo $field_name ?>"
			id="<?php echo $field_id ?>" value="<?php echo $field_value ?>" placeholder=""
			<?php if ($field_value > $price) echo ' data-tip="' . __( "The Purchase Price set is greater than the product's active price", ATUM_TEXT_DOMAIN ) . '"' ?>>
	</span>
</p>

