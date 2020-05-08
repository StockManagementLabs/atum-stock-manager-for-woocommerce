/* =======================================
   DASHBOARD SALES STATS WIDGET
   ======================================= */

export default class SalesStatsWidget {
	
	constructor(
		private $widgetsContainer: JQuery
	) {
		
		$('.stats-data-widget').find('select').change( (evt: JQueryEventObject) => {
			
			evt.stopPropagation(); // Avoid event bubbling to not trigger the layout saving.
			
			const $select: JQuery          = $(evt.currentTarget),
			      $widget: JQuery = $select.closest('.stats-data-widget');

			this.loadSales( $widget, $select.val() );
			
		});
		
	}

	/**
	 * Load the sales stats (if already loaded just displays them, if not, it'll request them via Ajax)
	 *
	 * @param {JQuery} $widget
	 * @param {string} filter
	 */
	loadSales( $widget: JQuery, filter: string ) {

		const $salesData: JQuery = $widget.find(`[data-value="${ filter }"]`);

		// Just show the data.
		if ( 'yes' === $salesData.data('updated') ) {
			$widget.find('.data').not('.hidden').addClass('hidden');
			$salesData.fadeIn('fast');
		}
		// Load the data.
		else {

			$.ajax( {
				url       : window[ 'ajaxurl' ],
				method    : 'POST',
				data      : {
					token : this.$widgetsContainer.data( 'nonce' ),
					action: 'atum_dashboard_load_sales',
					widget: $widget.data( 'type' ),
					filter: filter,
				},
				dataType  : 'json',
				beforeSend: () => $widget.addClass( 'overlay' ),
				success   : ( response: any ) => {

					if ( typeof response === 'object' && response.success ) {

						const data: any = response.data;

						if ( Object.keys( data ).length ) {

							$.each( data, ( key: string, value: any ) => {

								if ( $salesData.find(`[data-prop="${ key }"]`).length ) {
									$salesData.find(`[data-prop="${ key }"]`).html( value );
								}

							} );

							$widget.find('.data').not('.hidden').addClass('hidden');
							$salesData.removeClass('hidden');

						}

					}
					else {
						console.error( response );
					}

					$widget.removeClass( 'overlay' );

				},
			} );

		}

	}
	
}