/* =======================================
   BULK ACTIONS FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import ListTable from './_list-table';
import Settings from '../../config/_settings';
import Swal from 'sweetalert2';
import Utils from '../../utils/_utils';
import WPHooks from '../../interfaces/wp.hooks';

export default class BulkActions {

	$bulkButton: JQuery;
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private listTable: ListTable
	) {

		this.$bulkButton = $( '.apply-bulk-action' );
		
		this.globals.$atumList
		
			//
			// Apply Bulk Actions.
			// -------------------
			.on( 'click', '.apply-bulk-action', ( evt: JQueryEventObject ) => {

				if ( ! this.globals.$atumList.find( '.check-column input:checked' ).length ) {

					Swal.fire( {
						title             : this.settings.get( 'noItemsSelected' ),
						text              : this.settings.get( 'selectItems' ),
						icon              : 'info',
						confirmButtonText : this.settings.get( 'ok' ),
						confirmButtonColor: 'var(--primary)',
					} );

				}
				else {
					this.applyBulk();
				}

			} )
			
			//
			// Bulk actions dropdown.
			// ----------------------
			.on( 'change', '.bulkactions select', ( evt: JQueryEventObject ) => {

				const $select: JQuery = $( evt.currentTarget );

				this.updateBulkButton();

				if ( $select.val() !== '-1' ) {
					this.$bulkButton.show();
				}
				else {
					this.$bulkButton.hide();
				}

			} )
			
			//
			// Change the Bulk Buttons texts when selecting boxes.
			// ---------------------------------------------------
			.on( 'change', '.check-column input:checkbox', () => this.updateBulkButton() );
		
	}
	
	/**
	 * Apply a bulk action for the selected rows
	 */
	applyBulk() {

		const bulkAction: string    = this.globals.$atumList.find( '.bulkactions select' ).filter(
			( index: number, elem: Element ) => {
				return $( elem ).val() !== '-1';
			} ).val(),
	        selectedItems: string[] = [];

		this.globals.$atumList.find( 'tbody .check-column input:checkbox' ).filter( ':checked' ).each( ( index: number, elem: Element ) => {
			selectedItems.push( $( elem ).val() );
		} );

		// Allow processing the bulk action externally.
		const processBulkAction: boolean = this.wpHooks.applyFilters( 'atum_listTable_applyBulkAction', true, bulkAction, selectedItems, this );

		if ( processBulkAction ) {
			this.processBulk( bulkAction, selectedItems );
		}
		
	}

	/**
	 * Process the bulk action via Ajax
	 *
	 * @param {string}   bulkAction
	 * @param {string[]} selectedItems
	 * @param {any}      extraData
	 */
	processBulk( bulkAction: string, selectedItems: string[], extraData: any = null ) {

		const data: any = {
			token      : this.settings.get( 'nonce' ),
			action     : 'atum_apply_bulk_action',
			bulk_action: bulkAction,
			ids        : selectedItems,
		};

		if ( extraData ) {
			data[ 'extra_data' ] = extraData;
		}

		$.ajax( {
			url     : window[ 'ajaxurl' ],
			method  : 'POST',
			dataType: 'json',
			data    : data,
			beforeSend: () => {
				this.$bulkButton.prop( 'disabled', true );
				this.listTable.addOverlay();
			},
			success : ( response: any ) => {

				if ( typeof response === 'object' ) {
					const noticeType = response.success ? 'updated' : 'error';
					Utils.addNotice( noticeType, response.data );
				}

				this.$bulkButton.prop( 'disabled', false );

				if ( response.success ) {
					this.$bulkButton.hide();
					this.listTable.updateTable();
					this.$bulkButton.first().trigger( 'atum-list-table-bulk-actions-success', [ bulkAction, selectedItems ] );
				}
				else {
					this.$bulkButton.first().trigger( 'atum-list-table-bulk-actions-error', [ bulkAction, selectedItems ] );
				}

			},
			error   : () => {
				this.$bulkButton.prop( 'disabled', false );
				this.listTable.removeOverlay();
				this.$bulkButton.first().trigger( 'atum-list-table-bulk-actions-error', [ bulkAction, selectedItems ] );
			},
		} );

	}
	
	/**
	 * Update the Bulk Button text depending on the number of checkboxes selected
	 */
	updateBulkButton() {

		const numChecked: number = this.globals.$atumList.find( '.check-column input:checkbox:checked' ).length,
		      buttonText: string = numChecked > 1 ? this.settings.get( 'applyBulkAction' ) : this.settings.get( 'applyAction' );

		this.$bulkButton.text( buttonText );
		
	}
	
}
