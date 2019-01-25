/* =======================================
   BULK ACTIONS FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings'
import Globals from './_globals'
import ListTable from './_list-table'
import Utils from '../../utils/_utils'

let BulkActions = {
	
	init() {
		
		let self = this
		
		Globals.$atumList
		
			//
			// Apply Bulk Actions.
			// -------------------
			.on('click', '.apply-bulk-action', () => {
				
				if (!Globals.$atumList.find('.check-column input:checked').length) {
					
					swal({
						title             : Settings.get('noItemsSelected'),
						text              : Settings.get('selectItems'),
						type              : 'info',
						confirmButtonText : Settings.get('ok'),
						confirmButtonColor: '#00b8db',
					})
					
				}
				else {
					self.applyBulk()
				}
				
			})
			
			//
			// Bulk actions dropdown.
			// ----------------------
			.on('change', '.bulkactions select', (evt) => {
				
				self.updateBulkButton()
				
				if ($(evt.target).val() !== '-1') {
					$('.apply-bulk-action').show()
				}
				else {
					$('.apply-bulk-action').hide()
				}
			})
			
			//
			// Change the Bulk Button text when selecting boxes.
			// -------------------------------------------------
			.on('change', '.check-column input:checkbox', () => {
				self.updateBulkButton()
			})
		
	},
	
	/**
	 * Apply a bulk action for the selected rows
	 */
	applyBulk() {
		
		let $bulkButton   = $('.apply-bulk-action'),
		    bulkAction    = Globals.$atumList.find('.bulkactions select').filter( (index, elem) => {
			    return $(elem).val() !== '-1'
		    }).val(),
		    selectedItems = []
		
		Globals.$atumList.find('tbody .check-column input:checkbox').filter(':checked').each( (index, elem) => {
			selectedItems.push($(elem).val())
		})
		
		$.ajax({
			url       : ajaxurl,
			method    : 'POST',
			dataType  : 'json',
			data      : {
				token      : Settings.get('nonce'),
				action     : 'atum_apply_bulk_action',
				bulk_action: bulkAction,
				ids        : selectedItems,
			},
			beforeSend: () => {
				$bulkButton.prop('disabled', true)
				ListTable.addOverlay()
			},
			success   : (response) => {
				
				if (typeof response === 'object') {
					const noticeType = response.success ? 'updated' : 'error'
					Utils.addNotice(noticeType, response.data)
				}
				
				$bulkButton.prop('disabled', false)
				
				if (response.success) {
					$bulkButton.hide()
					ListTable.updateTable()
				}
				
			},
			error     : () => {
				$bulkButton.prop('disabled', false)
				ListTable.removeOverlay()
			},
		})
		
	},
	
	/**
	 * Update the Bulk Button text depending on the number of checkboxes selected
	 */
	updateBulkButton() {
		
		let numChecked = Globals.$atumList.find('.check-column input:checkbox:checked').length,
		    buttonText = numChecked > 1 ? Settings.get('applyBulkAction') : Settings.get('applyAction')
		
		$('.apply-bulk-action').text(buttonText)
		
	},
	
}

module.exports = BulkActions