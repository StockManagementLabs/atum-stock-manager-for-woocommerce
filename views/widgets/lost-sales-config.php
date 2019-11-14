<?php
/**
 * View for the Lost Sales widget config
 *
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || die;
?>

<form class="widget-config">

	<div class="form-field">
		<label for="time_window"><?php esc_html_e( 'Set the default time window', ATUM_TEXT_DOMAIN ) ?></label>

		<select name="time_window" id="time_window">
			<option value="today"><?php esc_html_e( 'Today', ATUM_TEXT_DOMAIN ) ?></option>
			<option value="month"><?php esc_html_e( 'Month', ATUM_TEXT_DOMAIN ) ?></option>
		</select>
	</div>

	<div class="config-controls">
		<input type="submit" value="<?php esc_attr_e( 'Save', ATUM_TEXT_DOMAIN ) ?>" class="btn btn-primary">
		<button type="button" class="cancel-config btn btn-danger"><?php esc_html_e( 'Cancel', ATUM_TEXT_DOMAIN ) ?></button>
	</div>

</form>
