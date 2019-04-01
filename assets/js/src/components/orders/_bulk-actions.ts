/* =======================================
   BULK ACTIONS FOR ATUM ORDERS
   ======================================= */

import AtumOrders from './_atum-orders';
import Settings from '../../config/_settings';
import {Blocker} from '../_blocker';

export default class OrdersBulkActions {
	
	askRemoval: boolean = true;
	swal: any = window['swal'];
	
	constructor(
		private settings: Settings,
		private $container: JQuery,
		private atumOrders: AtumOrders
	) {
		
		// Bulk action events.
		this.$container
		
			.on( 'click', 'button.bulk-delete-items', (evt: JQueryEventObject) => this.doBulkDelete(evt) )
			.on( 'click', 'button.bulk-increase-stock', (evt: JQueryEventObject) => this.doBulkIncreaseStock(evt) )
			.on( 'click', 'button.bulk-decrease-stock', (evt: JQueryEventObject) => this.doBulkDecreaseStock(evt) )
		
	}
	
	doBulkDelete(evt: JQueryEventObject) {
		
		evt.preventDefault();
		
		let $rows: JQuery   = $('table.atum_order_items').find('tr.selected'),
		    deferred: any[] = [];
		
		if ($rows.length) {
			
			if (this.askRemoval === true) {
				
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
							
							deferred = this.bulkDeleteItems($rows);
							
							if (deferred.length) {
								
								$.when.apply($, deferred).done( () => {
									this.atumOrders.reloadItems();
									resolve();
								});
								
							}
							else {
								resolve();
							}
							
						});
						
					}
				}).catch(this.swal.noop);
				
			}
			else {
				
				deferred = this.bulkDeleteItems($rows);
				this.askRemoval = true;
				
				if (deferred.length) {
					
					$.when.apply($, deferred).done( () => {
						
						this.atumOrders.reloadItems( () => {
							this.swal.close();
						});
						
					});
					
				}
				
			}
			
		}
		
	}
	
	bulkDeleteItems($rows: JQuery): any[] {
		
		Blocker.block(this.$container);
		
		let deleteItems: number[] = [],
		    deferred: any[]       = [];
		
		$.map($rows, ($row: JQuery) => {
			deleteItems.push( parseInt($row.data('atum_order_item_id'), 10) );
			return;
		});
		
		if (deleteItems.length) {
			
			deferred.push( $.ajax({
				url : window['ajaxurl'],
				data: {
					atum_order_id      : this.settings.get('post_id'),
					atum_order_item_ids: deleteItems,
					action             : 'atum_order_remove_item',
					security           : this.settings.get('atum_order_item_nonce'),
				},
				type: 'POST'
			}) );
			
		}
		
		return deferred;
		
	}
	
	doBulkIncreaseStock(evt: JQueryEventObject) {
		evt.preventDefault();
		this.bulkChangeStock('increase');
	}
	
	doBulkDecreaseStock(evt: JQueryEventObject) {
		evt.preventDefault();
		this.bulkChangeStock('decrease');
	}
	
	bulkChangeStock(action: string) {
		
		Blocker.block(this.$container);
		
		this.swal({
			title              : this.settings.get('are_you_sure'),
			text               : this.settings.get( action === 'increase' ?  'increase_stock_msg' : 'decrease_stock_msg' ),
			type               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get('continue'),
			cancelButtonText   : this.settings.get('cancel'),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {
				
				return new Promise( (resolve: Function, reject: Function) => {

                    let $rows: JQuery = $('table.atum_order_items').find('tr.selected'),
                        quantities: any = {},
                        itemIds: number[] = [];

					$rows.each( (index: number, elem: Element) => {
						
						const $elem: JQuery = $(elem);

						itemIds.push(parseInt($elem.data('atum_order_item_id'), 10));
						if ($elem.find('input.quantity').length) {
							quantities[ $elem.data('atum_order_item_id') ] = $elem.find('input.quantity').val();
						}

					});

					$.ajax({
						url     : window['ajaxurl'],
						data    : {
							atum_order_id      : this.settings.get('post_id'),
							atum_order_item_ids: itemIds,
							quantities         : quantities,
							action             : 'atum_order_' + action + '_items_stock',
							security           : this.settings.get('atum_order_item_nonce')
						},
						method  : 'POST',
						dataType: 'json',
						success : (response: any) => {
							
							if (response.success === true) {
								resolve();
							}
							else {
								reject(response.data);
							}
							
						}
					});
					
				});
				
			}
		}).then( () => {
			
			this.swal({
				title            : this.settings.get('done'),
				text             : this.settings.get( action === 'increase' ? 'stock_increased' : 'stock_decreased' ),
				type             : 'success',
				confirmButtonText: this.settings.get('ok'),
			});
			
			Blocker.unblock(this.$container);
			
		}, (dismiss: string) => {
			Blocker.unblock(this.$container);
		});
		
	}
	
}