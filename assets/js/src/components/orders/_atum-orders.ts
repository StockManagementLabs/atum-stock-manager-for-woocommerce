/* =======================================
   ATUM ORDERS
   ======================================= */

import AddItemsPopup from './_add-items-popup';
import AtumOrderItems from './_order-items';
import { Blocker } from '../_blocker';
import DateTimePicker from '../_date-time-picker';
import OrdersBulkActions from './_bulk-actions';
import Tooltip from '../_tooltip';
import Settings from '../../config/_settings';
import { StupidTable } from '../_stupid-table';
import { Switcher } from '../_switcher';
import { Utils } from '../../utils/_utils';

export default class AtumOrders {
	
	$container: JQuery;
	$itemsBlocker: JQuery;
	areItemsSelectable: boolean;
	isEditable: string;
	swal: any = window['swal'];

	constructor(
		private settings: Settings,
		private tooltip: Tooltip,
		private dateTimePicker: DateTimePicker
	) {
		
		this.$container = $('#atum_order_items');
		this.$itemsBlocker = this.$container.find('.items-blocker');
		
		if (!this.$itemsBlocker.length && typeof this.settings.get('itemBlocker') !== 'undefined') {
			$('.atum_order_items_wrapper').before(`<div class="items-blocker"><h3>${ this.settings.get('itemBlocker') }</h3></div>`);
			this.$itemsBlocker = this.$container.find('.items-blocker');
		}
		
		this.areItemsSelectable = this.settings.get('enableSelectItems');
		this.isEditable = $('#atum_order_is_editable').val();
		
		StupidTable.init( $('table.atum_order_items') );
		new AtumOrderItems(this.settings, this.$container, this);
		new OrdersBulkActions(this.settings, this.$container, this);
		new AddItemsPopup(this.settings, this.$container, this, this.tooltip);
		this.dateTimePicker.addDateTimePickers( $( '.atum-datepicker' ), { minDate: false } );
		Switcher.doSwitchers();
		
		this.bindEvents();
		
	}
	
	bindEvents() {
		
		// Bind items' events
		this.$container
			
			// Qty
			.on( 'change', 'input.quantity', (evt: JQueryEventObject) => this.quantityChanged(evt) )
			
			// Subtotal/total
			.on( 'keyup change', '.split-input :input', (evt: JQueryEventObject) => {
				
				const $input: JQuery    = $(evt.currentTarget),
				      $subtotal: JQuery = $input.parent().prev().find(':input');
				
				if ( $subtotal && ( $subtotal.val() === '' || $subtotal.is('.match-total') ) ) {
					$subtotal.val( $input.val() ).addClass('match-total');
				}
				
			})
			
			.on( 'keyup', '.split-input :input', (evt: JQueryEventObject) => {
				$(evt.currentTarget).removeClass('match-total');
			});
		
		if (this.areItemsSelectable) {
			
			this.$container
				
				.on('click', 'tr.item, tr.fee, tr.shipping', (evt: JQueryEventObject) => this.selectRow(evt))
				.on('click', 'tr.item :input, tr.fee :input, tr.shipping :input, tr.item a, tr.fee a, tr.shipping a', (evt: JQueryEventObject) => evt.stopPropagation());
			
		}
		
		// Trigger ATUM order type dependent fields
		$('#atum_order_type').change( (evt: JQueryEventObject) => this.toggleExtraFields(evt) ).change();
		
		// Hide/show the blocker section on supplier dropdown changes.
		$('.dropdown_supplier').change( (evt: JQueryEventObject) => this.toggleItemsBlocker( $(evt.currentTarget).val() !== null ) ).change();
		
		// Trigger multiple suppliers' dependent fields
		$('#multiple_suppliers').change( (evt: JQueryEventObject) => this.toggleSupplierField(evt) ).change();
		
		// Ask for importing the order items after linking an order
		$('#wc_order').change( () => this.importOrderItems() );
		
		// Change button page-title-action position
		$('.wp-heading-inline').append( $('.page-title-action').show() );
		
		// Footer position
		$(window).on('load', () => {
			
			if ( $('.footer-box').hasClass('no-style') ) {
				$('#wpfooter').css('position', 'relative').show();
				$('#wpcontent').css('min-height', '95vh');
			}
			
		});
		
	}
	
