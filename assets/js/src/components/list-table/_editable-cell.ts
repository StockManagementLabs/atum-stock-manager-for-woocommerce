/* =======================================
   EDITABLE CELL FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import TableCellPopovers from '../_table-cell-popovers';
import ListTable from './_list-table';
import WPHooks from '../../interfaces/wp.hooks';
import AddSupplierModal from './_add_supplier_modal';
import Settings from '../../config/_settings';

export default class EditableCell {

	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private popover: TableCellPopovers,
		private listTable: ListTable
	) {
		
		this.bindEvents();
		this.addHooks();
		
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// Destroy the popover when a meta cell is edited.
		this.globals.$atumList.on( 'atum-edited-cols-input-updated', ( evt: any, $metaCell: JQuery ) => {
			this.popover.destroyPopover( $metaCell );
		} );


		// Runs once the popover's set-meta button is clicked.
		$( 'body' ).on( 'click', '.popover button.set', ( evt: JQueryEventObject ) => {

			const $button: JQuery   = $( evt.currentTarget ),
			      $popover: JQuery  = $button.closest( '.popover' ),
			      popoverId: string = $popover.attr( 'id' ),
			      $setMeta: JQuery  = $( `[aria-describedby="${ popoverId }"]` );

			if ( $setMeta.length ) {
				this.listTable.maybeAddSaveButton();
				this.listTable.updateEditedColsInput( $setMeta, $popover );
			}

		} ).on( 'click', '.popover .content-footer .atum-add-supplier', ( evt: JQueryEventObject ) => {

			const $button: JQuery   = $( evt.currentTarget ),
			      $popover: JQuery  = $button.closest( '.popover' ),
			      popoverId: string = $popover.attr( 'id' ),
			      $setMeta: JQuery  = $( `[aria-describedby="${ popoverId }"]` );
			      // productId: number = parseInt( $setMeta.closest( 'tr' ).data( 'id' ) );

			this.popover.destroyPopover( $setMeta );
			new AddSupplierModal( this.settings, this.globals, this.listTable, $setMeta );
		} );

	}

	/**
	 * Add hooks
	 */
	addHooks() {

		// Restore the popovers after the List Table updates.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => this.popover.bindPopovers() );

	}
	
}
