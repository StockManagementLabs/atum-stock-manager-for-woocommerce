<?php
/**
 * View for the ATUM Dashboard Stock Control widget
 *
 * @since 1.4.0
 */

/**
 * @var array $sc_links
 * @var array $stock_counters
 */
?>

<div class="stock-control-widget">

	<div class="stock-data">

		<a href="<?php echo $sc_links['in_stock'] ?>" title="<?php _e('View Products In Stock', ATUM_TEXT_DOMAIN) ?>">
			<h3 class="widget-success"><?php echo $stock_counters['count_in_stock'] ?></h3>
			<h5><?php _e('In Stock', ATUM_TEXT_DOMAIN) ?></h5>
		</a>

		<hr>

		<a href="<?php echo $sc_links['low_stock'] ?>" title="<?php _e('View Products with Low Stock', ATUM_TEXT_DOMAIN) ?>">
			<h3 class="widget-warning"><?php echo $stock_counters['count_low_stock'] ?></h3>
			<h5><?php _e('Low Stock', ATUM_TEXT_DOMAIN) ?></h5>
		</a>

		<hr>

		<a href="<?php echo $sc_links['out_stock'] ?>" title="<?php _e('View Products Out of Stock', ATUM_TEXT_DOMAIN) ?>">
			<h3 class="widget-danger"><?php echo $stock_counters['count_out_stock'] ?></h3>
			<h5><?php _e('Out of Stock', ATUM_TEXT_DOMAIN) ?></h5>
		</a>

		<hr>

		<a href="<?php echo $sc_links['unmanaged'] ?>" title="<?php _e('View Products Unmanaged by WC', ATUM_TEXT_DOMAIN) ?>">
			<h3 class="widget-primary"><?php echo $stock_counters['count_unmanaged'] ?></h3>
			<h5><?php _e('Unmanaged', ATUM_TEXT_DOMAIN) ?></h5>
		</a>

	</div>

	<div class="stock-chart">
		<canvas data-instock="<?php echo $stock_counters['count_in_stock'] ?>" data-outstock="<?php echo $stock_counters['count_out_stock'] ?>" data-lowstock="<?php echo $stock_counters['count_low_stock'] ?>" data-unmanaged="<?php echo $stock_counters['count_unmanaged'] ?>"></canvas>
		<div class="stock-chart-tooltip">
			<table></table>
		</div>
	</div>

</div>
