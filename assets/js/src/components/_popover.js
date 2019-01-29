/* =======================================
   POPOVER
   ======================================= */

import Settings from '../config/_settings';
import DateTimePicker from './_date-time-picker';

let Popover = {
	
	init() {
		
		let self = this;
		
		// Init popovers.
		this.setFieldPopover();
		
		// Hide any other opened popover before opening a new one.
		$('body').click( (evt) => {
			
			let $target   = $(evt.target),
			    // If we are clicking on a editable cell, get the other opened popovers, if not, get all them all.
			    $metaCell = $target.hasClass('set-meta') ? $('.set-meta').not($target) : $('.set-meta');
			
			// Get only the cells with an opened popover.
			$metaCell = $metaCell.filter( (index, elem) => {
				return $(elem).data('bs.popover') !== 'undefined' && ($(elem).data('bs.popover').inState || false) && $(elem).data('bs.popover').inState.click === true;
			});
			
			self.destroyPopover($metaCell);
			
		});
		
	},
	
	/**
	 * Enable "Set Field" popovers
	 */
	setFieldPopover($metaCells) {
		
		let self = this;
		
		if (typeof $metaCells === 'undefined') {
			$metaCells = $('.set-meta');
		}
		
		// Set meta value for listed products.
		$metaCells.each( (index, elem) => {
			self.bindPopover($(elem));
		});
		
		// Focus on the input field and set a reference to the popover to the editable column.
		$metaCells.on('shown.bs.popover', () => {
			
			let $activePopover = $('.popover.in');
			$activePopover.find('.meta-value').focus();
			
			let $dateInputs = $activePopover.find('.datepicker');
			
			if ( $dateInputs.length) {
				DateTimePicker.addDateTimePickers($dateInputs);
			}
			
			$(this).attr('data-popover', $activePopover.attr('id'));
			
		});
		
	},
	
	/**
	 * Bind the editable cell's popovers
	 *
	 * @param jQuery $metaCell The cell where the popover will be attached.
	 */
	bindPopover($metaCell) {
		
		let symbol    = $metaCell.data('symbol') || '',
		    cellName  = $metaCell.data('cell-name') || '',
		    inputType = $metaCell.data('input-type') || 'number',
		    inputAtts = {
			    type : $metaCell.data('input-type') || 'number',
			    value: $metaCell.data('input-type') === 'number' || $metaCell.text() === '-' ? $metaCell.text().replace(symbol, '').replace('-', '') : $metaCell.text(),
			    class: 'meta-value',
		    };
		
		if (inputType === 'number') {
			inputAtts.min = '0';
			// Allow decimals only for the pricing fields for now.
			inputAtts.step = symbol ? '0.1' : '1';
		}
		
		let $input       = $('<input />', inputAtts),
		    $setButton   = $('<button />', {type: 'button', class: 'set btn btn-primary button-small', text: Settings.get('setButton')}),
		    extraMeta    = $metaCell.data('extra-meta'),
		    $extraFields = '',
		    popoverClass = '';
		
		// Check whether to add extra fields to the popover.
		if (typeof extraMeta !== 'undefined') {
			
			popoverClass = ' with-meta';
			$extraFields = $('<hr>');
			
			$.each(extraMeta, (index, metaAtts) => {
				$extraFields = $extraFields.add($('<input />', metaAtts));
			});
			
		}
		
		let $content = $extraFields.length ? $input.add($extraFields).add($setButton) : $input.add($setButton);
		
		// Create the meta edit popover.
		$metaCell.popover({
			title    : Settings.get('setValue') ? Settings.get('setValue').replace('%%', cellName) : cellName,
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
		
	},
	
	/**
	 * Destroy a popover attached to a specified table cell
	 *
	 * @param jQuery $metaCell The table cell where is attached the visible popover.
	 */
	destroyPopover($metaCell) {
		
		if ($metaCell.length) {
			
			let self = this;
			$metaCell.popover('destroy');
			$metaCell.removeAttr('data-popover');
			
			// Give a small lapse to complete the 'fadeOut' animation before re-binding.
			setTimeout( () => {
				self.setFieldPopover($metaCell);
			}, 300);
			
		}
		
	},
	
}

module.exports = Popover;