<?php
/**
 * View for the Lost Sales widget config
 *
 * @since 1.4.0
 */
?>

<form class="widget-config">

	<div class="form-field">
		<label for="time_window"><?php _e('Set the default time window') ?></label>

		<select name="time_window" id="time_window">
			<option value="today"><?php _e('Today', ATUM_TEXT_DOMAIN) ?></option>
			<option value="month"><?php _e('Month', ATUM_TEXT_DOMAIN) ?></option>
		</select>
	</div>

	<div class="config-controls">
		<input type="submit" value="<?php esc_attr_e('Save', ATUM_TEXT_DOMAIN ) ?>" class="btn btn-primary btn-pill">
		<button type="button" class="cancel-config btn btn-danger btn-pill"><?php _e('Cancel', ATUM_TEXT_DOMAIN) ?></button>
	</div>

</form>
