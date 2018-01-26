<?php
/**
 * View for the ATUM Dashboard Orders widget
 *
 * @since 1.3.9
 */
?>

<div class="stats-data-widget" data-widget="orders-data">

	<div class="data-filter">
		<select>
			<option value="this_month"><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></option>
			<option value="previous_month"><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></option>
			<option value="this_week"><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></option>
			<option value="today"><?php _e('Today', ATUM_TEXT_DOMAIN) ?></option>
		</select>
	</div>

	<div class="data">

		<h3 class="widget-success">4.211.319 €</h3>
		<h5>REVENUE</h5>

		<hr>

		<h3 class="widget-primary">457.631</h3>
		<h5>ORDERS</h5>

	</div>

</div>