	/**
	 * When the qty is changed, increase or decrease costs
	 */
	quantityChanged(evt: JQueryEventObject) {
		
		let $input: JQuery        = $(evt.currentTarget),
		    $row: JQuery          = $input.closest('tr.item'),
		    qty: number           = $input.val(),
		    oQty: number          = $input.data('qty'),
		    $lineTotal: JQuery    = $row.find('input.line_total'),
		    $lineSubtotal: JQuery = $row.find('input.line_subtotal');
		
		// Totals
		let unitTotal: number = <number>Utils.unformat( $lineTotal.data( 'total' ), this.settings.get('mon_decimal_point') ) / oQty;
		
		$lineTotal.val(
			parseFloat( <string>Utils.formatNumber( unitTotal * qty, this.settings.get('rounding_precision'), '' ) )
				.toString()
				.replace( '.', this.settings.get('mon_decimal_point') )
		);
		
		let unitSubtotal: number = <number>Utils.unformat( $lineSubtotal.data( 'subtotal' ), this.settings.get('mon_decimal_point') ) / oQty;
		
		$lineSubtotal.val(
			parseFloat( <string>Utils.formatNumber( unitSubtotal * qty, this.settings.get('rounding_precision'), '' ) )
				.toString()
				.replace( '.', this.settings.get('mon_decimal_point') )
		);
		
		// Taxes
		$row.find('input.line_tax').each( (index: number, elem: Element) => {
			
			let $lineTotalTax: JQuery    = $(elem),
			    taxId: string            = $lineTotalTax.data('tax_id'),
			    unitTotalTax: number     = <number>Utils.unformat($lineTotalTax.data('total_tax'), this.settings.get('mon_decimal_point')) / oQty,
			    $lineSubtotalTax: JQuery = $row.find(`input.line_subtotal_tax[data-tax_id="${ taxId }"]`),
			    unitSubtotalTax: number  = <number>Utils.unformat($lineSubtotalTax.data('subtotal_tax'), this.settings.get('mon_decimal_point')) / oQty;
			
			if ( 0 < unitTotalTax ) {
				$lineTotalTax.val(
					parseFloat( <string>Utils.formatNumber( unitTotalTax * qty, this.settings.get('rounding_precision'), '' ) )
						.toString()
						.replace( '.', this.settings.get('mon_decimal_point') )
				);
			}
			
			if ( 0 < unitSubtotalTax ) {
				$lineSubtotalTax.val(
					parseFloat( <string>Utils.formatNumber( unitSubtotalTax * qty, this.settings.get('rounding_precision'), '' ) )
						.toString()
						.replace( '.', this.settings.get('mon_decimal_point') )
				);
			}
			
		});
		
		$input.trigger( 'quantity_changed' );
		
	}
	
	loadItemsTable(data: any, dataType?: string, callback?: Function) {
		
		Blocker.block(this.$container);
		dataType = dataType || 'html';
		
		$.ajax({
			url     : window['ajaxurl'],
			data    : data,
			dataType: dataType,
			method  : 'POST',
			success : (response: any) => {
				
				if ((typeof response === 'object' && response.success === true) || typeof response !== 'object') {
					
					const itemsTable: string = dataType === 'html' ? response : response.data.html;
					
					this.$container.find('.inside').empty().append(itemsTable);
					this.tooltip.addTooltips();
					StupidTable.init( $('table.atum_order_items') );
					
				}
				else if (typeof response === 'object' && response.success === false) {
					this.showAlert('error', this.settings.get('error'), response.data.error);
				}
				
				Blocker.unblock(this.$container);
				
				if (callback) {
					callback();
				}
				
				this.$container.trigger('atum-after-loading-order-items');
				
			}
		});
		
	}
	
	reloadItems(callback?: Function) {
		
		this.loadItemsTable({
			atum_order_id: this.settings.get('post_id'),
			action       : 'atum_order_load_items',
			security     : this.settings.get('atum_order_item_nonce'),
		}, 'html', callback);
		
	}
	
