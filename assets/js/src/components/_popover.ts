/* =======================================
   POPOVER
   ======================================= */

import Settings from '../config/_settings';
import DateTimePicker from './_date-time-picker';
import { Utils } from '../utils/_utils';
import '../../vendor/bootstrap3-custom.min'; // TODO: USE BOOTSTRAP 4

export default class Popover {
	
	constructor(
		private settings: Settings,
		private dateTimePicker?: DateTimePicker
	) {
		
		// Init popovers.
		this.setFieldPopover();
		
		// Hide any other opened popover before opening a new one.
		$('body').click( (evt: JQueryEventObject) => {
			
			let $target: JQuery   = $(evt.target),
			    // If we are clicking on a editable cell, get the other opened popovers, if not, get all them all.
			    $metaCell: JQuery = $target.hasClass('set-meta') ? $('.set-meta').not($target) : $('.set-meta');
			
			// Do not hide any popover if the click is being performed within one.
			if ($target.is('.popover') || $target.closest('.popover.in').length) {
				return;
			}
			
			// Get only the cells with an opened popover.
			$metaCell = $metaCell.filter( (index: number, elem: Element) => {
				return typeof $(elem).data('bs.popover') !== 'undefined' && typeof $(elem).data('bs.popover').inState !== 'undefined' && $(elem).data('bs.popover').inState.click === true;
			});
			
			this.destroyPopover($metaCell);
			
		});
		
	}
	
	/**
	 * Enable "Set Field" popovers
	 */
	setFieldPopover($metaCells?: JQuery) {
		
		if (!$metaCells) {
			$metaCells = $('.set-meta');
		}
		
		// Set meta value for listed products.
		$metaCells.each( (index: number, elem: Element) => {
			this.bindPopover($(elem));
		});
		
		// Focus on the input field and set a reference to the popover to the editable column.
		$metaCells.on('shown.bs.popover', (evt: JQueryEventObject) => {
			
			let $metaCell: JQuery      = $(evt.currentTarget),
				$activePopover: JQuery = $('.popover.in');
			
			$activePopover.find('.meta-value').focus().select();
			
			if (this.dateTimePicker) {
				
				let $dateInputs: JQuery = $activePopover.find('.bs-datepicker');
				
				if ($dateInputs.length) {
					this.dateTimePicker.addDateTimePickers($dateInputs);
				}
				
			}
			
			// Click the "Set" button when hitting enter on an input field.
			$activePopover.find('input').on('keyup', (evt: JQueryEventObject) => {
				
				// Enter key.
				if (13 === evt.which) {
					$activePopover.find('.set').click();
				}
				// ESC key.
				else if (27 === evt.which) {
					this.destroyPopover($metaCell);
				}
				
			});
			
			$metaCell.attr('data-popover', $activePopover.attr('id'));
			
		});
		
	}
	
	/**
	 * Bind the editable cell's popovers
	 *
	 * @param jQuery $metaCell The cell where the popover will be attached.
	 */
	bindPopover($metaCell: JQuery) {
		
		let symbol: string    = $metaCell.data('symbol') || '',
		    cellName: string  = $metaCell.data('cell-name') || '',
		    inputType: string = $metaCell.data('input-type') || 'number',
		    inputAtts: any    = {
			    type : inputType || 'number',
			    value: $metaCell.text(),
			    class: 'meta-value',
			    min  : '',
			    step : '',
		    };
		
		if (inputType === 'number' || $metaCell.text() === '-') {
			
			let numericValue: number = Math.abs(<number>Utils.unformat($metaCell.text(), this.settings.get('currencyFormatDecimalSeparator')));
			
			inputAtts.value = isNaN(numericValue) ? 0 : numericValue;
			
		}
		
		if ( inputType === 'number' ) {
			inputAtts.min = symbol ? '0' : ''; // The minimum value for currency fields is 0.
			inputAtts.step = symbol ? '0.1' : '1'; // Allow decimals only for the currency fields for now.
		}
		
		let $input: JQuery       = $('<input />', inputAtts),
		    $setButton: JQuery   = $('<button />', {
			    type : 'button',
			    class: 'set btn btn-primary button-small',
			    text : this.settings.get('setButton'),
		    }),
		    extraMeta: any       = $metaCell.data('extra-meta'),
		    popoverClass: string = '',
		    $extraFields: JQuery = null;

		// Check whether to add extra fields to the popover.
		if (typeof extraMeta !== 'undefined') {
			
			popoverClass = ' with-meta';
			$extraFields = $('<hr>');
			
			$.each(extraMeta, (index: number, metaAtts: any) => {
				$extraFields = $extraFields.add($('<input />', metaAtts));
			});
			
		}
		
		let $content = $extraFields ? $input.add($extraFields).add($setButton) : $input.add($setButton);
		
		// Create the meta edit popover.
		(<any>$metaCell).popover({
			title    : this.settings.get('setValue') ? this.settings.get('setValue').replace('%%', cellName) : cellName,
			content  : $content,
			html     : true,
			template : `<div class="popover ${popoverClass}" role="tooltip">
							<div class="popover-arrow"></div>
							<h3 class="popover-title"></h3>
							<div class="popover-content"></div>
						</div>`,
			placement: 'bottom',
			trigger  : 'click',
			container: 'body',
		});
		
	}
	
	/**
	 * Destroy a popover attached to a specified table cell
	 *
	 * @param jQuery $metaCell The table cell where is attached the visible popover.
	 */
	destroyPopover($metaCell?: JQuery) {
		
		// If not passing the popover to destroy, try to find out the currently active.
		if (!$metaCell || !$metaCell.length) {
			$metaCell = $('.set-meta[data-popover]');
		}
		
		if ($metaCell.length) {
			
			(<any>$metaCell).popover('destroy');
			$metaCell.removeAttr('data-popover');
			
			// Give a small lapse to complete the 'fadeOut' animation before re-binding.
			setTimeout( () => this.setFieldPopover($metaCell), 300);
			
		}
		
	}
	
}
