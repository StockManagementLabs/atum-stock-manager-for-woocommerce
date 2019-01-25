/* =======================================
   SALES LAST DAYS LIST TABLES
   ======================================= */

import Globals from './_globals'
import Router from './_router'

let SalesLastDays = {
	
	init() {
		
		this.setup()
		
		Globals.$atumList.on('atum-table-updated', this.setup)
	
	},
	
	setup() {
		
		let $selectDays     = $('#sales_last_ndays_val'),
		    selectDaysText  = $selectDays.text(),
		    days            = Array.apply(null, {length: 31}).map(Number.call, Number), // [0, 1, 2, 3 ... 30]
		    $selectableDays = $('<select/>')
		
		days.shift()
		
		for (let i in days) {
			$selectableDays.append($('<option/>').html(days[i]))
		}
		
		$selectDays.html('<span class="textvalue">' + selectDaysText + '</span>')
		$selectDays.append($selectableDays)
		$selectDays.find('select').hide().val(selectDaysText)
		
		$selectableDays.change( (evt) => {
			
			let $select = $(evt.target)
			
			$selectDays.find('.textvalue').text($select.val())
			$select.hide()
			$selectDays.find('.select2').hide()
			$selectDays.find('.textvalue').show()
			
			$.address.parameter('sold_last_days', parseInt($select.val()))
			Router.updateHash()
			
		})
		
		$selectDays.find('.textvalue').click( (evt) => {
			$(evt.target).hide()
			// $selectDays.find('select').show();
			$selectDays.find('select').select2()
		})
		
	}
}

module.exports = SalesLastDays