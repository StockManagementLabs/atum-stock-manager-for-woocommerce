/* =======================================
   DATE TIME PICKER
   ======================================= */

import Settings from '../config/_settings';
import moment from 'moment/min/moment.min';

let DateTimePicker = {
	
	defaults : {
		format           : Settings.get('dateFormat'),
		useCurrent       : false,
		showClose        : true,
		icons            : {
			time    : 'atum-icon atmi-clock',
			date    : 'atum-icon atmi-calendar-full',
			up      : 'atum-icon atmi-chevron-up',
			down    : 'atum-icon atmi-chevron-down',
			previous: 'atum-icon atmi-chevron-left',
			next    : 'atum-icon atmi-chevron-right',
			today   : 'atum-icon atmi-frame-expand',
			clear   : 'atum-icon atmi-trash',
			close   : 'atum-icon atmi-cross',
		},
		minDate          : moment(),
		showClear        : true,
		showTodayButton  : true,
		widgetPositioning: {
			horizontal: 'right',
			vertical:   'bottom',
		},
		tooltips         : {
			today          : Settings.get('goToToday'),
			clear          : Settings.get('clearSelection'),
			close          : Settings.get('closePicker'),
			selectMonth    : Settings.get('selectMonth'),
			prevMonth      : Settings.get('prevMonth'),
			nextMonth      : Settings.get('nextMonth'),
			selectYear     : Settings.get('selectYear'),
			prevYear       : Settings.get('prevYear'),
			nextYear       : Settings.get('nextYear'),
			selectDecade   : Settings.get('selectDecade'),
			prevDecade     : Settings.get('prevDecade'),
			nextDecade     : Settings.get('nextDecade'),
			prevCentury    : Settings.get('prevCentury'),
			nextCentury    : Settings.get('nextCentury'),
			incrementHour  : Settings.get('incrementHour'),
			pickHour       : Settings.get('pickHour'),
			decrementHour  : Settings.get('decrementHour'),
			incrementMinute: Settings.get('incrementMinute'),
			pickMinute     : Settings.get('pickMinute'),
			decrementMinute: Settings.get('decrementMinute'),
			incrementSecond: Settings.get('incrementSecond'),
			pickSecond     : Settings.get('pickSecond'),
			decrementSecond: Settings.get('decrementSecond'),
		},
	},
	
	/**
	 * Add the date time pickers
	 *
	 * @param jQuery $selector
	 * @param Object opts
	 */
	addDateTimePickers($selector, opts) {
	
		let self = this;
		
		$selector.each( (index, elem) => {
			
			let $dateTimePicker = $(elem),
			    mergedOpts      = {};
			
			// Extend the date picker options with data options.
			$.extend(mergedOpts, self.defaults, $dateTimePicker.data() || {}, opts || {});
			
			$dateTimePicker.datetimepicker(mergedOpts);
			
		})
		.on('dp.change', (evt) => {
			
			const label = typeof evt.date === 'object' ? evt.date.format(Settings.get('dateFormat')) : Settings.get('none');
			$(this).siblings('.field-label').addClass('unsaved').text(label);
			
		})
		.on('dp.show', (evt) => {
			
			// Hide others opened.
			$selector.not($(evt.target)).filter( (index, elem) => {
				
				if ($(elem).children('.bootstrap-datetimepicker-widget').length) {
					return true;
				}
				
				return false;
				
			}).each( (index, elem) => {
				$(elem).data('DateTimePicker').hide();
			});
			
		});
		
	},
	
}

module.exports = DateTimePicker;