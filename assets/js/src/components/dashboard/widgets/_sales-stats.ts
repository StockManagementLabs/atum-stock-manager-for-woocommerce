/* =======================================
   DASHBOARD SALES STATS WIDGET
   ======================================= */

export default class SalesStatsWidget {
	
	constructor() {
		
		$('.stats-data-widget').find('select').change( (evt: JQueryEventObject) => {
			
			evt.stopPropagation(); // Avoid event bubbling to not trigger the layout saving.
			
			const $select: JQuery          = $(evt.currentTarget),
			      $widgetContainer: JQuery = $select.closest('.stats-data-widget');
			
			$widgetContainer.find('.data:visible').hide();
			$widgetContainer.find('[data-value="' + $select.val() + '"]').fadeIn('fast');
			
		});
		
	}
	
}