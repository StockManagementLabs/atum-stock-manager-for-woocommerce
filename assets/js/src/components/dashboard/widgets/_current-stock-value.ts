/* =======================================
   DASHBOARD CURRENT STOCK VALUE WIDGET
   ======================================= */

export default class CurrentStockValueWidget {
	
	$currentStockValueWidget: JQuery;
	
	constructor(
		private $widgetsContainer: JQuery
	) {
		
		this.$currentStockValueWidget = $('.current-stock-value-widget');
		
		if (this.$currentStockValueWidget.length) {
			this.onChange();
		}
		
	}
	
	onChange() {
		
		this.$currentStockValueWidget.find('select').change( (evt: JQueryEventObject) => {
			
			$.ajax({
				url       : window['ajaxurl'],
				method    : 'POST',
				data      : {
					action             : 'atum_current_stock_values',
					security           : this.$widgetsContainer.data('nonce'),
					categorySelected   : $('.categories-list').val(),
					productTypeSelected: $('.product-types-list').val(),
					writeOff           : $('.write-off-filter').val() || 'no'
				},
				dataType  : 'json',
				beforeSend: () => this.$currentStockValueWidget.addClass('overlay'),
				success   : ( response: any ) => {
					
					if (typeof response === 'object' && response.success === true) {
						
						const itemsWithoutPurchasePrice: string = response.data.current_stock_values.items_without_purchase_price,
						      $totalPurchasePrice: JQuery       = this.$currentStockValueWidget.find('.total');
						
						$totalPurchasePrice.html(response.data.current_stock_values.items_purchase_price_total);
						this.$currentStockValueWidget.find('.items-count .total').html(response.data.current_stock_values.items_stocks_counter);
						this.$currentStockValueWidget.find('.items_without_purchase_price').html(itemsWithoutPurchasePrice);
						this.$currentStockValueWidget.removeClass('overlay');
						
						if (itemsWithoutPurchasePrice === '0') {
							$('.items-without-purchase-price').hide();
						}
						else {
							$('.items-without-purchase-price').show();
						}
						
					}
					
				}
			});
		});
	
	}
	
}