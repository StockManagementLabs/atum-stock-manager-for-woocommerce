<?php
/**
 * View for the ATUM Dashboard Current Stock Value widget
 *
 * @since 1.5.0
 *
 * @var array $current_stock_values
 */

defined( 'ABSPATH' ) || die;

use Atum\Dashboard\WidgetHelpers;
?>

<div class="current-stock-value-widget">

	<div class="current-stock-value-filters">

		<?php
		// Category filtering.
		wc_product_dropdown_categories( array(
			'show_count'       => 0,
			'hide_empty'       => 0,
			'show_option_none' => __( 'All categories', ATUM_TEXT_DOMAIN ),
			'class'            => 'categories-list',
		) );

		// Product type filtering.
		echo WidgetHelpers::product_types_dropdown( '', 'product-types-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		do_action( 'atum/dashboard/current_stock_value_widget/after_filters' );
		?>
	</div>

	<div class="current-stock-value-content">
		<div class="stock-counter">
			<div class="total-items-purchase-price">
				<div class="total" data-currency="<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>">
					<?php echo $current_stock_values['items_purchase_price_total']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="stock-value-title">
					<h5><?php esc_html_e( 'Stock value', ATUM_TEXT_DOMAIN ) ?></h5>
				</div>
			</div>

			<div class="separator-line"></div>

			<div class="items-count">
				<div class="total">
					<?php echo esc_html( $current_stock_values['items_stocks_counter'] ); ?>
				</div>
				<div class="items-value-title">
					<h5><?php esc_html_e( 'Items in stock', ATUM_TEXT_DOMAIN ) ?></h5>
				</div>
			</div>
		</div>

		<?php if ( 0 !== $current_stock_values['items_without_purchase_price'] ) : ?>
			<div class="items-without-purchase-price">
				<i class="atmi-warning"></i>
				<span class="items_without_purchase_price">
					<?php echo esc_html( $current_stock_values['items_without_purchase_price'] ); ?>
				</span>
				<?php esc_html_e( ' items in stock without specified purchase price.', ATUM_TEXT_DOMAIN ); ?>
			</div>
		<?php endif; ?>
	</div>


</div>
