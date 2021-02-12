/* =======================================
 CHECK ORDER PRICES
 ======================================= */

import Settings from '../config/_settings';

export default class CheckOrderPrices {

	$checkPricesButton: JQuery;
	$checkingResultWrapper: JQuery;

	constructor(
		private settings: Settings
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
				data      : {},
				beforeSend: () => {

					this.$checkingResultWrapper
						.addClass( 'checking' )
						.html( this.settings.get( 'checkingPrices' ) );

				},
				success   : ( response: any ) => {

					this.$checkingResultWrapper.removeClass( 'checking' ).empty();
					console.log( response );

				},
			} );

		} );

	}

}