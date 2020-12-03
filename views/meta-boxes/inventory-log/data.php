<?php
/**
 * View for the Inventory Logs data meta box
 *
 * @since 1.2.4
 *
 * Imported variables
 *
 * @var \Atum\InventoryLogs\Models\Log $atum_order
 * @var \WP_Post                       $atum_order_post
 * @var \WC_Order                      $wc_order
 * @var array                          $labels
 */

defined( 'ABSPATH' ) || die;

use Atum\InventoryLogs\InventoryLogs;

$log_status = $atum_order->get_status();

?>
<style type="text/css">#post-body-content, #titlediv { display:none }</style>

<div class="panel-wrap">

	<input name="post_title" type="hidden" value="<?php echo esc_attr( empty( $atum_order->get_title() ) ? __( 'Inventory Log', ATUM_TEXT_DOMAIN ) : $atum_order->get_title() ); ?>" />
	<input name="post_status" type="hidden" value="<?php echo esc_attr( $log_status ?: ATUM_PREFIX . 'pending' ) ?>" />
	<input type="hidden" id="atum_order_is_editable" value="<?php echo ( $atum_order->is_editable() ? 'true' : 'false' ) ?>">

	<div class="atum-meta-box panel">

		<h2>
			<?php
			/* translators: first one is the inventory log name and second is the ID */
			printf( esc_html__( '%1$s #%2$s', ATUM_TEXT_DOMAIN ), esc_attr( $labels['singular_name'] ), esc_attr( $atum_order_post->ID ) );
			?>
		</h2>

		<div class="atum_order_data_column_container">
			<div class="atum_order_data_column">

				<p class="form-field form-field-wide">
					<label for="atum_order_type"><?php esc_html_e( 'Log type', ATUM_TEXT_DOMAIN ) ?></label>

					<select id="atum_order_type" name="atum_order_type" class="wc-enhanced-select atum-enhanced-select">
						<?php
						$log_types           = $atum_order::get_log_types();
						$atum_order_log_type = $atum_order->type;
						foreach ( $log_types as $type => $type_name ) : ?>
							<option value="<?php echo esc_attr( $type ) ?>"<?php selected( $type, $atum_order_log_type ) ?>><?php echo esc_html( $type_name ) ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="form-field">
					<label for="date"><?php esc_html_e( 'Log date', ATUM_TEXT_DOMAIN ) ?></label>
					<input type="text" class="atum-datepicker" name="date" id="date" maxlength="10" value="<?php echo esc_attr( date_i18n( 'Y-m-d', strtotime( $atum_order_post->post_date ) ) ) ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/inventory_logs/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="date_hour" id="date_hour" min="0" max="23" step="1" value="<?php echo esc_attr( date_i18n( 'H', strtotime( $atum_order_post->post_date ) ) ) ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="date_minute" id="date_minute" min="0" max="59" step="1" value="<?php echo esc_attr( date_i18n( 'i', strtotime( $atum_order_post->post_date ) ) ) ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field reservation-date<?php if ( 'reserved-stock' !== $atum_order_log_type ) echo ' hidden' ?>" data-dependency="atum_order_type:reserved-stock">
					<label for="reservation_date"><?php esc_html_e( 'Reservation date:', ATUM_TEXT_DOMAIN ) ?></label>
					<?php $reservation_date = $atum_order->reservation_date ?>
					<input type="text" class="atum-datepicker" name="reservation_date" id="reservation_date" maxlength="10" value="<?php echo esc_attr( $reservation_date ? date_i18n( 'Y-m-d', strtotime( $reservation_date ) ) : '' ) ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/inventory_logs/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="reservation_date_hour" id="reservation_date_hour" min="0" max="23" step="1" value="<?php echo esc_attr( $reservation_date ? date_i18n( 'H', strtotime( $reservation_date ) ) : '' ) ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="reservation_date_minute" id="reservation_date_minute" min="0" max="59" step="1" value="<?php echo esc_attr( $reservation_date ? date_i18n( 'i', strtotime( $reservation_date ) ) : '' ) ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field return-date<?php if ( 'customer-returns' !== $atum_order_log_type ) echo ' hidden' ?>" data-dependency="atum_order_type:customer-returns">
					<label for="return_date"><?php esc_html_e( 'Return date', ATUM_TEXT_DOMAIN ) ?></label>
					<?php $return_date = $atum_order->return_date ?>
					<input type="text" class="atum-datepicker" name="return_date" id="return_date" maxlength="10" value="<?php echo esc_attr( $return_date ? date_i18n( 'Y-m-d', strtotime( $return_date ) ) : '' ) ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/inventory_logs/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="return_date_hour" id="return_date_hour" min="0" max="23" step="1" value="<?php echo esc_attr( $return_date ? date_i18n( 'H', strtotime( $return_date ) ) : '' ) ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="return_date_minute" id="return_date_minute" min="0" max="59" step="1" value="<?php echo esc_attr( $return_date ? date_i18n( 'i', strtotime( $return_date ) ) : '' ) ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field damage-date<?php if ( 'warehouse-damage' !== $atum_order_log_type ) echo ' hidden' ?>" data-dependency="atum_order_type:warehouse-damage">
					<label for="damage_date"><?php esc_html_e( 'Date of damage', ATUM_TEXT_DOMAIN ) ?></label>
					<?php $damage_date = $atum_order->damage_date ?>
					<input type="text" class="atum-datepicker" name="damage_date" id="damage_date" maxlength="10" value="<?php echo esc_attr( $damage_date ? date_i18n( 'Y-m-d', strtotime( $damage_date ) ) : '' ) ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/inventory_logs/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="damage_date_hour" id="damage_date_hour" min="0" max="23" step="1" value="<?php echo esc_attr( $damage_date ? date_i18n( 'H', strtotime( $damage_date ) ) : '' ) ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="damage_date_minute" id="damage_date_minute" min="0" max="59" step="1" value="<?php echo esc_attr( $damage_date ? date_i18n( 'i', strtotime( $damage_date ) ) : '' ) ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field shipping-company<?php if ( 'lost-in-post' !== $atum_order_log_type ) echo ' hidden' ?>" data-dependency="atum_order_type:lost-in-post">
					<label for="shipping_company"><?php esc_html_e( 'Shipping company', ATUM_TEXT_DOMAIN ) ?></label>
					<input type="text" name="shipping_company" id="shipping_company" value="<?php echo esc_attr( $atum_order->shipping_company ) ?>" />
				</p>

				<p class="form-field form-field-wide custom-name<?php if ( 'other' !== $atum_order_log_type ) echo ' hidden' ?>" data-dependency="atum_order_type:other">
					<label for="custom_name"><?php esc_html_e( 'Custom log name', ATUM_TEXT_DOMAIN ) ?></label>
					<input type="text" name="custom_name" id="custom_name" maxlength="50" value="<?php echo esc_attr( $atum_order->custom_name ) ?>" />
				</p>

				<p class="form-field form-field-wide">
					<label for="log_status"><?php esc_html_e( 'Log status', ATUM_TEXT_DOMAIN ) ?></label>
					<?php InventoryLogs::atum_order_status_dropdown( 'log_status', 'status', $log_status ); ?>
				</p>

				<p class="form-field form-field-wide">
					<label for="customer_user"><?php esc_html_e( 'Order', ATUM_TEXT_DOMAIN ) ?></label>

					<select class="wc-product-search atum-enhanced-select" id="wc_order" name="wc_order" data-allow_clear="true" data-action="atum_json_search_orders"
							data-placeholder="<?php esc_attr_e( 'Search by Order ID&hellip;', ATUM_TEXT_DOMAIN ); ?>" data-multiple="false"
							data-selected="" data-minimum_input_length="1"
							>
						<?php if ( ! empty( $wc_order ) ) : ?>
						<option value="<?php echo esc_attr( $wc_order->get_id() ); ?>" selected="selected"><?php echo esc_html__( 'Order #', ATUM_TEXT_DOMAIN ) . esc_attr( $wc_order->get_id() ) ?></option>
						<?php endif; ?>
					</select>
				</p>

				<div class="form-field form-field-wide atum-editor">
					<label for="description"><?php esc_html_e( 'Log description', ATUM_TEXT_DOMAIN ) ?></label>
					<?php
					$editor_settings = array(
						'media_buttons' => FALSE,
						'textarea_rows' => 10,
						'tinymce'       => array( 'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo' ),
					);

					wp_editor( $atum_order_post->post_content, 'description', $editor_settings ); ?>
				</div>

				<?php do_action( 'atum/inventory_logs/after_log_details', $atum_order ); ?>
			</div>

		</div>

	</div>

</div>
