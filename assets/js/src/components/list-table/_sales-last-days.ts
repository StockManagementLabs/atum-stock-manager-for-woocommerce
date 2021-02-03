/* =======================================
   SALES LAST DAYS LIST TABLES
   ======================================= */

import EnhancedSelect from '../_enhanced-select';
import Globals from './_globals';
import Router from './_router';
import WPHooks from '../../interfaces/wp.hooks';

export default class SalesLastDays {

	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private globals: Globals,
		private router: Router,
		private enhancedSelect: EnhancedSelect
	) {
		
		this.setup();
		this.addHooks();
	
	}
	
	setup() {

		let $selectDays: any        = $( '#sales_last_ndays_val' ),
		    selectDaysText: string  = $selectDays.text().trim(),
		    days: number[]          = Array.apply( null, { length: 31 } ).map( Number.call, Number ), // [0, 1, 2, 3 ... 30]
		    $selectableDays: JQuery = $( '<select/>' );

		days.shift();

		for ( let i in days ) {
			$selectableDays.append( $( '<option/>' ).html( days[ i ].toString() ) );
		}

		$selectDays.html( '<span class="textvalue">' + selectDaysText + '</span>' );
		$selectDays.append( $selectableDays );
		$selectDays.find( 'select' ).hide().val( selectDaysText );

		$selectDays.click( ( evt: JQueryEventObject ) => {
			evt.preventDefault();
			evt.stopImmediatePropagation();
		} );

		$selectableDays.change( ( evt: JQueryEventObject ) => {

			let $select: JQuery = $( evt.currentTarget );

			$selectDays.find( '.textvalue' ).text( $select.val() );
			$select.hide();
			$selectDays.find( '.select2' ).hide();
			$selectDays.find( '.textvalue' ).show();

			$.address.parameter( 'sold_last_days', parseInt( $select.val() ) );
			this.router.updateHash();

		} );

		setTimeout( () => {
			$selectDays.find( '.textvalue' ).click( ( evt: JQueryEventObject ) => {

				evt.preventDefault();
				evt.stopPropagation();
				$( evt.currentTarget ).hide();
				this.enhancedSelect.doSelect2( $selectDays.find( 'select' ) );

			} );
		}, 100 );

	}

	/**
	 * Add hooks
	 */
	addHooks() {

		// Re-setup the component after the list table is updated.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => this.setup() );

	}
	
}
