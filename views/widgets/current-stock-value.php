<?php
/**
 * View for the ATUM Dashboard Current Stock Value widget
 *
 * @since 1.5.0
 *
 * @var array $current_stock_values
 */

use Atum\Inc\Helpers;


defined( 'ABSPATH' ) || die;
?>

<div class="current-stock-value-widget">

	<div class="current-stock-value-filters">

		<?php
		// Category filtering.
		wc_product_dropdown_categories( array(
			'show_count'       => 0,
			'show_option_none' => __( 'All categories', ATUM_TEXT_DOMAIN ),
			'class'            => 'categories-list',
		) );

		// Product type filtering.
		echo Helpers::product_types_dropdown( '', 'product-types-list' ); // WPCS: XSS ok.

		?>
	</div>

	<div class="current-stock-value-content">

		<div class="stock-counter">
			<div class="total-items-purcharse-price">
				<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
				<span class="total">
					<?php echo esc_html( $current_stock_values['items_purcharse_price_total'] ); ?>
				</span>
			</div>
			<h5><?php esc_html_e( 'Stock value', ATUM_TEXT_DOMAIN ) ?></h5>

			<hr/>

			<span class="items-count">
				<?php echo esc_html( $current_stock_values['items_stocks_counter'] ); ?>
			</span>
			<h5><?php esc_html_e( 'Items in stock', ATUM_TEXT_DOMAIN ) ?></h5>
		</div>
	</div>


</div>
