/* =======================================
   BULK ACTIONS FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import ListTable from './_list-table';
import Settings from '../../config/_settings';
import { Utils } from '../../utils/_utils';

export default class BulkActions {

	swal: any = window[ 'swal' ];
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private listTable: ListTable
	) {
		
		this.globals.$atumList
		
			//
			// Apply Bulk Actions.
			// -------------------
			.on( 'click', '.apply-bulk-action', ( evt: JQueryEventObject ) => {

				const $bulkButton: JQuery = $( evt.currentTarget );

				if ( ! this.globals.$atumList.find( '.check-column input:checked' ).length ) {

					this.swal( {
						title             : this.settings.get( 'noItemsSelected' ),
						text              : this.settings.get( 'selectItems' ),
						type              : 'info',
						confirmButtonText : this.settings.get( 'ok' ),
						confirmButtonColor: 'var(--primary)',
					} );

				}
				else {
					this.applyBulk( $bulkButton );
				}

			} )
			
			//
			// Bulk actions dropdown.
			// ----------------------
			.on( 'change', '.bulkactions select', ( evt: JQueryEventObject ) => {

				const $select: JQuery     = $( evt.currentTarget ),
				      $bulkButton: JQuery = $select.siblings( '.apply-bulk-action' );

				this.updateBulkButton( $bulkButton );

				if ( $select.val() !== '-1' ) {
					$bulkButton.show();
				}
				else {
					$bulkButton.hide();
				}

			} )
			
			//
			// Change the Bulk Buttons texts when selecting boxes.
			// ---------------------------------------------------
			.on( 'change', '.check-column input:checkbox', () => this.updateBulkButton( $( '.apply-bulk-action' ) ) );
		
	}
	
	/**
	 * Apply a bulk action for the selected rows
	 *
	 * @param {JQuery} $bulkButton
	 */
	applyBulk( $bulkButton: JQuery ) {

		const bulkAction: string    = this.globals.$atumList.find( '.bulkactions select' ).filter(
			( index: number, elem: Element ) => {
				return $( elem ).val() !== '-1';
			} ).val(),
	        selectedItems: string[] = [];

		this.globals.$atumList.find( 'tbody .check-column input:checkbox' ).filter( ':checked' ).each( ( index: number, elem: Element ) => {
			selectedItems.push( $( elem ).val() );
		} );

		$.ajax( {
			url       : window[ 'ajaxurl' ],
			method    : 'POST',
			dataType  : 'json',
			data      : {
				token      : this.settings.get( 'nonce' ),
				action     : 'atum_apply_bulk_action',
				bulk_action: bulkAction,
				ids        : selectedItems,
			},
			beforeSend: () => {
				$bulkButton.prop( 'disabled', true );
				this.listTable.addOverlay();
			},
			success   : ( response: any ) => {

				if ( typeof response === 'object' ) {
					const noticeType = response.success ? 'updated' : 'error';
					Utils.addNotice( noticeType, response.data );
				}

				$bulkButton.prop( 'disabled', false );

				if ( response.success ) {
					$bulkButton.hide();
					this.listTable.updateTable();
					$bulkButton.trigger( 'atum-list-table-bulk-actions-success', [ bulkAction, selectedItems ] );
				}
				else {
					$bulkButton.trigger( 'atum-list-table-bulk-actions-error', [ bulkAction, selectedItems ] );
				}

			},
			error     : () => {
				$bulkButton.prop( 'disabled', false );
				this.listTable.removeOverlay();
				$bulkButton.trigger( 'atum-list-table-bulk-actions-error', [ bulkAction, selectedItems ] );
			},
		} );
		
	}
	
	/**
	 * Update the Bulk Button text depending on the number of checkboxes selected
	 *
	 * @param {JQuery} $bulkButton
	 */
	updateBulkButton( $bulkButton: JQuery ) {

		const numChecked: number = this.globals.$atumList.find( '.check-column input:checkbox:checked' ).length,
		      buttonText: string = numChecked > 1 ? this.settings.get( 'applyBulkAction' ) : this.settings.get( 'applyAction' );

		$bulkButton.text( buttonText );
		
	}
	
}
