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

defined( 'ABSPATH' ) or die;

$log_status = $atum_order->get_status();

?>
<style type="text/css">#post-body-content, #titlediv { display:none }</style>

<div class="panel-wrap">

	<input name="post_title" type="hidden" value="<?php echo empty( $atum_order->get_title() ) ? __( 'Inventory Log', ATUM_TEXT_DOMAIN ) : esc_attr( $atum_order->get_title() ); ?>" />
	<input name="post_status" type="hidden" value="<?php echo ($log_status) ? ATUM_PREFIX . $log_status : 'atum_pending' ?>" />
	<input type="hidden" id="atum_order_is_editable" value="<?php echo ( $atum_order->is_editable() ) ? 'true' : 'false' ?>">

	<div class="atum-meta-box panel">

		<h2><?php printf( esc_html__( '%1$s #%2$s details', ATUM_TEXT_DOMAIN ), $labels['singular_name'], $atum_order_post->ID ); ?></h2>

		<div class="atum_order_data_column_container">
			<div class="atum_order_data_column">

				<p class="form-field form-field-wide">
					<label for="atum_order_type"><?php _e( 'Log type:', ATUM_TEXT_DOMAIN ) ?></label>

					<select id="atum_order_type" name="atum_order_type" class="wc-enhanced-select">
						<?php
						$types           = $atum_order::get_types();
						$atum_order_type = $atum_order->get_type();
						foreach ( $types as $type => $type_name ) : ?>
							<option value="<?php echo esc_attr( $type ) ?>"<?php selected( $type, $atum_order_type ) ?>><?php echo esc_html( $type_name ) ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="form-field">
					<label for="date"><?php _e( 'Log date:', ATUM_TEXT_DOMAIN ) ?></label>
					<input type="text" class="date-picker" name="date" id="date" maxlength="10" value="<?php echo date_i18n( 'Y-m-d', strtotime( $atum_order_post->post_date ) ); ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/inventory_logs/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="date_hour" id="date_hour" min="0" max="23" step="1" value="<?php echo date_i18n( 'H', strtotime( $atum_order_post->post_date ) ); ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="date_minute" id="date_minute" min="0" max="59" step="1" value="<?php echo date_i18n( 'i', strtotime( $atum_order_post->post_date ) ); ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field reservation-date<?php if ( $atum_order_type != 'reserved-stock') echo ' hidden' ?>" data-dependency="atum_order_type:reserved-stock">
					<label for="reservation_date"><?php _e( 'Reservation date:', ATUM_TEXT_DOMAIN ) ?></label>
					<?php $reservation_date = $atum_order->get_reservation_date() ?>
					<input type="text" class="date-picker" name="log_reservation_date" id="log_reservation_date" maxlength="10" value="<?php echo ($reservation_date) ? date_i18n( 'Y-m-d', strtotime($reservation_date) ) : '' ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/inventory_logs/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="reservation_date_hour" id="reservation_date_hour" min="0" max="23" step="1" value="<?php echo ($reservation_date) ? date_i18n( 'H', strtotime($reservation_date) ) : '' ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="reservation_date_minute" id="reservation_date_minute" min="0" max="59" step="1" value="<?php echo ($reservation_date) ? date_i18n( 'i', strtotime($reservation_date) ) : '' ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field return-date<?php if ( $atum_order_type != 'customer-returns') echo ' hidden' ?>" data-dependency="atum_order_type:customer-returns">
					<label for="return_date"><?php _e( 'Return date:', ATUM_TEXT_DOMAIN ) ?></label>
					<?php $return_date = $atum_order->get_return_date() ?>
					<input type="text" class="date-picker" name="return_date" id="return_date" maxlength="10" value="<?php echo ($return_date) ? date_i18n( 'Y-m-d', strtotime($return_date) ) : '' ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/inventory_logs/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="return_date_hour" id="return_date_hour" min="0" max="23" step="1" value="<?php echo ($return_date) ? date_i18n( 'H', strtotime($return_date) ) : '' ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="return_date_minute" id="return_date_minute" min="0" max="59" step="1" value="<?php echo ($return_date) ? date_i18n( 'i', strtotime($return_date) ) : '' ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field damage-date<?php if ( $atum_order_type != 'warehouse-damage') echo ' hidden' ?>" data-dependency="atum_order_type:warehouse-damage">
					<label for="damage_date"><?php _e( 'Date of damage:', ATUM_TEXT_DOMAIN ) ?></label>
					<?php $damage_date = $atum_order->get_damage_date() ?>
					<input type="text" class="date-picker" name="damage_date" id="damage_date" maxlength="10" value="<?php echo ($damage_date) ? date_i18n( 'Y-m-d', strtotime($damage_date) ) : '' ?>" pattern="<?php echo esc_attr( apply_filters( 'atum/inventory_logs/date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@
					<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', ATUM_TEXT_DOMAIN ) ?>" name="damage_date_hour" id="damage_date_hour" min="0" max="23" step="1" value="<?php echo ($damage_date) ? date_i18n( 'H', strtotime($damage_date) ) : '' ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
					<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', ATUM_TEXT_DOMAIN ) ?>" name="damage_date_minute" id="damage_date_minute" min="0" max="59" step="1" value="<?php echo ($damage_date) ? date_i18n( 'i', strtotime($damage_date) ) : '' ?>" pattern="[0-5]{1}[0-9]{1}" />
				</p>

				<p class="form-field shipping-company<?php if ( $atum_order_type != 'lost-in-post') echo ' hidden' ?>" data-dependency="atum_order_type:lost-in-post">
					<label for="shipping_company"><?php _e( 'Shipping company:', ATUM_TEXT_DOMAIN ) ?></label>
					<input type="text" name="shipping_company" id="shipping_company" value="<?php echo $atum_order->get_shipping_company() ?>" />
				</p>

				<p class="form-field form-field-wide custom-name<?php if ( $atum_order_type != 'other') echo ' hidden' ?>" data-dependency="atum_order_type:other">
					<label for="custom_name"><?php _e( 'Custom log name:', ATUM_TEXT_DOMAIN ) ?></label>
					<input type="text" name="custom_name" id="custom_name" maxlength="50" value="<?php echo $atum_order->get_custom_name() ?>" />
				</p>

				<p class="form-field form-field-wide">
					<label for="status"><?php _e( 'Log status:', ATUM_TEXT_DOMAIN ) ?></label>
					<?php \Atum\Inc\Helpers::atum_order_status_dropdown('status', $log_status) ?>
				</p>

				<p class="form-field form-field-wide">
					<label for="customer_user"><?php _e( 'Order:', ATUM_TEXT_DOMAIN ) ?></label>

					<select class="wc-product-search" id="wc_order" name="wc_order" data-allow_clear="true" data-action="atum_json_search_orders"
							data-placeholder="<?php esc_attr_e( 'Search by Order ID&hellip;', ATUM_TEXT_DOMAIN ); ?>" data-multiple="false"
							data-selected="" data-minimum_input_length="1">
						<?php if ( ! empty($wc_order) ): ?>
						<option value="<?php echo esc_attr( $wc_order->get_id() ); ?>" selected="selected"><?php echo __('Order #', ATUM_TEXT_DOMAIN) . $wc_order->get_id() ?></option>
						<?php endif; ?>
					</select>
				</p>

				<div class="form-field form-field-wide atum-editor">
					<label for="description"><?php _e( 'Log description:', ATUM_TEXT_DOMAIN ) ?></label>
					<?php
					$editor_settings = array(
						'media_buttons' => FALSE,
						'textarea_rows' => 10,
						'tinymce'       => array( 'toolbar1'=> 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo' )
					);

					wp_editor( $atum_order_post->post_content, 'description', $editor_settings ); ?>
				</div>

				<?php do_action( 'atum/inventory_logs/after_log_details', $atum_order ); ?>
			</div>

			<?php /*if ( isset($billing_fields) ): ?>
			<div class="log_data_column">
				<h3>
					<?php _e( 'Billing details', ATUM_TEXT_DOMAIN ); ?>
					<a href="#" class="edit_address"><?php _e( 'Edit', ATUM_TEXT_DOMAIN ); ?></a>
					<span>
							<a href="#" class="load_customer_billing" style="display:none;"><?php _e( 'Load billing address', ATUM_TEXT_DOMAIN ); ?></a>
						</span>
				</h3>

				<?php // Display values ?>
				<div class="address">
					<?php
					if ( $wc_order->get_formatted_billing_address() ) {
						echo '<p><strong>' . __( 'Address:', ATUM_TEXT_DOMAIN ) . '</strong>' . wp_kses( $wc_order->get_formatted_billing_address(), array( 'br' => array() ) ) . '</p>';
					}
					else {
						echo '<p class="none_set"><strong>' . __( 'Address:', ATUM_TEXT_DOMAIN ) . '</strong> ' . __( 'No billing address set.', ATUM_TEXT_DOMAIN ) . '</p>';
					}

					foreach ( $billing_fields as $key => $field ) {
						if ( isset( $field['show'] ) && false === $field['show'] ) {
							continue;
						}

						$field_name = 'billing_' . $key;

						if ( is_callable( array( $wc_order, 'get_' . $field_name ) ) ) {
							$field_value = $wc_order->{"get_$field_name"}( 'edit' );
						} else {
							$field_value = $wc_order->get_meta( '_' . $field_name );
						}

						echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . make_clickable( esc_html( $field_value ) ) . '</p>';
					}
					?>
				</div>

				<?php // Display form ?>
				<div class="edit_address">

					<?php
					foreach ( $billing_fields as $key => $field ) {
						if ( ! isset( $field['type'] ) ) {
							$field['type'] = 'text';
						}
						if ( ! isset( $field['id'] ) ) {
							$field['id'] = '_billing_' . $key;
						}
						switch ( $field['type'] ) {
							case 'select' :
								woocommerce_wp_select( $field );
								break;
							default :
								woocommerce_wp_text_input( $field );
								break;
						}
					}

					?>
					<p class="form-field form-field-wide">
						<label><?php _e( 'Payment method:', ATUM_TEXT_DOMAIN ); ?></label>

						<select name="_payment_method" id="_payment_method" class="first">
							<option value=""><?php _e( 'N/A', ATUM_TEXT_DOMAIN ); ?></option>
							<?php
							$found_method 	= false;

							foreach ( $payment_gateways as $gateway ) {
								if ( 'yes' === $gateway->enabled ) {
									echo '<option value="' . esc_attr( $gateway->id ) . '" ' . selected( $payment_method, $gateway->id, false ) . '>' . esc_html( $gateway->get_title() ) . '</option>';
									if ( $payment_method == $gateway->id ) {
										$found_method = true;
									}
								}
							}

							if ( ! $found_method && ! empty( $payment_method ) ) {
								echo '<option value="' . esc_attr( $payment_method ) . '" selected="selected">' . __( 'Other', ATUM_TEXT_DOMAIN ) . '</option>';
							}
							else {
								echo '<option value="other">' . __( 'Other', ATUM_TEXT_DOMAIN ) . '</option>';
							}
							?>
						</select>
					</p>

					<?php woocommerce_wp_text_input( array( 'id' => '_transaction_id', 'label' => __( 'Transaction ID', ATUM_TEXT_DOMAIN ) ) ); ?>

				</div>

				<?php do_action( 'atum/inventory_logs/after_billing_address', $wc_order ); ?>
			</div>
			<?php endif;*/ ?>

			<?php /* if ( isset($shipping_fields) ): ?>
			<div class="order_data_column">

				<h3>
					<?php _e( 'Shipping details', ATUM_TEXT_DOMAIN ); ?>
					<a href="#" class="edit_address"><?php _e( 'Edit', ATUM_TEXT_DOMAIN ); ?></a>
					<span>
							<a href="#" class="load_customer_shipping" style="display:none;"><?php _e( 'Load shipping address', ATUM_TEXT_DOMAIN ); ?></a>
							<a href="#" class="billing-same-as-shipping" style="display:none;"><?php _e( 'Copy billing address', ATUM_TEXT_DOMAIN ); ?></a>
						</span>
				</h3>


				<?php // Display values ?>
				<div class="address">

					<?php
					if ( $wc_order->get_formatted_shipping_address() ) {
						echo '<p><strong>' . __( 'Address:', ATUM_TEXT_DOMAIN ) . '</strong>' . wp_kses( $wc_order->get_formatted_shipping_address(), array( 'br' => array() ) ) . '</p>';
					}
					else {
						echo '<p class="none_set"><strong>' . __( 'Address:', ATUM_TEXT_DOMAIN ) . '</strong> ' . __( 'No shipping address set.', ATUM_TEXT_DOMAIN ) . '</p>';
					}

					if ( ! empty( self::$shipping_fields ) ) {

						foreach ( self::$shipping_fields as $key => $field ) {
							if ( isset( $field['show'] ) && false === $field['show'] ) {
								continue;
							}

							$field_name = 'shipping_' . $key;

							if ( is_callable( array( $wc_order, 'get_' . $field_name ) ) ) {
								$field_value = $wc_order->{"get_$field_name"}( 'edit' );
							}
							else {
								$field_value = $wc_order->get_meta( '_' . $field_name );
							}

							echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . make_clickable( esc_html( $field_value ) ) . '</p>';
						}

					}

					if ( apply_filters( 'atum/inventory_logs/enable_log_notes_field', 'yes' == get_option( 'woocommerce_enable_order_comments', 'yes' ) ) && $log->post_excerpt ) {
						echo '<p><strong>' . __( 'Customer provided note:', ATUM_TEXT_DOMAIN ) . '</strong> ' . nl2br( esc_html( $log->post_excerpt ) ) . '</p>';
					}
					?>

				</div>

				<?php // Display form ?>
				<div class="edit_address">

					<?php
					if ( ! empty( self::$shipping_fields ) ) {
						foreach ( self::$shipping_fields as $key => $field ) {
							if ( ! isset( $field['type'] ) ) {
								$field['type'] = 'text';
							}
							if ( ! isset( $field['id'] ) ) {
								$field['id'] = '_shipping_' . $key;
							}

							switch ( $field['type'] ) {
								case 'select' :
									woocommerce_wp_select( $field );
									break;
								default :
									woocommerce_wp_text_input( $field );
									break;
							}
						}
					}

					if ( apply_filters( 'atum/inventory_logs/enable_log_notes_field', 'yes' == get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) {
						?>
						<p class="form-field form-field-wide"><label for="excerpt"><?php _e( 'Customer provided note', ATUM_TEXT_DOMAIN ) ?>:</label>
							<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt" placeholder="<?php esc_attr_e( "Customer's notes about the order", ATUM_TEXT_DOMAIN ); ?>"><?php echo wp_kses_post( $log->post_excerpt ); ?></textarea></p>
						<?php
					}
					?>
				</div>

				<?php do_action( 'atum/inventory_logs/after_shipping_address', $wc_order ); ?>
			</div>
			<?php endif;*/ ?>



		</div>

		<div class="clear"></div>
	</div>

</div>
