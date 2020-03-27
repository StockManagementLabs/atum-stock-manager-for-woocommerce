/* =======================================
   ATUM ORDER ITEMS
   ======================================= */

import AtumOrders from './_atum-orders';
import { Blocker } from '../_blocker';
import Settings from '../../config/_settings';
import { Utils } from '../../utils/_utils';

export default class AtumOrderItems {
	
	swal: any = window['swal'];
	
	constructor(
		private settings: Settings,
		private $container: JQuery,
		private atumOrders: AtumOrders
	) {
		
		// Bind items' events
		this.$container
			.on( 'click', 'button.add-line-item', (evt: JQueryEventObject) => this.addLineItem(evt) )
			.on( 'click', '.cancel-action', (evt: JQueryEventObject) => this.cancel(evt) )
			.on( 'click', 'button.add-atum-order-item', (evt: JQueryEventObject) => this.addItem(evt) )
			.on( 'click', 'button.add-atum-order-fee', (evt: JQueryEventObject) => this.addFee(evt) )
			.on( 'click', 'button.add-atum-order-shipping', (evt: JQueryEventObject) => this.addShipping(evt) )
			.on( 'click', 'button.add-atum-order-tax', (evt: JQueryEventObject) => this.addTax(evt) )
			.on( 'click', 'a.delete-atum-order-tax', (evt: JQueryEventObject) => this.deleteTax(evt) )
			.on( 'click', 'button.calculate-action', (evt: JQueryEventObject) => this.recalculate(evt) )
			.on( 'click', 'a.edit-atum-order-item', (evt: JQueryEventObject) => this.editItem(evt) )
			.on( 'click', 'a.delete-atum-order-item', (evt: JQueryEventObject) => this.deleteItem(evt) )
			.on( 'click', 'button.save-action', (evt: JQueryEventObject) => this.saveLineItems(evt) )
			
			// Meta
			.on( 'click', 'button.add-atum-order-item-meta', (evt: JQueryEventObject) => this.addItemMeta(evt) )
			.on( 'click', 'button.remove-atum-order-item-meta', (evt: JQueryEventObject) => this.removeItemMeta(evt) )
			.on( 'click', 'button.set-purchase-price', (evt: JQueryEventObject) => this.setPurchasePrice(evt) );


		// Add this component to the global scope so can be accessed by other add-ons.
		if ( ! window.hasOwnProperty( 'atum' ) ) {
			window['atum'] = {};
		}

		window['atum']['AtumOrderItems'] = this;
		
	}
	
	addLineItem(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		$('div.atum-order-add-item').slideDown();
		$('div.atum-order-data-row-toggle').not('div.atum-order-add-item').slideUp();
		
	}
	
	cancel(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		$('div.atum-order-data-row-toggle' ).not('div.atum-order-bulk-actions').slideUp();
		$('div.atum-order-bulk-actions, div.atum-order-totals-items').slideDown();
		$('.atum-order-edit-line-item .atum-order-edit-line-item-actions').show();
		
		// Reload the items
		if ( true === $(evt.currentTarget).data('reload') ) {
			this.atumOrders.reloadItems();
		}
		
	}
	
	addItem(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		(<any>$(evt.currentTarget)).WCBackboneModal({
			template: 'atum-modal-add-products'
		});
		
	}
	
	addFee(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		Blocker.block(this.$container);
		
		const data: any = {
			action       : 'atum_order_add_fee',
			atum_order_id: this.settings.get('post_id'),
			security     : this.settings.get('atum_order_item_nonce'),
		};
		
		$.post( window['ajaxurl'], data, (response: any) => {
			
			if ( response.success ) {
				$('#atum_order_fee_line_items').append( response.data.html );
			}
			else {
				this.atumOrders.showAlert('error', this.settings.get('error'), response.data.error);
			}
			
			Blocker.unblock(this.$container);
			
		}, 'json');
		
	}
	
	addShipping(evt:JQueryEventObject) {
		
		evt.preventDefault();
		
		Blocker.block(this.$container);
		
		const data: any = {
			action       : 'atum_order_add_shipping',
			atum_order_id: this.settings.get('post_id'),
			security     : this.settings.get('atum_order_item_nonce'),
		};
		
		$.post( window['ajaxurl'], data, (response: any) => {
			
			if ( response.success ) {
				$('#atum_order_shipping_line_items').append( response.data.html );
			}
			else {
				this.atumOrders.showAlert('error', this.settings.get('error'), response.data.error);
			}
			
			Blocker.unblock(this.$container);
			
		}, 'json');
		
	}
	