	showAlert(type: string, title: string, message: string) {
		
		this.swal({
			title            : title,
			text             : message,
			type             : type,
			confirmButtonText: this.settings.get('ok'),
		});
		
	}
	
	selectRow(evt: JQueryEventObject) {
		
		const $row: JQuery   = $(evt.currentTarget).is('tr') ? $(evt.currentTarget) : $(evt.currentTarget).closest('tr'),
		      $table: JQuery = $row.closest('table');
		
		if ($row.is('.selected')) {
			$row.removeClass('selected');
		}
		else {
			$row.addClass('selected');
		}
		
		const $rows: JQuery                = $table.find('tr.selected'),
		      $editControlsWrapper: JQuery = $('div.atum-order-item-bulk-edit');
		
		if ($rows.length) {
			
			// The Increase/Decrease stock buttons must be only visible when at least one product is selected
			const $stockChangeButtons: JQuery = $('.bulk-decrease-stock, .bulk-increase-stock');
			
			if ($('table.atum_order_items').find('tr.item.selected').length) {
				$stockChangeButtons.show();
			}
			else {
				$stockChangeButtons.hide();
			}
			
			$editControlsWrapper.slideDown();
			
		}
		else {
			$editControlsWrapper.slideUp();
		}
		
	}
	
	toggleExtraFields(evt: JQueryEventObject) {
		
		const $atumOrderType: JQuery = $(evt.currentTarget),
		      typeValue: string      = $atumOrderType.val();
		
		$('[data-dependency]').each( (index: number, elem: Element) => {
			
			const $elem: JQuery        = $(elem),
			      dependency: string[] = $elem.data('dependency').split(':');
			
			if (dependency[0] === $atumOrderType.attr('id')) {
				
				if (dependency[1] === typeValue) {
					$elem.fadeIn();
				}
				else if ($elem.is(':visible')) {
					$elem.hide();
				}
				
			}
			
		});
		
	}
	
	toggleSupplierField(evt: JQueryEventObject) {

		const $checkbox: JQuery        = $(evt.currentTarget),
		      $body: JQuery            = $('body'),
		      $dropdown: JQuery        = $('.dropdown_supplier'),
		      $dropdownWrapper: JQuery = $dropdown.parent();

		if ($checkbox.is(':checked')) {
			$dropdown.val('').change();
			$body.addClass('allow-multiple-suppliers');
			this.toggleItemsBlocker();
			$dropdownWrapper.slideUp();
		}
		else {
			$body.removeClass('allow-multiple-suppliers');
			this.toggleItemsBlocker( $dropdown.val() !== null ); // Only block the items if there is no supplier selected.
			$dropdownWrapper.slideDown();
		}

	}
	
	toggleItemsBlocker(on: boolean = true) {
		
		if (!this.$itemsBlocker.length) {
			return;
		}
		
		if (on === true) {
			this.$itemsBlocker.addClass('unblocked');
		}
		else {
			this.$itemsBlocker.removeClass('unblocked');
		}
		
	}
	
	importOrderItems() {
		
		const $wcOrder: JQuery = $('#wc_order'),
		      orderId: number  = $wcOrder.val();
		
		if (!orderId || this.isEditable == 'false') {
			return false;
		}
		
		this.swal({
			text             : this.settings.get('import_order_items'),
			type             : 'warning',
			showCancelButton : true,
			confirmButtonText: this.settings.get('continue'),
			cancelButtonText : this.settings.get('cancel'),
			reverseButtons   : true,
			allowOutsideClick: false,
			preConfirm       : (): Promise<any> => {
				
				return new Promise( (resolve: Function, reject: Function) => {
					
					this.loadItemsTable({
						action       : 'atum_order_import_items',
						wc_order_id  : orderId,
						atum_order_id: this.settings.get('post_id'),
						security     : this.settings.get('import_order_items_nonce'),
					}, 'json', resolve);
					
				});
				
			},
		}).catch(this.swal.noop);
		
	}
	
}