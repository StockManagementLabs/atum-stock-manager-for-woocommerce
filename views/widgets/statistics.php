<?php
/**
 * View for the ATUM Dashboard Statistics widget
 *
 * @since 1.4.0
 *
 * @var array  $legends
 * @var array  $dataset
 * @var string $period
 */

defined( 'ABSPATH' ) || die;
?>

<div class="statistics-widget">

	<nav class="mobile-filter-nav">
		<ul>
			<li data-show-filter=".filter-controls">
				<i class="atum-icon atmi-funnel"></i> <span><?php esc_html_e( 'Filters', ATUM_TEXT_DOMAIN ) ?></span> <span class="status">+</span>
			</li>
			<li data-show-filter=".chart-type">
				<i class="atum-icon atmi-chart-bars"></i> <span><?php esc_html_e( 'Chart Type', ATUM_TEXT_DOMAIN ) ?></span> <span class="status">+</span>
			</li>
			<li data-show-filter=".chart-legend">
				<i class="atum-icon atmi-layers"></i> <span><?php esc_html_e( 'Layers', ATUM_TEXT_DOMAIN ) ?></span> <span class="status">+</span>
			</li>
		</ul>
	</nav>

	<div class="chart-filter">

		<div class="filter-controls">
			<select class="chart-data left">
				<option value="sales"><?php esc_html_e( 'Sales', ATUM_TEXT_DOMAIN ) ?></option>
				<option value="lost-sales"><?php esc_html_e( 'Lost Sales', ATUM_TEXT_DOMAIN ) ?></option>
				<option value="promo-sales"><?php esc_html_e( 'Promo Sales', ATUM_TEXT_DOMAIN ) ?></option>
				<option value="orders"><?php esc_html_e( 'Orders', ATUM_TEXT_DOMAIN ) ?></option>
			</select>

			<select class="chart-period left">
				<option value="this_year"><?php esc_html_e( 'This Year', ATUM_TEXT_DOMAIN ) ?></option>
				<option value="previous_year"><?php esc_html_e( 'Previous Year', ATUM_TEXT_DOMAIN ) ?></option>
				<option value="this_month"><?php esc_html_e( 'This Month', ATUM_TEXT_DOMAIN ) ?></option>
				<option value="previous_month"><?php esc_html_e( 'Previous Month', ATUM_TEXT_DOMAIN ) ?></option>
				<option value="this_week"><?php esc_html_e( 'This Week', ATUM_TEXT_DOMAIN ) ?></option>
				<option value="previous_week"><?php esc_html_e( 'Previous Week', ATUM_TEXT_DOMAIN ) ?></option>
			</select>
		</div>

		<div class="chart-type">

			<a class="active" href="#" title="<?php esc_html_e( 'Line Chart', ATUM_TEXT_DOMAIN ) ?>" data-view="line">
				<svg version="1.1" id="line-chart" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="100.8px" height="100.8px" viewBox="0 0 100.8 100.8" xml:space="preserve">
					<path d="M99.3,94.9H3V4.4c0-0.8-0.7-1.5-1.5-1.5S0,3.6,0,4.4v92c0,0.8,0.7,1.5,1.5,1.5h97.8c0.8,0,1.5-0.7,1.5-1.5 S100.1,94.9,99.3,94.9z" />
					<g>
						<path d="M99.7,91c-0.5,0-1-0.4-1-0.9c-1-12.2-5.8-44.3-15.6-46.9c-3.4-0.9-7.4,1.9-11.8,8.4C67,59,62.9,62.4,59,61.9 c-6.3-0.7-9.7-11.1-11.3-16.1l-0.2-0.5C44.8,37,37,23.3,27.5,22.2c-6.3-0.8-12.6,3.8-18.8,13.6c-0.3,0.5-0.9,0.6-1.4,0.3 c-0.5-0.3-0.6-0.9-0.3-1.4c6.7-10.5,13.6-15.4,20.8-14.5c11.7,1.5,19.7,18.2,21.7,24.5l0.2,0.5c1.4,4.2,4.6,14.2,9.6,14.8 c3,0.3,6.6-2.9,10.4-9.5c5-7.3,9.7-10.4,14-9.3c13.2,3.5,16.9,46.8,17,48.7C100.7,90.5,100.3,91,99.7,91C99.8,91,99.7,91,99.7,91z" />
					</g>
				</svg>
			</a>

			<a href="#" title="<?php esc_html_e( 'Area Chart', ATUM_TEXT_DOMAIN ) ?>" data-view="area">
				<svg version="1.1" id="area-chart" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="100.8px" height="100.8px" viewBox="0 0 100.8 100.8" xml:space="preserve">
					<path d="M99.3,94.9H3V4.4c0-0.8-0.7-1.5-1.5-1.5S0,3.6,0,4.4v92c0,0.8,0.7,1.5,1.5,1.5h97.8c0.8,0,1.5-0.7,1.5-1.5 S100.1,94.9,99.3,94.9z"/>
					<path d="M70.1,50.9c-13.3,22.7-19.9,0.5-22-6c-3.6-11-20.4-41.8-40.7-9.7v54.7h93C100.4,89.9,93.3,17,70.1,50.9z"/>
				</svg>
			</a>

			<a href="#" title="<?php esc_html_e( 'Bar Chart', ATUM_TEXT_DOMAIN ) ?>" data-view="bar">
				<svg version="1.1" id="bar-chart" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="100.8px" height="100.8px" viewBox="0 0 100.8 100.8" style="enable-background:new 0 0 100.8 100.8;" xml:space="preserve">
					<path d="M99.3,94.9H3V4.4c0-0.8-0.7-1.5-1.5-1.5S0,3.6,0,4.4v92c0,0.8,0.7,1.5,1.5,1.5h97.8c0.8,0,1.5-0.7,1.5-1.5 S100.1,94.9,99.3,94.9z"/>
					<path d="M18.8,49.5H8.4c-0.6,0-1,0.5-1,1v38.4c0,0.6,0.5,1,1,1h10.4c0.6,0,1-0.5,1-1V50.5C19.8,49.9,19.4,49.5,18.8,49.5z"/>
					<path d="M33.6,41H23.2c-0.6,0-1,0.5-1,1v46.8c0,0.6,0.5,1,1,1h10.4c0.6,0,1-0.5,1-1V42C34.6,41.5,34.1,41,33.6,41z"/>
					<path d="M50.4,12.8H40c-0.6,0-1,0.5-1,1v75.1c0,0.6,0.5,1,1,1h10.4c0.6,0,1-0.5,1-1V13.8C51.4,13.2,50.9,12.8,50.4,12.8z"/>
					<path d="M65.1,21.3H54.7c-0.6,0-1,0.5-1,1v66.6c0,0.6,0.5,1,1,1h10.4c0.6,0,1-0.5,1-1V22.3C66.1,21.8,65.7,21.3,65.1,21.3z"/>
					<path d="M82,35.5H71.6c-0.6,0-1,0.5-1,1v52.3c0,0.6,0.5,1,1,1H82c0.6,0,1-0.5,1-1V36.6C83,36,82.5,35.5,82,35.5z"/>
					<path d="M96.7,55.6H86.3c-0.6,0-1,0.5-1,1v32.3c0,0.6,0.5,1,1,1h10.4c0.6,0,1-0.5,1-1V56.6C97.7,56.1,97.3,55.6,96.7,55.6z"/>
				</svg>
			</a>

		</div>

		<div class="chart-legend">

			<span class="legend-switch">
				<span class="form-switch">
					<input type="checkbox" id="value-chart" checked value="on" class="form-check-input value-chart">
					<label for="value-chart" class="form-check-label"><?php echo esc_html( $legends['value'] ) ?></label>
				</span>
			</span>

			<span class="legend-switch">
				<span class="form-switch">
					<input type="checkbox" id="products-chart" checked value="on" class="form-check-input products-chart blue-switch">
					<label for="products-chart" class="form-check-label"><?php echo esc_html( $legends['products'] ) ?></label>
				</span>
			</span>

		</div>

	</div>

	<div class="canvas-wrapper">
		<canvas class="statistics-canvas" data-chartdata='<?php echo esc_attr( wp_json_encode( $dataset ) ) ?>' data-period="<?php echo esc_attr( $period ) ?>" data-legends='<?php echo esc_attr( wp_json_encode( $legends ) ) ?>'></canvas>
		<div class="stats-chart-tooltip" id="stats-chart-tooltip-0"></div>
		<div class="stats-chart-tooltip" id="stats-chart-tooltip-1"></div>
	</div>

</div>
