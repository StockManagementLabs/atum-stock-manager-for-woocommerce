/* =======================================
   BULK ACTIONS FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';
import ListTable from './_list-table';
import { Utils } from '../../utils/_utils';

export default class BulkActions {
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private listTable: ListTable
	) {
		
		this.globals.$atumList
		
			//
			// Apply Bulk Actions.
			// -------------------
			.on('click', '.apply-bulk-action', () => {
				
				if (!this.globals.$atumList.find('.check-column input:checked').length) {
					
					const swal: any = window['swal'];
					
					swal({
						title             : this.settings.get('noItemsSelected'),
						text              : this.settings.get('selectItems'),
						type              : 'info',
						confirmButtonText : this.settings.get('ok'),
						confirmButtonColor: '#00b8db',
					});
					
				}
				else {
					this.applyBulk();
				}
				
			})
			
			//
			// Bulk actions dropdown.
			// ----------------------
			.on('change', '.bulkactions select', (evt: JQueryEventObject) => {
				
				this.updateBulkButton();
				
				if ($(evt.currentTarget).val() !== '-1') {
					$('.apply-bulk-action').show();
				}
				else {
					$('.apply-bulk-action').hide();
				}
				
			})
			
			//
			// Change the Bulk Button text when selecting boxes.
			// -------------------------------------------------
			.on('change', '.check-column input:checkbox', () => {
				this.updateBulkButton();
			});
		
	}
	
	/**
	 * Apply a bulk action for the selected rows
	 */
	applyBulk() {
		
		let $bulkButton: JQuery     = $('.apply-bulk-action'),
		    bulkAction: string      = this.globals.$atumList.find('.bulkactions select').filter((index: number, elem: Element) => {
			    return $(elem).val() !== '-1'
		    }).val(),
		    selectedItems: string[] = [];
		
		this.globals.$atumList.find('tbody .check-column input:checkbox').filter(':checked').each( (index: number, elem: Element) => {
			selectedItems.push( $(elem).val() );
		});
		
		$.ajax({
			url       : window['ajaxurl'],
			method    : 'POST',
			dataType  : 'json',
			data      : {
				token      : this.settings.get('nonce'),
				action     : 'atum_apply_bulk_action',
				bulk_action: bulkAction,
				ids        : selectedItems,
			},
			beforeSend: () => {
				$bulkButton.prop('disabled', true);
				this.listTable.addOverlay();
			},
			success   : (response: any) => {
				
				if (typeof response === 'object') {
					const noticeType = response.success ? 'updated' : 'error';
					Utils.addNotice(noticeType, response.data);
				}
				
				$bulkButton.prop('disabled', false);
				
				if (response.success) {
					$bulkButton.hide();
					this.listTable.updateTable();
				}
				
			},
			error     : () => {
				$bulkButton.prop('disabled', false);
				this.listTable.removeOverlay();
			},
		})
		
	}
	
	/**
	 * Update the Bulk Button text depending on the number of checkboxes selected
	 */
	updateBulkButton() {
		
		let numChecked: number = this.globals.$atumList.find('.check-column input:checkbox:checked').length,
		    buttonText: string = numChecked > 1 ? this.settings.get('applyBulkAction') : this.settings.get('applyAction');
		
		$('.apply-bulk-action').text(buttonText);
		
	}
	
}