	addTax(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		(<any>$(evt.currentTarget)).WCBackboneModal({
			template: 'atum-modal-add-tax'
		});
		
	}
	
	deleteTax(evt: JQueryEventObject) {

		evt.preventDefault();

		let $item: JQuery = $(evt.currentTarget);

		this.swal({
			text               : this.settings.get('delete_tax_notice'),
			type               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get('continue'),
			cancelButtonText   : this.settings.get('cancel'),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {

				return new Promise( (resolve: Function, reject: Function) => {

					this.atumOrders.loadItemsTable({
						action       : 'atum_order_remove_tax',
						rate_id      : $item.data('rate_id'),
						atum_order_id: this.settings.get('post_id'),
						security     : this.settings.get('atum_order_item_nonce'),
					}, 'html', resolve);

				});

			}
		}).catch(this.swal.noop);
		
	}
	
	recalculate(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		this.swal({
			text               : this.settings.get('calc_totals'),
			type               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get('continue'),
			cancelButtonText   : this.settings.get('cancel'),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {
				
				return new Promise( (resolve: Function, reject: Function) => {
					
					this.atumOrders.loadItemsTable({
						action       : 'atum_order_calc_line_taxes',
						atum_order_id: this.settings.get('post_id'),
						items        : $('table.atum_order_items :input[name], .atum-order-totals-items :input[name]').serialize(),
						security     : this.settings.get('calc_totals_nonce'),
					}, 'html', resolve);
					
				});
				
			}
		}).catch(this.swal.noop);
		
	}
	
	editItem(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		const $item: JQuery = $(evt.currentTarget);
		
		$item.closest( 'tr' ).find( '.view' ).hide();
		$item.closest( 'tr' ).find( '.edit' ).show();
		$item.hide();
		$('button.add-line-item').click();
		$('button.cancel-action').data( 'reload', true );
		
	}
	
	deleteItem(evt:JQueryEventObject) {
		
		evt.preventDefault();
		
		let $item: JQuery           = $(evt.currentTarget).closest('tr.item, tr.fee, tr.shipping'),
		    atumOrderItemId: number = $item.data('atum_order_item_id'),
		    $container: JQuery      = $item.closest('#atum_order_items');
		
		this.swal({
			text               : this.settings.get('remove_item_notice'),
			type               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get('continue'),
			cancelButtonText   : this.settings.get('cancel'),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {
				
				return new Promise( (resolve: Function, reject: Function) => {
					
					Blocker.block(this.$container);
					
					$.ajax({
						url    : window['ajaxurl'],
						data   : {
							atum_order_id      : this.settings.get('post_id'),
							atum_order_item_ids: atumOrderItemId,
							action             : 'atum_order_remove_item',
							security           : this.settings.get('atum_order_item_nonce'),
						},
						type   : 'POST',
						success: () => {
							resolve();
						}
					});
					
				});
			}
		}).then( () => {
			
			$item.remove();
			$container.trigger('atum_item_line_removed', [atumOrderItemId]);
			Blocker.unblock(this.$container);
			
		}).catch(this.swal.noop);
		
	}
	
	saveLineItems(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		this.atumOrders.loadItemsTable({
			atum_order_id: this.settings.get('post_id'),
			items        : $('table.atum_order_items :input[name], .atum-order-totals-items :input[name]').serialize(),
			action       : 'atum_order_save_items',
			security     : this.settings.get('atum_order_item_nonce'),
		});
		
		$(evt.currentTarget).trigger( 'items_saved' );
		
	}
	
	addItemMeta(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		let $button: JQuery = $(evt.currentTarget),
		    $item: JQuery   = $button.closest('tr.item, tr.shipping'),
		    $items: JQuery  = $item.find('tbody.meta_items'),
		    index: number   = $items.find('tr').length + 1,
		    $row: string    =  `
				<tr data-meta_id="0">
			        <td>
			            <input type="text" placeholder="${ this.settings.get('placeholder_name') }" name="meta_key[${ $item.data('atum_order_item_id') }][new-${ index }]" />
			            <textarea placeholder="${ this.settings.get('placeholder_value') }" name="meta_value[${ $item.data('atum_order_item_id') }][new-${ index }]"></textarea>
			        </td>
			        <td width="1%"><button class="remove-atum-order-item-meta button">&times;</button></td>
			    </tr>`;
		
		$items.append( $row );
		
	}
	
