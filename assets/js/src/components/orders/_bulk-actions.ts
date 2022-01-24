/* =======================================
   BULK ACTIONS FOR ATUM ORDERS
   ======================================= */

import AtumOrders from './_atum-orders';
import Blocker from '../_blocker';
import Settings from '../../config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';
import WPHooks from '../../interfaces/wp.hooks';

export default class OrdersBulkActions {
	
	askRemoval: boolean = true;
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private $container: JQuery,
		private atumOrders: AtumOrders
	) {
		
		// Bulk action events.
		this.$container

			.on( 'click', 'button.bulk-delete-items', ( evt: JQueryEventObject ) => this.doBulkDelete( evt ) )

			.on( 'click', 'button.bulk-increase-stock', ( evt: JQueryEventObject ) => {
				evt.preventDefault();
				this.bulkChangeStock( 'increase' );
			} )

			.on( 'click', 'button.bulk-decrease-stock', ( evt: JQueryEventObject ) => {
				evt.preventDefault();
				this.bulkChangeStock( 'decrease' );
			} );
		
	}

	/**
	 * Delete the selected items in bulk
	 *
	 * @param {JQueryEventObject} evt
	 */
	doBulkDelete( evt: JQueryEventObject ) {
		
		evt.preventDefault();
		
		const $rows: JQuery = $('table.atum_order_items').find('tr.selected');
		let deferred: any[] = [];

		if ( $rows.length ) {

			if ( this.askRemoval === true ) {

				Swal.fire( {
					text               : this.settings.get( 'removeItemNotice' ),
					icon               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : this.settings.get( 'continue' ),
					cancelButtonText   : this.settings.get( 'cancel' ),
					reverseButtons     : true,
					allowOutsideClick  : false,
					showLoaderOnConfirm: true,
					preConfirm         : (): Promise<any> => {

						return new Promise( ( resolve: Function, reject: Function ) => {

							deferred = this.bulkDeleteItems( $rows );

							if ( deferred.length ) {

								$.when.apply( $, deferred ).done( () => {
									this.atumOrders.reloadItems();
									resolve();
								} );

							}
							else {
								resolve();
							}

						} );

					},
				} );
				
			}
			else {

				deferred = this.bulkDeleteItems( $rows );
				this.askRemoval = true;

				if ( deferred.length ) {

					$.when.apply( $, deferred ).done( () => {

						this.atumOrders.reloadItems( () => {
							Swal.close();
						} );

					} );

				}
				
			}
			
		}
		
	}

	/**
	 * Perform the deletion for the sepcified items
	 *
	 * @param {JQuery} $rows
	 *
	 * @return {any[]}
	 */
	bulkDeleteItems( $rows: JQuery ): any[] {

		Blocker.block( this.$container );

		let deleteItems: number[] = [],
		    deferred: any[]       = [];

		$.map( $rows, ( $row: JQuery ) => {
			deleteItems.push( parseInt( $row.data( 'atum_order_item_id' ), 10 ) );
			return;
		} );

		if ( deleteItems.length ) {

			deferred.push( $.ajax( {
				url : window[ 'ajaxurl' ],
				data: {
					atum_order_id      : this.settings.get( 'postId' ),
					atum_order_item_ids: deleteItems,
					action             : 'atum_order_remove_item',
					security           : this.settings.get( 'atumOrderItemNonce' ),
				},
				type: 'POST',
			} ) );

		}

		return deferred;

	}

	/**
	 * Perform the stock change (increase or decrease)
	 *
	 * @param {string} action
	 */
	bulkChangeStock( action: string ) {

		const $rows: JQuery       = $( 'table.atum_order_items' ).find( 'tr.selected' );
		const checkItems: boolean = this.wpHooks.applyFilters( 'atum_ordersBulkActions_checkChangeStock', true, $rows );
		const confirmProcessItems: string = this.wpHooks.applyFilters( 'atum_ordersBulkActions_confirmProcessItemsChangeStock', '', $rows, action );

		if ( checkItems ) {

			Blocker.block( this.$container );

			Swal.fire( {
				title              : this.settings.get( 'areYouSure' ),
				html               : ( this.settings.get( action === 'increase' ? 'increaseStockMsg' : 'decreaseStockMsg' ) ) + confirmProcessItems,
				icon               : 'warning',
				showCancelButton   : true,
				confirmButtonText  : this.settings.get( 'continue' ),
				cancelButtonText   : this.settings.get( 'cancel' ),
				reverseButtons     : true,
				allowOutsideClick  : false,
				showLoaderOnConfirm: true,
				preConfirm         : (): Promise<void> => {

					return new Promise( ( resolve: Function, reject: Function ) => {

						const modeProcess: string = $( '#bulk-change-stock-mode' ).length > 0 && $( '#bulk-change-stock-mode' ).is( ':checked' ) ? 'yes' : 'no';
						// Allow bypassing the change (MI needs to run its own version).
						const maybeProcessItems: boolean = this.wpHooks.applyFilters( 'atum_ordersBulkActions_bulkChangeStock', true, $rows, action, modeProcess, resolve );

						if ( maybeProcessItems ) {

							let quantities: any   = {},
							    itemIds: number[] = [];

							$rows.each( ( index: number, elem: Element ) => {

								const $elem: JQuery = $( elem );

								itemIds.push( parseInt( $elem.data( 'atum_order_item_id' ), 10 ) );
								if ( $elem.find( 'input.quantity' ).length ) {
									quantities[ $elem.data( 'atum_order_item_id' ) ] = $elem.find( 'input.quantity' ).val();
								}

							} );


							$.ajax( {
								url     : window[ 'ajaxurl' ],
								data    : {
									atum_order_id      : this.settings.get( 'postId' ),
									atum_order_item_ids: itemIds,
									quantities         : quantities,
									mode               : modeProcess,
									action             : `atum_order_${ action }_items_stock`,
									security           : this.settings.get( 'atumOrderItemNonce' ),
								},
								method  : 'POST',
								dataType: 'json',
								success : ( response: any ) => {

									if ( response.data.length ) {
										// Display notes.
										$( '#atum_order_notes .inside .atum-meta-box' ).empty().html( response.data );
									}
									if ( response.success !== true ) {
										Swal.showValidationMessage( response.data );
									}

									resolve();

								},
							} );

						}

					} );

				}
			} )
			.then( ( result: SweetAlertResult ) => {

				if ( result.isConfirmed ) {

					Swal.fire( {
						title            : this.settings.get( 'done' ),
						text             : this.settings.get( action === 'increase' ? 'stockIncreased' : 'stockDecreased' ),
						icon             : 'success',
						confirmButtonText: this.settings.get( 'ok' ),
					} );

				}

				Blocker.unblock( this.$container );

			} );

		}
	}
	
}