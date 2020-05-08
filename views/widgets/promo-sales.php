<?php
/**
 * View for the ATUM Dashboard Promo Sales widget
 *
 * @since 1.4.0
 *
 * @var array $stats_today
 * @var array $stats_this_week
 * @var array $stats_this_month
 * @var array $stats_previous_month
 */

defined( 'ABSPATH' ) || die;
?>

<div class="stats-data-widget" data-widget="sales_data" data-type="promo_sales">

	<div class="data-filter">
		<select>
			<option value="today" selected="selected"><?php esc_html_e( 'Today', ATUM_TEXT_DOMAIN ) ?></option>
			<option value="this_month"><?php esc_html_e( 'This Month', ATUM_TEXT_DOMAIN ) ?></option>
			<option value="previous_month"><?php esc_html_e( 'Previous Month', ATUM_TEXT_DOMAIN ) ?></option>
			<option value="this_week"><?php esc_html_e( 'This Week', ATUM_TEXT_DOMAIN ) ?></option>
		</select>
	</div>

	<div class="data" data-value="today">

		<h3 class="widget-success" data-prop="value" data-updated="yes"><?php echo esc_html( $stats_today['value'] ) ?></h3>
		<h5><?php esc_html_e( 'Value', ATUM_TEXT_DOMAIN ) ?></h5>

		<hr>

		<h3 class="widget-primary" data-prop="products" data-updated="yes"><?php echo esc_html( $stats_today['products'] ) ?></h3>
		<h5><?php esc_html_e( 'Products', ATUM_TEXT_DOMAIN ) ?></h5>

	</div>

	<div class="data hidden" data-value="this_week">

		<h3 class="widget-success" data-prop="value"></h3>
		<h5><?php esc_html_e( 'Value', ATUM_TEXT_DOMAIN ) ?></h5>

		<hr>

		<h3 class="widget-primary" data-prop="products"></h3>
		<h5><?php esc_html_e( 'Products', ATUM_TEXT_DOMAIN ) ?></h5>

	</div>

	<div class="data hidden" data-value="this_month">

		<h3 class="widget-success" data-prop="value"></h3>
		<h5><?php esc_html_e( 'Value', ATUM_TEXT_DOMAIN ) ?></h5>

		<hr>

		<h3 class="widget-primary" data-prop="products"></h3>
		<h5><?php esc_html_e( 'Products', ATUM_TEXT_DOMAIN ) ?></h5>

	</div>

	<div class="data hidden" data-value="previous_month">

		<h3 class="widget-success" data-prop="value"></h3>
		<h5><?php esc_html_e( 'Value', ATUM_TEXT_DOMAIN ) ?></h5>

		<hr>

		<h3 class="widget-primary" data-prop="products"></h3>
		<h5><?php esc_html_e( 'Products', ATUM_TEXT_DOMAIN ) ?></h5>

	</div>

</div>
