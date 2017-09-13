<?php
/**
 * View for the Dashboard Statistics widget configuration
 *
 * @since 1.2.7
 */

defined( 'ABSPATH' ) or die;

?>
<div class="atum-statistics-widget">
	<div class="stat-tables">

		<div class="atum-table config-table table-today">
			<table>
				<thead>
					<tr>
						<th colspan="2">
							<div class="th-wrap">
								<span><?php _e('Sold Today', ATUM_TEXT_DOMAIN) ?></span>
								<span><input type="checkbox" name="sold_today" id="sold_today"<?php checked($sold_today, TRUE) ?> class="section-checkbox" value="true"></span>
							</div>
						</th>
					</tr>
				</thead>

				<tbody>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="sold_today_earnings" id="sold_today_earnings"<?php checked($sold_today_earnings, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="sold_today_products" id="sold_today_products"<?php checked($sold_today_products, TRUE) ?> value="true">
						</td>
					</tr>
				</tbody>
			</table>

			<table>
				<thead>
					<tr>
						<th colspan="2">
							<div class="th-wrap">
								<span><?php _e( 'Lost Sales Today', ATUM_TEXT_DOMAIN ) ?></span>
								<span><input type="checkbox" name="lost_sales_today" id="lost_sales_today"<?php checked($lost_sales_today, TRUE) ?> class="section-checkbox" value="true"></span>
							</div>
						</th>
					</tr>
				</thead>

				<tbody>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="lost_sales_today_earnings" id="lost_sales_today_earnings"<?php checked($lost_sales_today_earnings, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="lost_sales_today_products" id="lost_sales_today_products"<?php checked($lost_sales_today_products, TRUE) ?> value="true">
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="atum-table config-table table-current-month">
			<table>
				<thead>
					<tr>
						<th colspan="2">
							<div class="th-wrap">
								<span><?php _e( 'Sold This Month', ATUM_TEXT_DOMAIN ) ?></span>
								<span><input type="checkbox" name="sold_this_month" id="sold_this_month"<?php checked($sold_this_month, TRUE) ?> class="section-checkbox" value="true"></span>
							</div>
						</th>
					</tr>
				</thead>

				<tbody>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="sold_this_month_earnings" id="sold_this_month_earnings"<?php checked($sold_this_month_earnings, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="sold_this_month_products" id="sold_this_month_products"<?php checked($sold_this_month_products, TRUE) ?> value="true">
						</td>
					</tr>
				</tbody>
			</table>

			<table>
				<thead>
					<tr>
						<th colspan="2">
							<div class="th-wrap">
								<span><?php _e('Lost Sales This Month', ATUM_TEXT_DOMAIN) ?></span>
								<span><input type="checkbox" name="lost_sales_this_month" id="lost_sales_this_month"<?php checked($lost_sales_this_month, TRUE) ?> class="section-checkbox" value="true"></span>
							</div>
						</th>
					</tr>
				</thead>

				<tbody>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="lost_sales_this_month_earnings" id="lost_sales_this_month_earnings"<?php checked($lost_sales_this_month_earnings, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="lost_sales_this_month_products" id="lost_sales_this_month_products"<?php checked($lost_sales_this_month_products, TRUE) ?> value="true">
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="atum-table config-table table-totals">
			<table>
				<thead>
					<tr>
						<th colspan="2">
							<div class="th-wrap">
								<span><?php _e( 'Orders Total', ATUM_TEXT_DOMAIN ) ?></span>
								<span><input type="checkbox" name="orders_total" id="orders_total"<?php checked($orders_total, TRUE) ?> class="section-checkbox" value="true"></span>
							</div>
						</th>
					</tr>
				</thead>

				<tbody>
					<?php /*<tr>
						<td><?php _e('This Year', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="orders_total_this_year" id="orders_total_this_year"<?php checked($orders_total_this_year, TRUE) ?> value="true">
						</td>
					</tr>*/ ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="orders_total_this_month" id="orders_total_this_month"<?php checked($orders_total_this_month, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="orders_total_previous_month" id="orders_total_previous_month"<?php checked($orders_total_previous_month, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="orders_total_this_week" id="orders_total_this_week"<?php checked($orders_total_this_week, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="orders_total_today" id="orders_total_today"<?php checked($orders_total_today, TRUE) ?> value="true">
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="atum-table config-table table-revenue">
			<table>
				<thead>
					<tr>
						<th colspan="2">
							<div class="th-wrap">
								<span><?php _e( 'Revenue', ATUM_TEXT_DOMAIN ) ?></span>
								<span><input type="checkbox" name="revenue" id="revenue"<?php checked($revenue, TRUE) ?> class="section-checkbox" value="true"></span>
							</div>
						</th>
					</tr>
				</thead>

				<tbody>
					<?php /*<tr>
						<td><?php _e('This Year', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
	                        <input type="checkbox" name="revenue_this_year" id="revenue_this_year"<?php checked($revenue_this_year, TRUE) ?> value="true">
						</td>
					</tr>*/ ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="revenue_this_month" id="revenue_this_month"<?php checked($revenue_this_month, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="revenue_previous_month" id="revenue_previous_month"<?php checked($revenue_previous_month, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="revenue_this_week" id="revenue_this_week"<?php checked($revenue_this_week, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="revenue_today" id="revenue_today"<?php checked($revenue_today, TRUE) ?> value="true">
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="atum-table config-table table-promo-products">
			<table>
				<thead>
					<tr>
						<th colspan="2">
							<div class="th-wrap">
								<span><?php _e( 'Promo Products Sold', ATUM_TEXT_DOMAIN ) ?></span>
								<span><input type="checkbox" name="promo_products" id="promo_products"<?php checked($promo_products, TRUE) ?> class="section-checkbox" value="true"></span>
							</div>
						</th>
					</tr>
				</thead>

				<tbody>
					<?php /*<tr>
						<td><?php _e('This Year', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_products_this_year" id="promo_products_this_year"<?php checked($promo_products_this_year, TRUE) ?> value="true">
						</td>
					</tr>*/ ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_products_this_month" id="promo_products_this_month"<?php checked($promo_products_this_month, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_products_previous_month" id="promo_products_previous_month"<?php checked($promo_products_previous_month, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_products_this_week" id="promo_products_this_week"<?php checked($promo_products_this_week, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_products_today" id="promo_products_today"<?php checked($promo_products_today, TRUE) ?> value="true">
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="atum-table config-table table-promo-value">
			<table>
				<thead>
					<tr>
						<th colspan="2">
							<div class="th-wrap">
								<span><?php _e( 'Promo Value', ATUM_TEXT_DOMAIN ) ?></span>
								<span><input type="checkbox" name="promo_value" id="promo_value"<?php checked($promo_value, TRUE) ?> class="section-checkbox" value="true"></span>
							</div>
						</th>
					</tr>
				</thead>

				<tbody>
					<?php /*<tr>
						<td><?php _e('This Year', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_value_this_year" id="promo_value_this_year"<?php checked($promo_value_this_year, TRUE) ?> value="true">
						</td>
					</tr>*/ ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_value_this_month" id="promo_value_this_month"<?php checked($promo_value_this_month, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_value_previous_month" id="promo_value_previous_month"<?php checked($promo_value_previous_month, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_value_this_week" id="promo_value_this_week"<?php checked($promo_value_this_week, TRUE) ?> value="true">
						</td>
					</tr>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt">
							<input type="checkbox" name="promo_value_today" id="promo_value_today"<?php checked($promo_value_today, TRUE) ?> value="true">
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="atum-table config-table table-circle-stats">
			<table>
				<thead>
				<tr>
					<th colspan="2">
						<div class="th-wrap">
							<span><?php _e( 'Circle Stats', ATUM_TEXT_DOMAIN ) ?></span>
							<span><input type="checkbox" name="circle_stats" id="circle_stats"<?php checked($circle_stats, TRUE) ?> value="true"></span>
						</div>
					</th>
				</tr>
				</thead>
			</table>
		</div>

	</div>
	<input type="hidden" name="atum_statistics" value="yes">
</div>

<script type="text/javascript">
	jQuery(function($){

		var $configTables = $('.atum-table.config-table');

		$configTables.on('change', 'input:checkbox', function() {

			var $checkbox = $(this),
			    state     = $checkbox.is(':checked'),
			    isSection = $checkbox.hasClass('section-checkbox');

			if (isSection) {
				var $dataCheckboxes = $checkbox.closest('table').find('td.amt').find('input:checkbox');
				$dataCheckboxes.prop('disabled', !state).prop('checked', state);
			}

		});

		// Initialize unchecked sections
		$configTables.find('.section-checkbox').not(':checked').change();

	});
</script>