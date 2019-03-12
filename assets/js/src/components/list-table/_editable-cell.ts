/* =======================================
   EDITABLE CELL FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import Popover from '../_popover';
import ListTable from './_list-table';

export default class EditableCell {
	
	constructor(
		private globals: Globals,
		private popover: Popover,
		private listTable: ListTable
	) {
		
		this.globals.$atumList
		
			// Restore the popovers after the List Table updates.
			.on('atum-table-updated', () => {
				this.popover.setFieldPopover();
			})
		
			// Destroy the popover when a meta cell is edited.
			.on('atum-edited-cols-input-updated', (evt: any, $metaCell: JQuery) => {
				this.popover.destroyPopover($metaCell);
			});
			
		
		// Runs once the popover's set-meta button is clicked.
		$('body').on('click', '.popover button.set', (evt: JQueryEventObject) => {
			
			let $button   = $(evt.currentTarget),
				$popover  = $button.closest('.popover'),
				popoverId = $popover.attr('id'),
				$setMeta  = $('[data-popover="' + popoverId + '"]');
			
			if ($setMeta.length) {
				this.listTable.maybeAddSaveButton();
				this.listTable.updateEditedColsInput($setMeta, $popover);
			}
			
		});
		
	}
	
}
