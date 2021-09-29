<?php
/**
 * View for the PO data meta box
 *
 * @since 1.2.9
 *
 * @var \Atum\PurchaseOrders\Models\PurchaseOrder $atum_order
 * @var \WP_Post                                  $atum_order_post
 * @var array                                     $labels
 * @var \Atum\Suppliers\Supplier                  $supplier
 * @var bool                                      $has_multiple_suppliers
 */

defined( 'ABSPATH' ) || die;

use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Inc\Helpers;

$po_status = $atum_order->get_status();

?>
<style type="text/css">#post-body-content, #titlediv { display:none }</style>

<div id="po-data-meta-box" class="panel-wrap">

	<input name="post_title" type="hidden" value="<?php echo ( empty( $atum_order->get_title() ) ? esc_attr__( 'Purchase Order', ATUM_TEXT_DOMAIN ) : esc_attr( $atum_order->get_title() ) ) ?>">
	<input name="post_status" type="hidden" value="<?php echo esc_attr( $po_status ?: ATUM_PREFIX . 'pending' ) ?>">
	<input type="hidden" id="atum_order_is_editable" value="<?php echo ( $atum_order->is_editable() ? 'true' : 'false' ) ?>">
	<input type="hidden" id="atum_order_has_multiple_suppliers" value="<?php echo ( $has_multiple_suppliers ? 'true' : 'false' ) ?>">

	<?php do_action( 'atum/purchase_orders/before_po_data_panel', $atum_order_post, $labels ) ?>

	<div class="atum-meta-box panel">

		<h2>
			<?php
			/* translators: first one is the purchase order name and second is the ID */
			printf( esc_html__( '%1$s #%2$s', ATUM_TEXT_DOMAIN ), $labels['singular_name'], $atum_order_post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</h2>

		<div class="atum_order_data_column_container">
			<div class="atum_order_data_column">

				<p class="form-field form-field-wide">
					<span class="form-switch">
						<input type="checkbox" class="form-check-input" name="multiple_suppliers" id="multiple_suppliers" value="yes"<?php checked( $has_multiple_suppliers, TRUE ) ?>>
						<input type="hidden" class="item-blocker-old-value" value="<?php echo ( $has_multiple_suppliers ? 'yes' : 'no' ) ?>">
						<label for="multiple_suppliers" class="form-check-label"><?php esc_html_e( 'Multiple Suppliers', ATUM_TEXT_DOMAIN ) ?></label>
					</span>
				</p>

				<p class="form-field form-field-wide"<?php if ( $has_multiple_suppliers ) echo ' style="display:none"' ?>>
					<label for="customer_user"><?php esc_html_e( 'Supplier', ATUM_TEXT_DOMAIN ) ?></label>

					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo Helpers::suppliers_dropdown( [
						'selected' => $supplier ? $supplier->id : '',
						'enhanced' => TRUE,
					] ); ?>
					<input type="hidden" class="item-blocker-old-value" value="<?php echo esc_attr( $supplier ? $supplier->id : '' ) ?>">
				</p>

				<p class="form-field">
					<label for="date"><?php esc_html_e( 'PO date', ATUM_TEXT_DOMAIN ) ?></label>
					<input type="text" class="atum-datepicker" name="date" id="date" maxlength="10" value="<?php echo esc_attr( date_i18n( 'Y-m-d', strtotime( $atum_order_post->post_date ) ) ); ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/purchase_orders/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" autocomplete="off">@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="date_hour" id="date_hour" min="0" max="23" step="1" value="<?php echo esc_attr( date_i18n( 'H', strtotime( $atum_order_post->post_date ) ) ); ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})">:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="date_minute" id="date_minute" min="0" max="59" step="1" value="<?php echo esc_attr( date_i18n( 'i', strtotime( $atum_order_post->post_date ) ) ); ?>" pattern="[0-5]{1}[0-9]{1}">
				</p>

				<p class="form-field form-field-wide">
					<label for="po_status"><?php esc_html_e( 'PO status', ATUM_TEXT_DOMAIN ) ?></label>
					<?php PurchaseOrders::atum_order_status_dropdown( 'po_status', 'status', $po_status ) ?>
				</p>

				<p class="form-field date-expected">
					<label for="date_expected"><?php esc_html_e( 'Expected at location date', ATUM_TEXT_DOMAIN ) ?></label>
					<?php $date_expected = $atum_order->date_expected ?>
					<input type="text" class="atum-datepicker" name="date_expected" id="date_expected" maxlength="10" value="<?php echo esc_attr( $date_expected ? date_i18n( 'Y-m-d', strtotime( $date_expected ) ) : '' ) ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/purchase_orders/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" autocomplete="off">@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="date_expected_hour" id="date_expected_hour" min="0" max="23" step="1" value="<?php echo esc_attr( $date_expected ? date_i18n( 'H', strtotime( $date_expected ) ) : '' ) ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})">:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="date_expected_minute" id="date_expected_minute" min="0" max="59" step="1" value="<?php echo esc_attr( $date_expected ? date_i18n( 'i', strtotime( $date_expected ) ) : '' ) ?>" pattern="[0-5]{1}[0-9]{1}">
				</p>

				<div class="form-field form-field-wide atum-editor">
					<label for="description"><?php esc_html_e( 'PO description', ATUM_TEXT_DOMAIN ) ?></label>
					<?php
					$editor_settings = array(
						'media_buttons' => FALSE,
						'textarea_rows' => 10,
						'tinymce'       => array( 'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo' ),
					);

					wp_editor( $atum_order_post->post_content, 'description', $editor_settings ); ?>
				</div>

				<?php do_action( 'atum/purchase_orders/after_po_details', $atum_order ); ?>

			</div>
		</div>

	</div>

</div>
