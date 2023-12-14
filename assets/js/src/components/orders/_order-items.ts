/* =======================================
   ATUM ORDER ITEMS
   ======================================= */

import AtumOrders from './_atum-orders';
import Blocker from '../_blocker';
import { COLORS } from '../../config/_constants';
import Settings from '../../config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';
import Utils from '../../utils/_utils';
import WPHooks from '../../interfaces/wp.hooks';

export default class AtumOrderItems {

	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private $container: JQuery,
		private atumOrders: AtumOrders
	) {
		
		// Bind items' events
		this.$container
			.on( 'click', 'button.add-line-item', ( evt: JQueryEventObject ) => this.addLineItem( evt ) )
			.on( 'click', '.cancel-action', ( evt: JQueryEventObject ) => this.cancel( evt ) )
			.on( 'click', 'button.add-atum-order-item', ( evt: JQueryEventObject ) => this.addItem( evt ) )
			.on( 'click', 'button.add-atum-order-fee', ( evt: JQueryEventObject ) => this.addFee( evt ) )
			.on( 'click', 'button.add-atum-order-shipping', ( evt: JQueryEventObject ) => this.addShipping( evt ) )
			.on( 'click', 'button.add-atum-order-tax', ( evt: JQueryEventObject ) => this.addTax( evt ) )
			.on( 'click', 'a.delete-atum-order-tax', ( evt: JQueryEventObject ) => this.deleteTax( evt ) )
			.on( 'click', 'button.calculate-action', ( evt: JQueryEventObject ) => this.recalculate( evt ) )
			.on( 'click', 'a.edit-atum-order-item', ( evt: JQueryEventObject ) => this.editItem( evt ) )
			.on( 'click', 'a.delete-atum-order-item', ( evt: JQueryEventObject ) => this.deleteItem( evt ) )
			.on( 'click', 'button.save-action', ( evt: JQueryEventObject ) => this.saveLineItems( evt ) )

			// Meta
			.on( 'click', 'button.add-atum-order-item-meta', ( evt: JQueryEventObject ) => this.addItemMeta( evt ) )
			.on( 'click', 'button.remove-atum-order-item-meta', ( evt: JQueryEventObject ) => this.removeItemMeta( evt ) )
			.on( 'click', 'button.set-purchase-price', ( evt: JQueryEventObject ) => {
				evt.preventDefault();
				this.setPurchasePrice( $( evt.currentTarget ).closest( '.item' ) );
			} );


		// Add this component to the global scope so can be accessed by other add-ons.
		if ( ! window.hasOwnProperty( 'atum' ) ) {
			window[ 'atum' ] = {};
		}

		window[ 'atum' ][ 'AtumOrderItems' ] = this;
		
	}

	/**
	 * Handles the "Add Product" button clicks
	 *
	 * @param {JQueryEventObject} evt
	 */
	addLineItem( evt: JQueryEventObject ) {

		evt.preventDefault();

		$( 'div.atum-order-add-item' ).slideDown();
		$( 'div.atum-order-data-row-toggle' ).not( 'div.atum-order-add-item' ).slideUp();

	}

	/**
	 * Handles the "Cancel" button clicks
	 *
	 * @param {JQueryEventObject} evt
	 */
	cancel( evt: JQueryEventObject ) {

		evt.preventDefault();

		$( 'div.atum-order-data-row-toggle' ).not( 'div.atum-order-bulk-actions' ).slideUp();
		$( 'div.atum-order-bulk-actions, div.atum-order-totals-items' ).slideDown();
		$( '.atum-order-edit-line-item .atum-order-edit-line-item-actions' ).show();

		// Reload the items
		if ( true === $( evt.currentTarget ).data( 'reload' ) ) {
			this.atumOrders.reloadItems();
		}

	}

	/**
	 * Opens up the modal for adding a new product item to the order
	 *
	 * @param {JQueryEventObject} evt
	 */
	addItem( evt: JQueryEventObject ) {

		evt.preventDefault();

		( <any> $( evt.currentTarget ) ).WCBackboneModal( {
			template: 'atum-modal-add-products',
		} );

	}

	/**
	 * Adds a new fee item to the order
	 *
	 * @param {JQueryEventObject} evt
	 */
	addFee( evt: JQueryEventObject ) {

		evt.preventDefault();

		Blocker.block( this.$container );

		const data: any = {
			action       : 'atum_order_add_fee',
			atum_order_id: this.settings.get( 'postId' ),
			security     : this.settings.get( 'atumOrderItemNonce' ),
		};

		$.post( window[ 'ajaxurl' ], data, ( response: any ) => {

			if ( response.success ) {
				$( '#atum_order_fee_line_items' ).append( response.data.html );
			}
			else {
				this.atumOrders.showAlert( 'error', this.settings.get( 'error' ), response.data.error );
			}

			Blocker.unblock( this.$container );
			this.wpHooks.doAction( 'atum_orderItems_afterAddingFee' );

		}, 'json' );

	}

	/**
	 * Adds a new shipping item to the order
	 *
	 * @param {JQueryEventObject} evt
	 */
	addShipping( evt: JQueryEventObject ) {

		evt.preventDefault();

		Blocker.block( this.$container );

		const data: any = {
			action       : 'atum_order_add_shipping',
			atum_order_id: this.settings.get( 'postId' ),
			security     : this.settings.get( 'atumOrderItemNonce' ),
		};

		$.post( window[ 'ajaxurl' ], data, ( response: any ) => {

			if ( response.success ) {
				$( '#atum_order_shipping_line_items' ).append( response.data.html );
			}
			else {
				this.atumOrders.showAlert( 'error', this.settings.get( 'error' ), response.data.error );
			}

			Blocker.unblock( this.$container );
			this.wpHooks.doAction( 'atum_orderItems_afterAddingShipping' );

		}, 'json' );

	}

	/**
	 * Opens the modal to add a new tax item to the order
	 *
	 * @param {JQueryEventObject} evt
	 */
	addTax( evt: JQueryEventObject ) {

		evt.preventDefault();

		( <any> $( evt.currentTarget ) ).WCBackboneModal( {
			template: 'atum-modal-add-tax',
		} );

	}

	/**
	 * Deletes a tax from the order
	 *
	 * @param {JQueryEventObject} evt
	 */
	deleteTax( evt: JQueryEventObject ) {

		evt.preventDefault();

		const $item: JQuery = $( evt.currentTarget );

		// Prompt for removal confirmation.
		Swal.fire( {
			text               : this.settings.get( 'deleteTaxNotice' ),
			icon               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get( 'continue' ),
			cancelButtonText   : this.settings.get( 'cancel' ),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {

				return new Promise( ( resolve: Function, reject: Function ) => {

					this.atumOrders.loadItemsTable( {
						action       : 'atum_order_remove_tax',
						rate_id      : $item.data( 'rate_id' ),
						atum_order_id: this.settings.get( 'postId' ),
						security     : this.settings.get( 'atumOrderItemNonce' ),
					}, 'html', resolve );

				} );

			},
		} );

	}

	/**
	 * Recalculate order totals
	 *
	 * @param {JQueryEventObject} evt
	 */
	recalculate( evt: JQueryEventObject ) {

		evt.preventDefault();

		Swal.fire( {
			text               : this.settings.get( 'calcTotals' ),
			icon               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get( 'continue' ),
			cancelButtonText   : this.settings.get( 'cancel' ),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {

				return new Promise( ( resolve: Function, reject: Function ) => {

					this.atumOrders.loadItemsTable( {
						action       : 'atum_order_calc_line_taxes',
						atum_order_id: this.settings.get( 'postId' ),
						items        : $( 'table.atum_order_items :input[name], .atum-order-totals-items :input[name]' ).serialize(),
						security     : this.settings.get( 'calcTotalsNonce' ),
					}, 'html', resolve );

				} );

			},
		} );

	}

	/**
	 * Enables item edition
	 *
	 * @param {JQueryEventObject} evt
	 */
	editItem( evt: JQueryEventObject ) {

		evt.preventDefault();

		const $item: JQuery = $( evt.currentTarget );

		$item.closest( 'tr' ).find( '.view' ).hide();
		$item.closest( 'tr' ).find( '.edit' ).show();
		$item.hide();
		$( 'button.add-line-item' ).click();
		$( 'button.cancel-action' ).data( 'reload', true );

	}

	/**
	 * Deletes an order item
	 *
	 * @param {JQueryEventObject} evt
	 */
	deleteItem( evt: JQueryEventObject ) {

		evt.preventDefault();

		const $item: JQuery           = $( evt.currentTarget ).closest( 'tr.item, tr.fee, tr.shipping' ),
		      atumOrderItemId: number = $item.data( 'atum_order_item_id' ),
		      $container: JQuery      = $item.closest( '#atum_order_items' );

		let options: any[] = [],
		    modal: JQuery;

		// Asks for confirmation before proceeding.
		Swal.fire( {
			html               : this.wpHooks.applyFilters( 'atum_ordersItems_deleteItemConfirmMessage', this.settings.get( 'removeItemNotice' ), $item, atumOrderItemId ),
			icon               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get( 'continue' ),
			cancelButtonText   : this.settings.get( 'cancel' ),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			didOpen            : ( element: HTMLElement ) => {
				modal = $( element );
			},
			preConfirm         : (): Promise<void> => this.processDeleteItem( atumOrderItemId ),
		} )
		.then( ( result: SweetAlertResult ) => {

			options = this.wpHooks.applyFilters( 'atum_ordersItems_deleteItemOptions', options, modal );

			if ( result.isConfirmed ) {
				$item.remove();
				this.wpHooks.doAction( 'atum_orderItems_deleteItem_removed', $container, atumOrderItemId, options );
			}

			Blocker.unblock( this.$container );

		} );

	}

	/**
	 * Used in modals to process the order item removal
	 *
	 * @param {number} atumOrderItemId
	 *
	 * @return {Promise<void>}
	 */
	processDeleteItem( atumOrderItemId: number ): Promise<void> {

		return new Promise( ( resolve: Function ) => {

			Blocker.block( this.$container );

			$.ajax( {
				url     : window[ 'ajaxurl' ],
				data    : {
					atum_order_id      : this.settings.get( 'postId' ),
					atum_order_item_ids: atumOrderItemId,
					action             : 'atum_order_remove_item',
					security           : this.settings.get( 'atumOrderItemNonce' ),
				},
				method  : 'POST',
				dataType: 'json',
				success : ( response: any ) => {

					if ( ! response.success ) {
						Swal.showValidationMessage( response.data );
					}

					resolve();

				},
			} );

		} );

	}

	/**
	 * Save the order items to the db
	 *
	 * @param {JQueryEventObject} evt
	 */
	saveLineItems( evt: JQueryEventObject ) {

		evt.preventDefault();

		const data: any = this.wpHooks.applyFilters( 'atum_orderItems_saveLineItems_data', {
			atum_order_id: this.settings.get( 'postId' ),
			items        : $( 'table.atum_order_items :input[name], .atum-order-totals-items :input[name]' ).serialize(),
			action       : 'atum_order_save_items',
			security     : this.settings.get( 'atumOrderItemNonce' ),
		} );

		this.atumOrders.loadItemsTable( data );
		this.wpHooks.doAction( 'atum_orderItems_saveLineItems_itemsSaved' );

	}

	/**
	 * Add meta to an order item
	 *
	 * @param {JQueryEventObject} evt
	 */
	addItemMeta( evt: JQueryEventObject ) {

		evt.preventDefault();

		const $button: JQuery = $( evt.currentTarget ),
		      $item: JQuery   = $button.closest( 'tr.item, tr.shipping' ),
		      $items: JQuery  = $item.find( 'tbody.meta_items' ),
		      index: number   = $items.find( 'tr' ).length + 1,
		      $row: string    = `
				<tr data-meta_id="0">
			        <td>
			            <input type="text" placeholder="${ this.settings.get( 'metaPlaceholderName' ) }" name="meta_key[${ $item.data( 'atum_order_item_id' ) }][new-${ index }]" />
			            <textarea placeholder="${ this.settings.get( 'metaPlaceholderValue' ) }" name="meta_value[${ $item.data( 'atum_order_item_id' ) }][new-${ index }]"></textarea>
			        </td>
			        <td width="1%"><button class="remove-atum-order-item-meta button">&times;</button></td>
			    </tr>`;

		$items.append( $row );

	}

	/**
	 * Remove a meta from an order item
	 *
	 * @param {JQueryEventObject} evt
	 */
	removeItemMeta( evt: JQueryEventObject ) {

		evt.preventDefault();

		// Asks for confirmation before removing.
		Swal.fire( {
			text             : this.settings.get( 'removeItemMeta' ),
			icon             : 'warning',
			showCancelButton : true,
			confirmButtonText: this.settings.get( 'continue' ),
			cancelButtonText : this.settings.get( 'cancel' ),
			reverseButtons   : true,
			allowOutsideClick: false,
			preConfirm       : (): Promise<any> => {

				return new Promise( ( resolve: Function, reject: Function ) => {

					const $row: JQuery = $( evt.currentTarget ).closest( 'tr' );
					$row.find( '[name^="meta_value"]' ).val( '' ); // Clear the value, so it's deleted when saving.
					$row.hide();
					resolve();

				} );

			},
		} );

	}

	/**
	 * Set the purchase price for an order item product
	 *
	 * @param {JQuery} $item
	 * @param {number} purchasePrice
	 * @param {string} purchasePriceTxt
	 * @param {string} itemName
	 */
	setPurchasePrice( $item: JQuery, purchasePrice?: number, purchasePriceTxt?: string, itemName?: string ) {

		const $lineSubTotal: JQuery = $item.find( 'input.line_subtotal' ),
		      $lineTotal: JQuery    = $item.find( 'input.line_total' ),
		      decimalSep: string    = this.settings.get( 'priceDecimalSep' ) || '.',
		      precision: number     = this.settings.get( 'priceNumDecimals' ) || 0

		if ( ! itemName ) {
			itemName = $item.find( '.atum-order-item-name' ).text().trim();
		}

		if ( ! purchasePrice ) {

			const qty: number       = parseFloat( $item.find( 'input.quantity' ).val() || 1 ),
			      lineTotal: number = qty !== 0 ? Utils.unformat( $lineTotal.val() || 0, decimalSep ) : 0;

			purchasePrice = qty !== 0 ? Utils.divideDecimals( lineTotal, qty ) : 0;

		}

		if ( ! purchasePriceTxt ) {

			const purchasePriceFmt: string = Utils.formatNumber( purchasePrice, precision, '', decimalSep );
			purchasePriceTxt = purchasePriceFmt;

			const rates: any = $item.find( '.item_cost' ).data( 'productTaxRates' );

			if ( typeof rates === 'object' ) {

				const taxes: number          = Utils.calcTaxesFromBase( purchasePrice, rates ),
				      formattedTaxes: string = Utils.formatNumber( taxes, precision );

				if ( taxes ) {
					const purchasePriceWithTaxesFmt: string = Utils.formatNumber( Utils.sumDecimals( purchasePrice, taxes ), precision, '', decimalSep );
					purchasePriceTxt = `${ purchasePriceWithTaxesFmt } ( ${ purchasePriceFmt } + ${ formattedTaxes } ${ this.settings.get( 'taxesName' ) } )`;
					purchasePrice    = Utils.unformat( purchasePriceWithTaxesFmt, decimalSep );
				}

			}
			else {
				purchasePrice = Utils.unformat( purchasePriceFmt, decimalSep );
			}

		}

		Swal.fire( {
			title              : this.settings.get( 'confirmPurchasePriceTitle' ),
			html               : this.settings.get( 'confirmPurchasePrice' ).replace( '{{number}}', `<code>${ purchasePriceTxt }</code>` ).replace( '{{name}}', `<code>${ itemName }</code>` ),
			icon               : 'question',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get( 'continue' ),
			confirmButtonColor : COLORS.primary,
			cancelButtonText   : this.settings.get( 'cancel' ),
			reverseButtons     : true,
			showCloseButton    : true,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<void> => {

				return new Promise( ( resolve: Function ) => {

					$.ajax( {
						url     : window[ 'ajaxurl' ],
						data    : {
							action                                       : 'atum_order_change_purchase_price',
							security                                     : this.settings.get( 'atumOrderItemNonce' ),
							atum_order_id                                : this.settings.get( 'postId' ),
							atum_order_item_id                           : $item.data( 'atum_order_item_id' ),
							[ this.settings.get( 'purchasePriceField' ) ]: purchasePrice,
						},
						type    : 'POST',
						dataType: 'json',
						success : ( response: any ) => {

							if ( response.success === false ) {
								Swal.showValidationMessage( response.data );
							}

							resolve();

						},
					} );

				} );

			},
		} )
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {

				if ( $lineSubTotal.length && $lineTotal.length ) {
					$lineSubTotal.val( $lineTotal.val() );
					$lineSubTotal.data( 'subtotal', $lineTotal.data( 'total' ) );
				}

				Swal.fire( {
					title            : this.settings.get( 'done' ),
					text             : this.settings.get( 'purchasePriceChanged' ),
					icon             : 'success',
					confirmButtonText: this.settings.get( 'ok' ),
				} );

			}

		} );

	}
	
}