	removeItemMeta(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		this.swal({
			text               : this.settings.get('remove_item_meta'),
			type               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get('continue'),
			cancelButtonText   : this.settings.get('cancel'),
			reverseButtons     : true,
			allowOutsideClick  : false,
			preConfirm         : (): Promise<any> => {
				
				return new Promise( (resolve: Function, reject: Function) => {
					
					const $row: JQuery = $(evt.currentTarget).closest('tr');
					$row.find(':input').val('');
					$row.hide();
					resolve();
					
				});
				
			}
		}).catch(this.swal.noop);
		
	}

	setPurchasePrice( evt: JQueryEventObject, purchasePrice?: number, purchasePriceTxt?: string ) {
		
		evt.preventDefault();

		let $item: JQuery = $( evt.currentTarget ).closest( '.item' ),
		    qty: number   = parseFloat( $item.find( 'input.quantity' ).val() || 1 ),
		    taxes: number = 0,
		    data: any     = {
			    atum_order_id     : this.settings.get( 'post_id' ),
			    atum_order_item_id: $item.data( 'atum_order_item_id' ),
			    action            : 'atum_order_change_purchase_price',
			    security          : this.settings.get( 'atum_order_item_nonce' ),
		    },
		    rates: any    = $item.find( '.item_cost' ).data( 'productTaxRates' );

		if ( ! purchasePrice ) {
			purchasePrice = qty !== 0 ? <number>Utils.unformat($item.find('input.line_total').val() || 0, this.settings.get('mon_decimal_point')) / qty : 0;
		}

		if ( ! purchasePriceTxt ) {

			purchasePriceTxt = purchasePrice.toString();

			if ( typeof rates === 'object' ) {
				taxes = this.calcTaxesFromBase( purchasePrice, rates );

				if ( taxes ) {
					purchasePriceTxt = `${ purchasePrice + taxes } (${ purchasePrice } + ${ taxes } ${ this.settings.get( 'taxes_name' ) })`;
					purchasePrice += taxes;
				}

			}

		}
		
		data[ this.settings.get('purchase_price_field') ] = purchasePrice;
		
		this.swal({
			html               : this.settings.get('confirm_purchase_price').replace('{{number}}', `<strong>${ purchasePriceTxt }</strong>`),
			type               : 'question',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get('continue'),
			cancelButtonText   : this.settings.get('cancel'),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {
				
				return new Promise( (resolve: Function, reject: Function) => {
					
					$.ajax({
						url    : window['ajaxurl'],
						data   : data,
						type   : 'POST',
						dataType: 'json',
						success: (response: any) => {
							
							if (response.success === false) {
								reject(response.data);
							}
							else {
								resolve();
							}
							
						}
					});
					
				});
				
			}
		}).then( () => {
			
			this.swal({
				title            : this.settings.get('done'),
				text             : this.settings.get('purchase_price_changed'),
				type             : 'success',
				confirmButtonText: this.settings.get('ok'),
			});
			
		}).catch(this.swal.noop);
		
	}
	
	/**
	 * Calc a base price taxes. Based on WC_Tax::calc_exclusive_tax as we have a price without applied taxes.
	 *
	 * @param {number} price
	 * @param {any[]} rates
	 * @return {number}
	 */
	calcTaxesFromBase( price: number, rates: any[]) {
		
		let taxes: number[] = [0],
		    preCompoundTaxes: number;
		
		$.each( rates, (i: number, rate: any) => {
			
			if ( 'yes' === rate['compound'] ) {
				return true;
			}
			taxes.push(price * rate['rate'] / 100);
		});
		
		preCompoundTaxes = taxes.reduce((a, b) => a + b, 0 );
		
		// Compound taxes.
		$.each( rates, (i: number, rate: any) => {
			
			let currentTax: number;
			
			if ( 'no' === rate['compound'] ) {
				return true;
			}
			
			currentTax = ( price + preCompoundTaxes ) * rate['rate'] / 100;
			taxes.push(currentTax);
			preCompoundTaxes += currentTax;
			
		});
	
		return taxes.reduce((a, b) => a + b, 0 );
	}
	
}