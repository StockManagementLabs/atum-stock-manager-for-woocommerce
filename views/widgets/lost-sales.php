<?php
/**
 * View for the ATUM Dashboard Lost Sales widget
 *
 * @since 1.4.0
 *
 * @var array $stats_today
 * @var array $stats_this_month
 */

defined( 'ABSPATH' ) || die;
?>

<div class="stats-data-widget" data-widget="sales_data" data-type="lost_sales">

	<div class="data-filter">
		<select>
			<option value="today"><?php esc_html_e( 'Today', ATUM_TEXT_DOMAIN ) ?></option>
			<option value="month"><?php esc_html_e( 'This Month', ATUM_TEXT_DOMAIN ) ?></option>
		</select>
	</div>

	<div class="data" data-value="today">

		<h3 class="widget-success" data-prop="lost_value" data-updated="yes"><?php echo esc_html( $stats_today['lost_value'] ) ?></h3>
		<h5><?php esc_html_e( 'Value', ATUM_TEXT_DOMAIN ) ?></h5>

		<hr>

		<h3 class="widget-primary" data-prop="lost_products" data-updated="yes"><?php echo esc_html( $stats_today['lost_products'] ) ?></h3>
		<h5><?php esc_html_e( 'Products', ATUM_TEXT_DOMAIN ) ?></h5>

	</div>

	<div class="data hidden" data-value="month">

		<h3 class="widget-success" data-prop="lost_value"></h3>
		<h5><?php esc_html_e( 'Value', ATUM_TEXT_DOMAIN ) ?></h5>

		<hr>

		<h3 class="widget-primary" data-prop="lost_products"></h3>
		<h5><?php esc_html_e( 'Products', ATUM_TEXT_DOMAIN ) ?></h5>

	</div>

</div>

<?php echo $config; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
