<?php
/**
 * View for the Dashboard Statistics widget
 *
 * @since 1.2.3
 */

defined( 'ABSPATH' ) or die;
?>

<div class="atum-statistics-widget">
	<div class="stat-tables">

		<?php if ( $widget_options['sold_today']['enabled'] || $widget_options['lost_sales_today']['enabled'] ): ?>
		<div class="atum-table table-today">

			<?php if ( $widget_options['sold_today']['enabled'] ): ?>
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Sold Today', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php if ( $widget_options['sold_today']['data']['earnings'] ): ?>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_today['earnings'] ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['sold_today']['data']['products'] ): ?>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_today['products'] ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
			<?php endif ?>

			<?php  if ( $widget_options['lost_sales_today']['enabled'] ): ?>
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Lost Sales Today', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php if ( $widget_options['lost_sales_today']['data']['earnings'] ): ?>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_today['lost_earnings'] ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['lost_sales_today']['data']['products'] ): ?>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_today['lost_products'] ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
			<?php endif ?>

		</div>
		<?php endif ?>

		<?php if ( $widget_options['sold_this_month']['enabled'] || $widget_options['lost_sales_this_month']['enabled'] ): ?>
		<div class="atum-table table-current-month">

			<?php if( $widget_options['sold_this_month']['enabled'] ): ?>
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Sold This Month', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php if ( $widget_options['sold_this_month']['data']['earnings'] ): ?>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_this_month['earnings'] ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['sold_this_month']['data']['products'] ): ?>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_this_month['products'] ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
			<?php endif ?>

			<?php if( $widget_options['lost_sales_this_month']['enabled'] ): ?>
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Lost Sales This Month', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php if ( $widget_options['lost_sales_this_month']['data']['earnings'] ): ?>
					<tr>
						<td><?php _e('Earnings', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_this_month['lost_earnings'] ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['lost_sales_this_month']['data']['products'] ): ?>
					<tr>
						<td><?php _e('Products', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $stats_this_month['lost_products'] ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
			<?php endif ?>

		</div>
		<?php endif ?>

		<?php if ( $widget_options['orders_total']['enabled'] ): ?>
		<div class="atum-table table-totals">
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Orders Total', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php /*if ( $widget_options['orders_total']['data']['this_year'] ): ?>
					<tr>
						<td><?php _e('This Year', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_amount_this_year ?></td>
					</tr>
				<?php endif*/ ?>

				<?php if ( $widget_options['orders_total']['data']['this_month'] ): ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_amount_this_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['orders_total']['data']['previous_month'] ): ?>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_amount_previous_month ?></td>
					</tr>
				<?php endif; ?>

				<?php if ( $widget_options['orders_total']['data']['this_week'] ): ?>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_amount_this_week ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['orders_total']['data']['today'] ): ?>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_amount_today ?></td>
					</tr>
				<?php endif ?>

				</tbody>
			</table>
		</div>
		<?php endif ?>

		<?php if ( $widget_options['revenue']['enabled'] ): ?>
		<div class="atum-table table-revenue">
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Revenue', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php /*if ( $widget_options['revenue']['data']['this_year'] ): ?>
					<tr>
						<td><?php _e('This Year', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_revenue_this_year ?></td>
					</tr>
				<?php endif*/ ?>

				<?php if ( $widget_options['revenue']['data']['this_month'] ): ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_revenue_this_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['revenue']['data']['previous_month'] ): ?>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_revenue_previous_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['revenue']['data']['this_week'] ): ?>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_revenue_this_week ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['revenue']['data']['today'] ): ?>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $orders_revenue_today ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
		</div>
		<?php endif ?>

		<?php if ( $widget_options['promo_products']['enabled'] ): ?>
		<div class="atum-table table-promo-products">
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Promo Products Sold', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php /*if ( $widget_options['promo_products']['data']['this_year'] ): ?>
					<tr>
						<td><?php _e('This Year', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_products_this_year ?></td>
					</tr>
				<?php endif*/ ?>

				<?php if ( $widget_options['promo_products']['data']['this_month'] ): ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_products_this_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_products']['data']['previous_month'] ): ?>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_products_previous_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_products']['data']['this_week'] ): ?>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_products_this_week ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_products']['data']['today'] ): ?>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_products_today ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
		</div>
		<?php endif ?>

		<?php if ( $widget_options['promo_value']['enabled'] ): ?>
		<div class="atum-table table-promo-value">
			<table>
				<thead>
					<tr>
						<th colspan="2"><?php _e('Promo Value', ATUM_TEXT_DOMAIN) ?></th>
					</tr>
				</thead>

				<tbody>
				<?php /*if ( $widget_options['promo_value']['data']['this_year'] ): ?>
					<tr>
						<td><?php _e('This Year', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_value_this_year ?></td>
					</tr>
				<?php endif*/ ?>

				<?php if ( $widget_options['promo_value']['data']['this_month'] ): ?>
					<tr>
						<td><?php _e('This Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_value_this_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_value']['data']['previous_month'] ): ?>
					<tr>
						<td><?php _e('Previous Month', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_value_previous_month ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_value']['data']['this_week'] ): ?>
					<tr>
						<td><?php _e('This Week', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_value_this_week ?></td>
					</tr>
				<?php endif ?>

				<?php if ( $widget_options['promo_value']['data']['today'] ): ?>
					<tr>
						<td><?php _e('Today', ATUM_TEXT_DOMAIN) ?></td>
						<td class="amt"><?php echo $promo_value_today ?></td>
					</tr>
				<?php endif ?>
				</tbody>
			</table>
		</div>
		<?php endif ?>

	</div>

	<?php if ( $widget_options['circle_stats']['enabled'] ): ?>
	<div class="stock-counters">

		<?php
		$in_stock = 0;

		if ($stock_counters['count_in_stock'] > 0) {
			$in_stock = ( ( $stock_counters['count_in_stock'] * 100  ) / $stock_counters['count_all'] ) / 100;
		}
		?>

		<div class="stock-indicator">
			<div id="in-stock-circle" class="circle" data-thickness="11" data-size="100" data-value="<?php echo $in_stock ?>" data-stock="<?php echo $stock_counters['count_in_stock'] ?>" data-fill='{"gradient": ["greenyellow", "#00B050", "#00B050"], "gradientAngle": -1.15}'>
				<strong></strong>
			</div>

			<div><?php _e('In Stock', ATUM_TEXT_DOMAIN) ?></div>
		</div>

		<?php
		$low_stock = 0;

		if ($stock_counters['count_low_stock'] > 0) {
			$low_stock = ( ( $stock_counters['count_low_stock'] * 100  ) / $stock_counters['count_all'] ) / 100;
		}
		?>

		<div class="stock-indicator">
			<div id="low-stock-circle" class="circle" data-thickness="11" data-size="100" data-value="<?php echo $low_stock ?>" data-stock="<?php echo $stock_counters['count_low_stock'] ?>" data-fill='{"gradient": ["deepskyblue", "#0073AA", "#0073AA"], "gradientAngle": -1.15}'>
				<strong></strong>
			</div>

			<div><?php _e('Low Stock', ATUM_TEXT_DOMAIN) ?></div>
		</div>

		<?php
		$out_stock = 0;

		if ($stock_counters['count_out_stock'] > 0) {
			$out_stock = ( ( $stock_counters['count_out_stock'] * 100  ) / $stock_counters['count_all'] ) / 100;
		}
		?>

		<div class="stock-indicator">
			<div id="out-of-stock-circle" class="circle" data-thickness="11" data-size="100" data-value="<?php echo $out_stock ?>" data-stock="<?php echo $stock_counters['count_out_stock'] ?>" data-fill='{"gradient": ["orange", "#EF4D5A", "#EF4D5A"], "gradientAngle": -1.15}'>
				<strong></strong>
			</div>

			<div><?php _e('Out of Stock', ATUM_TEXT_DOMAIN) ?></div>
		</div>

	</div>
	<?php endif ?>

</div>

<?php if ( $widget_options['circle_stats']['enabled'] ): ?>
<script type="text/javascript">
	jQuery(function($){

		var atumTotalProducts = <?php echo $stock_counters['count_all'] ?>;

		$('.circle').circleProgress().on('circle-animation-progress', function (event, progress, stepValue) {

			var percentage = stepValue.toFixed(2).substr(2),
				textValue = 0;

			if (atumTotalProducts) {
				textValue = (atumTotalProducts * percentage) / 100;
			}

			$(this).find('strong').text(textValue.toFixed(0));

		});

	});
</script>
<?php endif;
