/* =======================================
   EDITABLE CELL FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import Popover from '../_popover';
import ListTable from './_list-table';

let EditableCell = {
	
	init() {
	
		Popover.init();
		
		Globals.$atumList
		
			// Restore the popovers after the List Table updates.
			.on('atum-table-updated', this.setFieldPopover)
		
			// Destroy the popover when a meta cell is edited.
			.on('atum-edited-cols-input-updated', (evt, $metaCell) => {
				Popover.destroyPopover($metaCell);
			});
			
		// Runs once the popover's set-meta button is clicked.
		$('body').on('click', '.popover button.set', (evt) => {
			
			let $button   = $(evt.target),
				$popover  = $button.closest('.popover'),
				popoverId = $popover.attr('id'),
				$setMeta  = $('[data-popover="' + popoverId + '"]');
			
			if ($setMeta.length) {
				ListTable.maybeAddSaveButton();
				ListTable.updateEditedColsInput($setMeta, $popover);
			}
			
		});
		
	}
	
}

module.exports = EditableCell;