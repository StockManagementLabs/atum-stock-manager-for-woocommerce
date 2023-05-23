<?php
/**
 * ATUM file attachment meta box view
 *
 * @var array $product_attachments
 * @var array $email_notifications
 */

?>
<ul class="atum-attachments-list">

	<?php foreach ( $product_attachments as $attachment ) : ?>

		<li data-id="<?php echo esc_attr( $attachment->id ) ?>">
			<label><?php esc_html_e( 'Attach to email:', ATUM_TEXT_DOMAIN ); ?></label>
			<select class="attach-to-email">
				<?php foreach ( $email_notifications as $email_key => $email_title ) : ?>
					<option value="<?php echo esc_attr( $email_key ) ?>"<?php selected( $email_key, $attachment->email ) ?>>
						<?php echo esc_html( $email_title ) ?>
					</option>
				<?php endforeach; ?>
			</select>

			<a href="<?php echo esc_url( wp_get_attachment_url( $attachment->id ) ) ?>" target="_blank">

				<?php
				$is_image = FALSE;
				$check    = wp_check_filetype( wp_get_original_image_path( $attachment->id ) );

				if ( ! empty( $check['ext'] ) ) :
					$is_image = in_array( $check['ext'], [ 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'webp' ] );
				endif;

				if ( $is_image ) : ?>
					<?php echo wp_get_attachment_image( $attachment->id, 'medium' ) ?>
				<?php else : ?>
					<div class="atum-attachment-icon"><i class="atum-icon atmi-file-empty"></i></div>
				<?php endif; ?>
			</a>
			<i class="delete-attachment dashicons dashicons-dismiss atum-tooltip" title="<?php esc_attr_e( 'Delete attachment', ATUM_TEXT_DOMAIN ); ?>"></i>
		</li>

	<?php endforeach; ?>

</ul>

<a href="#" class="atum-file-uploader"><?php esc_html_e( 'Add product attachments', ATUM_TEXT_DOMAIN ); ?></a>
<input type="hidden" name="atum-attachments" id="atum-attachments" value='<?php echo wp_json_encode( $product_attachments ) ?>'>
