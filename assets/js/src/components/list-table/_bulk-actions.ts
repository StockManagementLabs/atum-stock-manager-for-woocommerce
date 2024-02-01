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

	noOptionValue: string = '-1';
	$bulkButton: JQuery;
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private listTable: ListTable
	) {

		this.$bulkButton = $( '.apply-bulk-action' );
		this.bindEvents();

		// Add this component to the global scope so can be accessed by other add-ons.
		if ( ! window.hasOwnProperty( 'atum' ) ) {
			window[ 'atum' ] = {};
		}

		window[ 'atum' ][ 'BulkActions' ] = this;
		
	}

	/**
	 * Bind events
	 */
	bindEvents() {

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

				const $select: JQuery  = $( evt.currentTarget ),
				      selected: string = $select.val();

				this.$bulkButton = $( '.apply-bulk-action' ); // If the table's DOM has been updated, we must reassign the bulk button.

				// Sync the top and bottom selects.
				this.globals.$atumList.find( '.bulkactions select' ).not( $select ).val( selected ).trigger( 'change.select2' );
				this.updateBulkButton();
				this.$bulkButton.toggle( selected !== this.noOptionValue );

			} )

			//
			// Change the Bulk Buttons texts when selecting boxes.
			// ---------------------------------------------------
			.on( 'change', '.check-column input:checkbox', () => this.updateBulkButton() );

	}

	addHooks() {

		// Allow resetting the bulk fields externally.
		this.wpHooks.addAction( 'atum_listTable_resetBulkFields', 'atum', () => this.resetBulkFields() );

	}
	
	/**
	 * Apply a bulk action for the selected rows
	 */
	applyBulk() {

		const $bulkSelect: JQuery     = this.globals.$atumList.find( '.bulkactions select' ),
		      bulkAction: string      = $bulkSelect.filter( ( index: number, elem: Element ) => {
			      return $( elem ).val() !== this.noOptionValue;
		      } ).val(),
		      selectedItems: string[] = [];

		this.globals.$atumList.find( 'tbody .check-column input:checkbox' ).filter( ':checked' ).each( ( index: number, elem: Element ) => {
			selectedItems.push( $( elem ).val() );
		} );

		// Allow processing the bulk action externally.
		const allowProcessBulkAction: boolean = this.wpHooks.applyFilters( 'atum_listTable_applyBulkAction', true, bulkAction, selectedItems, this );

		if ( allowProcessBulkAction ) {
			this.processBulk( bulkAction, selectedItems );

			// Reset the bulk action select.
			this.resetBulkFields();
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
			action     : 'atum_apply_bulk_action',
			security   : this.settings.get( 'nonce' ),
			bulk_action: bulkAction,
			ids        : selectedItems,
		};

		extraData = this.wpHooks.applyFilters( 'atum_listTable_bulkAction_extraData', extraData, bulkAction );

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
					Utils.addNotice( response.success ? 'success' : 'error', response.data );
				}

				this.$bulkButton.prop( 'disabled', false );

				if ( response.success ) {
					this.$bulkButton.hide();
					this.listTable.updateTable();
					this.$bulkButton.first().trigger( 'atum-list-table-bulk-actions-success', [ bulkAction, selectedItems ] );
					this.wpHooks.doAction( 'atum_listTable_bulkAction_success', bulkAction, selectedItems );
				}
				else {
					this.listTable.removeOverlay();
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
		      buttonText: string = this.settings.get( numChecked > 1 ? 'applyBulkAction' : 'applyAction' );

		this.$bulkButton.text( buttonText );
		
	}

	/**
	 * Reset bulk fields
	 */
	resetBulkFields() {
		this.globals.$atumList.find( '.bulkactions select' ).val( this.noOptionValue ).change();
	}
	
}
