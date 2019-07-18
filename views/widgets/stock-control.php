<?php
/**
 * View for the ATUM Dashboard Stock Control widget
 *
 * @since 1.4.0
 *
 * @var array $sc_links
 * @var array $stock_counters
 */

defined( 'ABSPATH' ) || die;
?>

<div class="stock-control-widget">

	<div class="stock-data<?php echo esc_attr( ' ' . $mode ); ?>">

		<a href="<?php echo esc_url( $sc_links['in_stock'] ) ?>" title="<?php esc_html_e( 'View Products In Stock', ATUM_TEXT_DOMAIN ) ?>">
			<h3 class="widget-success"><?php echo esc_html( $stock_counters['count_in_stock'] ) ?></h3>
			<h5><?php esc_html_e( 'In Stock', ATUM_TEXT_DOMAIN ) ?></h5>
		</a>

		<hr>

		<a href="<?php echo esc_url( $sc_links['low_stock'] ) ?>" title="<?php esc_html_e( 'View Products with Low Stock', ATUM_TEXT_DOMAIN ) ?>">
			<h3 class="widget-warning"><?php echo esc_html( $stock_counters['count_low_stock'] ) ?></h3>
			<h5><?php esc_html_e( 'Low Stock', ATUM_TEXT_DOMAIN ) ?></h5>
		</a>

		<hr>

		<a href="<?php echo esc_url( $sc_links['out_stock'] ) ?>" title="<?php esc_html_e( 'View Products Out of Stock', ATUM_TEXT_DOMAIN ) ?>">
			<h3 class="widget-danger"><?php echo esc_html( $stock_counters['count_out_stock'] ) ?></h3>
			<h5><?php esc_html_e( 'Out of Stock', ATUM_TEXT_DOMAIN ) ?></h5>
		</a>

		<hr>

		<a href="<?php echo esc_url( $sc_links['unmanaged'] ) ?>" title="<?php esc_html_e( 'View Products Unmanaged by WC', ATUM_TEXT_DOMAIN ) ?>">
			<h3 class="widget-primary"><?php echo esc_html( $stock_counters['count_unmanaged'] ) ?></h3>
			<h5><?php esc_html_e( 'Unmanaged', ATUM_TEXT_DOMAIN ) ?></h5>
		</a>

	</div>

	<div class="stock-chart">
		<canvas data-instock="<?php echo esc_attr( $stock_counters['count_in_stock'] ) ?>" data-lowstock="<?php echo esc_attr( $stock_counters['count_low_stock'] ) ?>"
				data-outstock="<?php echo esc_attr( $stock_counters['count_out_stock'] ) ?>" data-unmanaged="<?php echo esc_attr( $stock_counters['count_unmanaged'] ) ?>">
		</canvas>
		<div class="stock-chart-tooltip">
			<table></table>
		</div>
	</div>

</div>
