<?php
/**
 * View for the Add Inventory modal's JS template
 *
 * @since 1.4.7
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Inc\Helpers;

?>
<script type="text/template" id="create-supplier-modal">
	<div class="atum-modal-content">

		<form>
			<fieldset>
				<h3><?php esc_html_e( 'Supplier Details', ATUM_TEXT_DOMAIN ); ?></h3>

				<div class="input-group">
					<label for="name"><?php esc_html_e( 'Name', ATUM_TEXT_DOMAIN ); ?></label>

					<span class="name-field">
						<input type="text" name="name" id="supplier-name" required
							placeholder="<?php esc_attr_e( 'Type the supplier name', ATUM_TEXT_DOMAIN ); ?>" value="">
					</span>

				</div>

				<div class="input-group">

					<label for="code"><?php esc_html_e( 'Supplier Code', ATUM_TEXT_DOMAIN ); ?></label>

					<span class="code-field">
						<input type="text" name="code" id="supplier-code"
							placeholder="<?php esc_attr_e( 'Type the supplier code', ATUM_TEXT_DOMAIN ); ?>" value="">
					</span>
				</div>

				<div class="input-group">

					<label for="tax_number"><?php esc_html_e( 'Tax/VAT Number', ATUM_TEXT_DOMAIN ); ?></label>

					<span class="tax_number-field">
						<input type="text" name="tax_number" id="supplier-tax_number"
							placeholder="<?php esc_attr_e( 'Type the Tax/VAT Number', ATUM_TEXT_DOMAIN ); ?>" value="">
					</span>
				</div>

				<div class="input-group">

					<label for="phone"><?php esc_html_e( 'Phone Number', ATUM_TEXT_DOMAIN ); ?></label>

					<span class="phone-field">
						<input type="text" name="phone" id="supplier-phone"
							placeholder="<?php esc_attr_e( 'Type the Phone Number', ATUM_TEXT_DOMAIN ); ?>" value="">
					</span>
				</div>

				<div class="input-group">

					<label for="general_email"><?php esc_html_e( 'General Email Address', ATUM_TEXT_DOMAIN ); ?></label>

					<span class="general_email-field">
						<input type="text" name="general_email" id="supplier-general_email"
							placeholder="<?php esc_attr_e( 'Type the Email Address', ATUM_TEXT_DOMAIN ); ?>" value="">
					</span>
				</div>

				<div class="input-group">

					<label for="tax_rate"><?php esc_html_e( 'Tax Rate', ATUM_TEXT_DOMAIN ); ?></label>

					<span class="tax_rate-field">
						<input input type="number" step="1" min="0" name="tax_rate" id="supplier-tax_rate"
							placeholder="<?php esc_attr_e( 'Type the Tax Rate', ATUM_TEXT_DOMAIN ); ?>" value="">
					</span>
				</div>

				<div class="input-group">

					<label for="discount"><?php esc_html_e( 'Discount', ATUM_TEXT_DOMAIN ); ?></label>

					<span class="discount-field">
						<input input type="number" step="1" min="0" name="discount" id="supplier-discount"
							placeholder="<?php esc_attr_e( 'Type the Discount', ATUM_TEXT_DOMAIN ); ?>" value="">
					</span>
				</div>

			</fieldset>

		</form>

	</div>
</script>
