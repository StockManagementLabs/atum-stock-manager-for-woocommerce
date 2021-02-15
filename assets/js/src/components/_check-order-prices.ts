/* =======================================
 CHECK ORDER PRICES
 ======================================= */

import Tooltip from './_tooltip';
import Settings from '../config/_settings';

export default class CheckOrderPrices {

	$checkPricesButton: JQuery;
	$checkingResultWrapper: JQuery;

	constructor(
		private settings: Settings,
		private tooltip: Tooltip
	) {

		this.$checkPricesButton = $( `<button id="atum-check-order-prices" class="page-title-action">${ this.settings.get( 'checkOrderPrices' ) }</button>` );
		this.$checkingResultWrapper = $( '<span id="atum-checking-result" />' );

		this.$checkPricesButton.insertAfter( $( '.page-title-action' ).last() );
		this.$checkingResultWrapper.insertAfter( this.$checkPricesButton );

		this.bindEvents();
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		this.$checkPricesButton.click( ( evt: JQueryEventObject ) => {

			evt.preventDefault();

			$.ajax( {
				url       : window[ 'ajaxurl' ],
				method    : 'post',
				dataType  : 'json',
				data      : {
					action      : 'atum_check_order_prices',
					token       : this.settings.get( 'nonce' ),
					query_string: location.search,
				},
				beforeSend: () => {

					$( '#atum-mismatching-orders' ).remove();

					this.$checkingResultWrapper
						.addClass( 'checking' )
						.html( this.settings.get( 'checkingPrices' ) );

				},
				success   : ( response: any ) => {

					this.$checkingResultWrapper.removeClass( 'checking' ).empty();

					if ( response.success ) {
						const $resultBadge: JQuery = $( response.data );
						$resultBadge.insertAfter( this.$checkPricesButton );
						this.tooltip.addTooltips( $resultBadge.parent() );
					}

				},
			} );

		} );

	}

}