/* =======================================
   SALES LAST DAYS LIST TABLES
   ======================================= */

import Globals from './_globals';
import Router from './_router';

export default class SalesLastDays {
	
	globals: Globals;
	router: Router;
	
	constructor(globalsObj: Globals, routerObj: Router) {
		
		this.globals = globalsObj;
		this.router = routerObj;
		
		this.setup();
		
		this.globals.$atumList.on('atum-table-updated', () => this.setup());
	
	}
	
	setup() {
		
		let $selectDays: any        = $('#sales_last_ndays_val'),
		    selectDaysText: string  = $selectDays.text(),
		    days: number[]          = Array.apply(null, {length: 31}).map(Number.call, Number), // [0, 1, 2, 3 ... 30]
		    $selectableDays: JQuery = $('<select/>');
		
		days.shift();
		
		for (let i in days) {
			$selectableDays.append( $('<option/>').html( days[i].toString() ) );
		}
		
		$selectDays.html('<span class="textvalue">' + selectDaysText + '</span>');
		$selectDays.append($selectableDays);
		$selectDays.find('select').hide().val(selectDaysText);
		
		$selectableDays.change( (evt: any) => {
			
			let $select: JQuery = $(evt.currentTarget);
			
			$selectDays.find('.textvalue').text($select.val());
			$select.hide();
			$selectDays.find('.select2').hide();
			$selectDays.find('.textvalue').show();
			
			$.address.parameter('sold_last_days', parseInt($select.val()));
			this.router.updateHash();
			
		});
		
		$selectDays.find('.textvalue').click( (evt: any) => {
			
			$(evt.currentTarget).hide();
			// $selectDays.find('select').show();
			
			// TODO: USE ENHANCEDSELECT COMPONENT?
			const $select = $selectDays.find('select');
			$select.select2();
			$select.siblings('.select2-container').addClass('atum-select2');
			
		});
		
	}
	
}
