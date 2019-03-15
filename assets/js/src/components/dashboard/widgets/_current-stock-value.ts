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
					token              : this.$widgetsContainer.data('nonce'),
					action             : 'atum_current_stock_values',
					categorySelected   : $('.categories-list').val(),
					productTypeSelected: $('.product-types-list').val(),
				},
				dataType  : 'json',
				beforeSend: () => {
					this.$currentStockValueWidget.addClass('overlay');
				},
				success   : (response: any) => {
					
					if (typeof response === 'object' && response.success === true) {
						
						const itemsWithoutPurcharsePrice: string = response.data.current_stock_values.items_without_purcharse_price,
						      $totalPurcharsePrice: JQuery       = this.$currentStockValueWidget.find('.total');
						
						$totalPurcharsePrice.html($totalPurcharsePrice.data('currency') + ' ' + response.data.current_stock_values.items_purcharse_price_total);
						this.$currentStockValueWidget.find('.items-count .total').html(response.data.current_stock_values.items_stocks_counter);
						this.$currentStockValueWidget.find('.items_without_purcharse_price').html(itemsWithoutPurcharsePrice);
						this.$currentStockValueWidget.removeClass('overlay');
						
						if (itemsWithoutPurcharsePrice === '0') {
							$('.items-without-purcharse-price').hide();
						}
						else {
							$('.items-without-purcharse-price').show();
						}
						
					}
					
				}
			});
		});
	
	}
	
}