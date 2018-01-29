<?php
/**
 * View for the ATUM Dashboard Sales widget
 *
 * @since 1.3.9
 */
?>

<div class="stats-data-widget" data-widget="sales_data">

	<div class="data-filter">
		<select>
			<option value="today"><?php _e('Today', ATUM_TEXT_DOMAIN) ?></option>
			<option value="month"><?php _e('Month', ATUM_TEXT_DOMAIN) ?></option>
		</select>
	</div>

	<div class="data" data-value="today">

		<h3 class="widget-success"><?php echo $stats_today['earnings'] ?></h3>
		<h5><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></h5>

		<hr>

		<h3 class="widget-primary"><?php echo $stats_today['products'] ?></h3>
		<h5><?php _e('Products', ATUM_TEXT_DOMAIN) ?></h5>

	</div>

	<div class="data hidden" data-value="month">

		<h3 class="widget-success"><?php echo $stats_this_month['earnings'] ?></h3>
		<h5><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></h5>

		<hr>

		<h3 class="widget-primary"><?php echo $stats_this_month['products'] ?></h3>
		<h5><?php _e('Products', ATUM_TEXT_DOMAIN) ?></h5>

	</div>

</div>