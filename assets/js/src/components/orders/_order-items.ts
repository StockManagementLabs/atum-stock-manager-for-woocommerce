/* =======================================
   ATUM ORDER ITEMS
   ======================================= */

import AtumOrders from './_atum-orders';
import Blocker from '../_blocker';
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
			.on( 'click', 'button.set-purchase-price', ( evt: JQueryEventObject ) => this.setPurchasePrice( evt ) );


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
			atum_order_id: this.settings.get( 'post_id' ),
			security     : this.settings.get( 'atum_order_item_nonce' ),
		};

		$.post( window[ 'ajaxurl' ], data, ( response: any ) => {

			if ( response.success ) {
				$( '#atum_order_fee_line_items' ).append( response.data.html );
			}
			else {
				this.atumOrders.showAlert( 'error', this.settings.get( 'error' ), response.data.error );
			}

			Blocker.unblock( this.$container );
			this.wpHooks.doAction( 'orderItems_afterAddingFee' );

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
			atum_order_id: this.settings.get( 'post_id' ),
			security     : this.settings.get( 'atum_order_item_nonce' ),
		};

		$.post( window[ 'ajaxurl' ], data, ( response: any ) => {

			if ( response.success ) {
				$( '#atum_order_shipping_line_items' ).append( response.data.html );
			}
			else {
				this.atumOrders.showAlert( 'error', this.settings.get( 'error' ), response.data.error );
			}

			Blocker.unblock( this.$container );
			this.wpHooks.doAction( 'orderItems_afterAddingShipping' );

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
			text               : this.settings.get( 'delete_tax_notice' ),
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
						atum_order_id: this.settings.get( 'post_id' ),
						security     : this.settings.get( 'atum_order_item_nonce' ),
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
			text               : this.settings.get( 'calc_totals' ),
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
						atum_order_id: this.settings.get( 'post_id' ),
						items        : $( 'table.atum_order_items :input[name], .atum-order-totals-items :input[name]' ).serialize(),
						security     : this.settings.get( 'calc_totals_nonce' ),
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

		// Asks for confirmation before proceeding.
		Swal.fire( {
			text               : this.settings.get( 'remove_item_notice' ),
			icon               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get( 'continue' ),
			cancelButtonText   : this.settings.get( 'cancel' ),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<void> => this.processDeleteItem( atumOrderItemId ),
		} )
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {
				$item.remove();
				this.wpHooks.doAction( 'orderItems_deleteItem_removed', $container, atumOrderItemId );
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
	processDeleteItem( atumOrderItemId ): Promise<void> {

		return new Promise( ( resolve: Function ) => {

			Blocker.block( this.$container );

			$.ajax( {
				url     : window[ 'ajaxurl' ],
				data    : {
					atum_order_id      : this.settings.get( 'post_id' ),
					atum_order_item_ids: atumOrderItemId,
					action             : 'atum_order_remove_item',
					security           : this.settings.get( 'atum_order_item_nonce' ),
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

		const data: any = this.wpHooks.applyFilters( 'orderItems_saveLineItems_data', {
			atum_order_id: this.settings.get( 'post_id' ),
			items        : $( 'table.atum_order_items :input[name], .atum-order-totals-items :input[name]' ).serialize(),
			action       : 'atum_order_save_items',
			security     : this.settings.get( 'atum_order_item_nonce' ),
		} );

		this.atumOrders.loadItemsTable( data );
		this.wpHooks.doAction( 'orderItems_saveLineItems_itemsSaved' );

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
			            <input type="text" placeholder="${ this.settings.get( 'placeholder_name' ) }" name="meta_key[${ $item.data( 'atum_order_item_id' ) }][new-${ index }]" />
			            <textarea placeholder="${ this.settings.get( 'placeholder_value' ) }" name="meta_value[${ $item.data( 'atum_order_item_id' ) }][new-${ index }]"></textarea>
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
			text             : this.settings.get( 'remove_item_meta' ),
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
	 * @param {JQueryEventObject} evt
	 * @param {number} purchasePrice
	 * @param {string} purchasePriceTxt
	 */
	setPurchasePrice( evt: JQueryEventObject, purchasePrice?: number, purchasePriceTxt?: string ) {

		evt.preventDefault();

		const $item: JQuery         = $( evt.currentTarget ).closest( '.item' ),
		      $lineSubTotal: JQuery = $item.find( 'input.line_subtotal' ),
		      $lineTotal: JQuery    = $item.find( 'input.line_total' ),
		      qty: number           = parseFloat( $item.find( 'input.quantity' ).val() || 1 ),
		      lineTotal: number     = qty !== 0 ? <number> Utils.unformat( $lineTotal.val() || 0, this.settings.get( 'mon_decimal_point' ) ) : 0,
		      data: any             = {
			      atum_order_id     : this.settings.get( 'post_id' ),
			      atum_order_item_id: $item.data( 'atum_order_item_id' ),
			      action            : 'atum_order_change_purchase_price',
			      security          : this.settings.get( 'atum_order_item_nonce' ),
		      },
		      rates: any            = $item.find( '.item_cost' ).data( 'productTaxRates' );

		let purchasePriceFmt: string,
		    taxes: number = 0;

		if ( ! purchasePrice ) {
			purchasePrice = qty !== 0 ? lineTotal / qty : 0;
		}

		if ( ! purchasePriceTxt ) {

			purchasePriceFmt = purchasePrice % 1 !== 0 ? <string> Utils.formatNumber( purchasePrice, this.settings.get( 'mon_decimals' ), '', this.settings.get( 'mon_decimal_point' ) ) : purchasePrice.toString();
			purchasePriceTxt = purchasePriceFmt;

			if ( typeof rates === 'object' ) {

				taxes = this.calcTaxesFromBase( purchasePrice, rates );

				if ( taxes ) {
					let purchasePriceWithTaxesFmt: string = ( purchasePrice + taxes ) % 1 !== 0 ? <string> Utils.formatNumber( purchasePrice + taxes, this.settings.get( 'mon_decimals' ), '', this.settings.get( 'mon_decimal_point' ) ) : ( purchasePrice + taxes ).toString();
					purchasePriceTxt = `${ purchasePriceWithTaxesFmt } (${ purchasePriceFmt } + ${ taxes } ${ this.settings.get( 'taxes_name' ) })`;
					purchasePrice = <number> Utils.unformat( purchasePriceWithTaxesFmt, this.settings.get( 'mon_decimal_point' ) );
				}

			}
			else {
				purchasePrice = <number> Utils.unformat( purchasePriceFmt, this.settings.get( 'mon_decimal_point' ) );
			}

		}

		data[ this.settings.get( 'purchase_price_field' ) ] = purchasePrice;

		Swal.fire( {
			html               : this.settings.get( 'confirm_purchase_price' ).replace( '{{number}}', `<strong>${ purchasePriceTxt }</strong>` ),
			icon               : 'question',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get( 'continue' ),
			cancelButtonText   : this.settings.get( 'cancel' ),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {

				return new Promise( ( resolve: Function, reject: Function ) => {

					$.ajax( {
						url     : window[ 'ajaxurl' ],
						data    : data,
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

				$lineSubTotal.val( $lineTotal.val() );
				$lineSubTotal.data( 'subtotal', $lineTotal.data( 'total' ) );

				Swal.fire( {
					title            : this.settings.get( 'done' ),
					text             : this.settings.get( 'purchase_price_changed' ),
					icon             : 'success',
					confirmButtonText: this.settings.get( 'ok' ),
				} );

			}

		} );

	}

	/**
	 * Calc a base price taxes. Based on WC_Tax::calc_exclusive_tax as we have a price without applied taxes.
	 *
	 * @param {number} price
	 * @param {any[]} rates
	 *
	 * @return {number}
	 */
	calcTaxesFromBase( price: number, rates: any[] ): number {

		let taxes: number[] = [ 0 ],
		    preCompoundTaxes: number;

		$.each( rates, ( i: number, rate: any ) => {

			if ( 'yes' === rate[ 'compound' ] ) {
				return true;
			}
			taxes.push( price * rate[ 'rate' ] / 100 );
		} );

		preCompoundTaxes = taxes.reduce( ( a, b ) => a + b, 0 );
		
		// Compound taxes.
		$.each( rates, ( i: number, rate: any ) => {

			let currentTax: number;

			if ( 'no' === rate[ 'compound' ] ) {
				return true;
			}

			currentTax = ( price + preCompoundTaxes ) * rate[ 'rate' ] / 100;
			taxes.push( currentTax );
			preCompoundTaxes += currentTax;

		} );

		return taxes.reduce( ( a, b ) => a + b, 0 );
	}
	
}
