<?php
/**
 * View for the PO data meta box
 *
 * @since 1.2.9
 *
 * Imported variables
 *
 * @var \Atum\PurchaseOrders\Models\PurchaseOrder $atum_order
 * @var \WP_Post                                  $atum_order_post
 * @var array                                     $labels
 * @var \WP_Post                                  $supplier
 */

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;

$po_status = $atum_order->get_status();

?>
<style type="text/css">#post-body-content, #titlediv { display:none }</style>

<div class="panel-wrap">

	<input name="post_title" type="hidden" value="<?php echo empty( $atum_order->get_title() ) ? __( 'Purchase Order', ATUM_TEXT_DOMAIN ) : esc_attr( $atum_order->get_title() ); ?>" />
	<input name="post_status" type="hidden" value="<?php echo ($po_status) ? ATUM_PREFIX . $po_status : 'atum_pending' ?>" />
	<input type="hidden" id="atum_order_is_editable" value="<?php echo ( $atum_order->is_editable() ) ? 'true' : 'false' ?>">

	<div class="atum-meta-box panel">

		<h2><?php printf( esc_html__( '%1$s #%2$s details', ATUM_TEXT_DOMAIN ), $labels['singular_name'], $atum_order_post->ID ); ?></h2>

		<div class="atum_order_data_column_container">
			<div class="atum_order_data_column">

				<p class="form-field form-field-wide">
					<label for="customer_user"><?php _e( 'Supplier:', ATUM_TEXT_DOMAIN ) ?></label>

					<?php
					$supplier_id = ($supplier) ? $supplier->ID : '';
					echo Helpers::suppliers_dropdown($supplier_id, TRUE);
					?>
					<input type="hidden" class="item-blocker-old-value" value="<?php if ( ! empty($supplier) ) echo $supplier->ID ?>">
				</p>

				<p class="form-field">
					<label for="date"><?php _e( 'PO date:', ATUM_TEXT_DOMAIN ) ?></label>
					<input type="text" class="date-picker" name="date" id="date" maxlength="10" value="<?php echo date_i18n( 'Y-m-d', strtotime( $atum_order_post->post_date ) ); ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/purchase_orders/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="date_hour" id="date_hour" min="0" max="23" step="1" value="<?php echo date_i18n( 'H', strtotime( $atum_order_post->post_date ) ); ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="date_minute" id="date_minute" min="0" max="59" step="1" value="<?php echo date_i18n( 'i', strtotime( $atum_order_post->post_date ) ); ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field form-field-wide">
					<label for="status"><?php _e( 'PO status:', ATUM_TEXT_DOMAIN ) ?></label>
					<?php Helpers::atum_order_status_dropdown('status', $po_status) ?>
				</p>

				<p class="form-field expected-at-location-date">
					<label for="reservation_date"><?php _e( 'Expected at location date:', ATUM_TEXT_DOMAIN ) ?></label>
					<?php $expected_at_location_date = $atum_order->get_expected_at_location_date() ?>
					<input type="text" class="date-picker" name="expected_at_location_date" id="expected_at_location_date" maxlength="10" value="<?php echo ($expected_at_location_date) ? date_i18n( 'Y-m-d', strtotime($expected_at_location_date) ) : '' ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/purchase_orders/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="expected_at_location_date_hour" id="expected_at_location_date_hour" min="0" max="23" step="1" value="<?php echo ($expected_at_location_date) ? date_i18n( 'H', strtotime($expected_at_location_date) ) : '' ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="expected_at_location_date_minute" id="expected_at_location_date_minute" min="0" max="59" step="1" value="<?php echo ($expected_at_location_date) ? date_i18n( 'i', strtotime($expected_at_location_date) ) : '' ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<div class="form-field form-field-wide atum-editor">
					<label for="description"><?php _e( 'PO description:', ATUM_TEXT_DOMAIN ) ?></label>
					<?php
					$editor_settings = array(
						'media_buttons' => FALSE,
						'textarea_rows' => 10,
						'tinymce'       => array( 'toolbar1'=> 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo' )
					);

					wp_editor( $atum_order_post->post_content, 'description', $editor_settings ); ?>
				</div>

				<?php do_action( 'atum/purchase_orders/after_po_details', $atum_order ); ?>

			</div>
		</div>

	</div>

</div>
