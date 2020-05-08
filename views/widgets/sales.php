<?php
/**
 * View for the ATUM Dashboard Sales widget
 *
 * @since 1.4.0
 *
 * @var array $stats_today
 * @var array $stats_this_month
 */

defined( 'ABSPATH' ) || die;
?>

<div class="stats-data-widget" data-widget="sales_data" data-type="sales">

	<div class="data-filter">
		<select>
			<option value="today" selected="selected"><?php esc_html_e( 'Today', ATUM_TEXT_DOMAIN ) ?></option>
			<option value="month"><?php esc_html_e( 'This Month', ATUM_TEXT_DOMAIN ) ?></option>
		</select>
	</div>

	<div class="data" data-value="today">

		<h3 class="widget-success" data-prop="value" data-updated="yes"><?php echo esc_html( $stats_today['value'] ) ?></h3>
		<h5><?php esc_html_e( 'Value', ATUM_TEXT_DOMAIN ) ?></h5>

		<hr>

		<h3 class="widget-primary" data-prop="products" data-updated="yes"><?php echo esc_html( $stats_today['products'] ) ?></h3>
		<h5><?php esc_html_e( 'Products', ATUM_TEXT_DOMAIN ) ?></h5>

	</div>

	<div class="data hidden" data-value="month">

		<h3 class="widget-success" data-prop="value"></h3>
		<h5><?php esc_html_e( 'Value', ATUM_TEXT_DOMAIN ) ?></h5>

		<hr>

		<h3 class="widget-primary" data-prop="products"></h3>
		<h5><?php esc_html_e( 'Products', ATUM_TEXT_DOMAIN ) ?></h5>

	</div>

</div>
