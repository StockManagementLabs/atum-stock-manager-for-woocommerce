<?php
/**
 * View for the ATUM Dashboard Stock Control widget
 *
 * @since 1.3.9
 */
?>

<div class="stock-control-widget">

	<div class="stock-data">

		<h3 class="widget-success"><?php echo $stock_counters['count_in_stock'] ?></h3>
		<h5><?php _e('In Stock', ATUM_TEXT_DOMAIN) ?></h5>

		<hr>

		<h3 class="widget-warning"><?php echo $stock_counters['count_low_stock'] ?></h3>
		<h5><?php _e('Low Stock', ATUM_TEXT_DOMAIN) ?></h5>

		<hr>

		<h3 class="widget-danger"><?php echo $stock_counters['count_out_stock'] ?></h3>
		<h5><?php _e('Out of Stock', ATUM_TEXT_DOMAIN) ?></h5>

	</div>

	<div class="stock-chart">
		<canvas data-instock="<?php echo $stock_counters['count_in_stock'] ?>" data-outstock="<?php echo $stock_counters['count_out_stock'] ?>" data-lowstock="<?php echo $stock_counters['count_low_stock'] ?>"></canvas>
		<div class="stock-chart-tooltip">
			<table></table>
		</div>
	</div>

</div>
