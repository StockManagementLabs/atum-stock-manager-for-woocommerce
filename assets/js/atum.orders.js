/**
 * Atum Orders
 *
 * @copyright Stock Management Labs ©2017
 * @since 1.2.4
 */

(function ($) {
	'use strict';
	
	$(function () {
		
		/**
		 * ATUM Order Items meta box
		 * Based on the WooCommerce Order's "items meta box"
		 */
		var atum_order_items = {
			
			init: function() {
				
				this.$container = $('#atum_order_items');
				this.$itemsBlocker = this.$container.find('.items-blocker');
				this.stupidtable.init();
				this.isEditable = $('#atum_order_is_editable').val();
				this.askRemoval = true;
				
				// Bind items' events
				this.$container
					.on( 'click', 'button.add-line-item', this.add_line_item )
					.on( 'click', '.cancel-action', this.cancel )
					.on( 'click', 'button.add-atum-order-item', this.add_item )
					.on( 'click', 'button.add-atum-order-fee', this.add_fee )
					.on( 'click', 'button.add-atum-order-shipping', this.add_shipping )
					.on( 'click', 'button.add-atum-order-tax', this.add_tax )
					.on( 'click', 'button.save-action', this.save_line_items )
					.on( 'click', 'a.delete-atum-order-tax', this.delete_tax )
					.on( 'click', 'button.calculate-action', this.recalculate )
					.on( 'click', 'a.edit-atum-order-item', this.edit_item )
					.on( 'click', 'a.delete-atum-order-item', this.delete_item )
					.on( 'click', 'tr.item, tr.fee, tr.shipping', this.select_row )
					.on( 'click', 'tr.item :input, tr.fee :input, tr.shipping :input, tr.item a, tr.fee a, tr.shipping a', this.select_row_child )
					
					// Bulk actions
					.on( 'click', 'button.bulk-delete-items', this.do_bulk_delete )
					.on( 'click', 'button.bulk-increase-stock', this.do_bulk_increase_stock )
					.on( 'click', 'button.bulk-decrease-stock', this.do_bulk_decrease_stock )
					
					// Qty
					.on( 'change', 'input.quantity', this.quantity_changed )
					
					// Subtotal/total
					.on( 'keyup change', '.split-input :input', function() {
						var $subtotal = $( this ).parent().prev().find(':input');
						if ( $subtotal && ( $subtotal.val() === '' || $subtotal.is( '.match-total' ) ) ) {
							$subtotal.val( $( this ).val() ).addClass( 'match-total' );
						}
					})
					
					.on( 'keyup', '.split-input :input', function() {
						$( this ).removeClass( 'match-total' );
					})
					
					// Meta
					.on( 'click', 'button.add-atum-order-item-meta', this.item_meta.add )
					.on( 'click', 'button.remove-atum-order-item-meta', this.item_meta.remove )
					.on( 'click', 'button.set-purchase-price', this.item_meta.set_purchase_price );
				
				$(document.body)
					.on( 'wc_backbone_modal_loaded', this.backbone.init )
					.on( 'wc_backbone_modal_response', this.backbone.response );
				
				// Trigger ATUM order type dependent fields
				$('#atum_order_type').change(this.toggleExtraFields).change();
				
				// Trigger multiple suppliers' dependent fields
				$('#multiple_suppliers').change(this.toggleSupplierField);
				
				// Ask for importing the order items after linking an order
				$('#wc_order').change(this.importOrderItems);
				
			},
			
			block: function() {
				
				this.$container.block({
					message   : null,
					overlayCSS: {
						background: '#000',
						opacity   : 0.5
					}
				});
				
			},
			
			unblock: function() {
				this.$container.unblock();
			},
			
			reload_items: function(callback) {
				
				this.load_items_table({
					atum_order_id: atumOrder.post_id,
					action       : 'atum_order_load_items',
					security     : atumOrder.atum_order_item_nonce
				}, 'html', callback);
			},
			
			load_items_table: function(data, dataType, callback) {
				
				var self = this;
				this.block();
				dataType = (typeof dataType !== 'undefined') ? dataType : 'html';
				
				$.ajax({
					url     : ajaxurl,
					data    : data,
					dataType: dataType,
					method  : 'POST',
					success : function (response) {
						
						if ((typeof response === 'object' && response.success === true) || typeof response !== 'object') {
							var itemsTable = (dataType === 'html') ? response : response.data.html;
							self.$container.find('.inside').empty().append(itemsTable);
							self.reloadTooltips();
							self.stupidtable.init();
						}
						else if (typeof response === 'object' && response.success === false) {
							self.showalert('error', atumOrder.error, response.data.error);
						}
						
						self.unblock();
						
						if (typeof callback !== 'undefined') {
							callback();
						}
						
					}
				});
				
			},
			
			// When the qty is changed, increase or decrease costs
			quantity_changed: function() {
				
				var $row          = $(this).closest('tr.item'),
				    qty           = $(this).val(),
				    o_qty         = $(this).data('qty'),
				    line_total    = $('input.line_total', $row),
				    line_subtotal = $('input.line_subtotal', $row);
				
				// Totals
				var unit_total = accounting.unformat( line_total.data( 'total' ), atumOrder.mon_decimal_point ) / o_qty;
				line_total.val(
					parseFloat( accounting.formatNumber( unit_total * qty, atumOrder.rounding_precision, '' ) )
						.toString()
						.replace( '.', atumOrder.mon_decimal_point )
				);
				
				var unit_subtotal = accounting.unformat( line_subtotal.data( 'subtotal' ), atumOrder.mon_decimal_point ) / o_qty;
				line_subtotal.val(
					parseFloat( accounting.formatNumber( unit_subtotal * qty, atumOrder.rounding_precision, '' ) )
						.toString()
						.replace( '.', atumOrder.mon_decimal_point )
				);
				
				// Taxes
				$( 'input.line_tax', $row ).each( function() {
					
					var $line_total_tax    = $(this),
					    tax_id             = $line_total_tax.data('tax_id'),
					    unit_total_tax     = accounting.unformat($line_total_tax.data('total_tax'), atumOrder.mon_decimal_point) / o_qty,
					    $line_subtotal_tax = $('input.line_subtotal_tax[data-tax_id="' + tax_id + '"]', $row),
					    unit_subtotal_tax  = accounting.unformat($line_subtotal_tax.data('subtotal_tax'), atumOrder.mon_decimal_point) / o_qty;
					
					if ( 0 < unit_total_tax ) {
						$line_total_tax.val(
							parseFloat( accounting.formatNumber( unit_total_tax * qty, atumOrder.rounding_precision, '' ) )
								.toString()
								.replace( '.', atumOrder.mon_decimal_point )
						);
					}
					
					if ( 0 < unit_subtotal_tax ) {
						$line_subtotal_tax.val(
							parseFloat( accounting.formatNumber( unit_subtotal_tax * qty, atumOrder.rounding_precision, '' ) )
								.toString()
								.replace( '.', atumOrder.mon_decimal_point )
						);
					}
				});
				
				$(this).trigger( 'quantity_changed' );
			},
			
			add_line_item: function() {
				
				$( 'div.atum-order-add-item' ).slideDown();
				$( 'div.atum-order-data-row-toggle' ).not( 'div.atum-order-add-item' ).slideUp();
				
				return false;
				
			},
			
			cancel: function() {
				
				$( 'div.atum-order-data-row-toggle' ).not( 'div.atum-order-bulk-actions' ).slideUp();
				$( 'div.atum-order-bulk-actions, div.atum-order-totals-items' ).slideDown();
				$( '.atum-order-edit-line-item .atum-order-edit-line-item-actions' ).show();
				
				// Reload the items
				if ( 'true' === $(this).data('reload') ) {
					atum_order_items.reload_items();
				}
				
				return false;
				
			},
			
			add_item: function() {
				
				$(this).WCBackboneModal({
					template: 'atum-modal-add-products'
				});
				
				return false;
				
			},
			
			add_fee: function() {
				
				atum_order_items.block();
				
				var data = {
					action       : 'atum_order_add_fee',
					atum_order_id: atumOrder.post_id,
					security     : atumOrder.atum_order_item_nonce
				};
				
				$.post( ajaxurl, data, function( response ) {
					
					if ( response.success ) {
						$('#atum_order_fee_line_items').append( response.data.html );
					}
					else {
						this.showalert('error', atumOrder.error, response.data.error);
					}
					
					atum_order_items.unblock();
					
				}, 'json');
				
				return false;
				
			},
			
			add_shipping: function() {
				
				atum_order_items.block();
				
				var data = {
					action       : 'atum_order_add_shipping',
					atum_order_id: atumOrder.post_id,
					security     : atumOrder.atum_order_item_nonce
				};
				
				$.post( ajaxurl, data, function( response ) {
					
					if ( response.success ) {
						$('#atum_order_shipping_line_items').append( response.data.html );
					}
					else {
						this.showalert('error', atumOrder.error, response.data.error);
					}
					
					atum_order_items.unblock();
					
				}, 'json');
				
				return false;
				
			},
			
			add_tax: function() {
				
				$(this).WCBackboneModal({
					template: 'atum-modal-add-tax'
				});
				return false;
				
			},
			
			edit_item: function() {
				
				var $this = $(this);
				
				$this.closest( 'tr' ).find( '.view' ).hide();
				$this.closest( 'tr' ).find( '.edit' ).show();
				$this.hide();
				$('button.add-line-item').click();
				$('button.cancel-action').data( 'reload', true );
				
				return false;
				
			},
			
			delete_item: function() {
				
				var $item              = $(this).closest('tr.item, tr.fee, tr.shipping'),
				    atum_order_item_id = $item.data('atum_order_item_id'),
					$container         = $item.closest('#atum_order_items');
				
				swal({
					text               : atumOrder.remove_item_notice,
					type               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : atumOrder.continue,
					cancelButtonText   : atumOrder.cancel,
					reverseButtons     : true,
					allowOutsideClick  : false,
					showLoaderOnConfirm: true,
					preConfirm         : function () {
						return new Promise(function (resolve, reject) {
							
							atum_order_items.block();
							
							$.ajax({
								url    : ajaxurl,
								data   : {
									atum_order_id      : atumOrder.post_id,
									atum_order_item_ids: atum_order_item_id,
									action             : 'atum_order_remove_item',
									security           : atumOrder.atum_order_item_nonce
								},
								type   : 'POST',
								success: function () {
									resolve();
								}
							});
							
						});
					}
				}).then(function () {
					$item.remove();
					$container.trigger('atum_item_line_removed', [atum_order_item_id]);
					atum_order_items.unblock();
				}).catch(swal.noop);
				
				return false;
				
			},
			
			delete_tax: function() {
				
				swal({
					text               : atumOrder.delete_tax_notice,
					type               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : atumOrder.continue,
					cancelButtonText   : atumOrder.cancel,
					reverseButtons     : true,
					allowOutsideClick  : false,
					showLoaderOnConfirm: true,
					preConfirm         : function () {
						return new Promise(function (resolve, reject) {
							
							atum_order_items.load_items_table({
								action       : 'atum_order_remove_tax',
								rate_id      : $(this).data('rate_id'),
								atum_order_id: atumOrder.post_id,
								security     : atumOrder.atum_order_item_nonce
							}, 'html', resolve);
							
						});
					}
				}).catch(swal.noop);
				
				return false;
				
			},
			
			recalculate: function() {
				
				swal({
					text               : atumOrder.calc_totals,
					type               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : atumOrder.continue,
					cancelButtonText   : atumOrder.cancel,
					reverseButtons     : true,
					allowOutsideClick  : false,
					showLoaderOnConfirm: true,
					preConfirm         : function () {
						return new Promise(function (resolve, reject) {
							
							atum_order_items.load_items_table({
								action       : 'atum_order_calc_line_taxes',
								atum_order_id: atumOrder.post_id,
								items        : $('table.atum_order_items :input[name], .atum-order-totals-items :input[name]').serialize(),
								security     : atumOrder.calc_totals_nonce
							}, 'html', resolve);
							
						});
					}
				}).catch(swal.noop);
				
				return false;
				
			},
			
			save_line_items: function() {
				
				atum_order_items.load_items_table({
					atum_order_id: atumOrder.post_id,
					items        : $('table.atum_order_items :input[name], .atum-order-totals-items :input[name]').serialize(),
					action       : 'atum_order_save_items',
					security     : atumOrder.atum_order_item_nonce
				});
				
				$(this).trigger( 'items_saved' );
				
				return false;
				
			},
			
			item_meta: {
				
				add: function() {
					
					var $button = $(this),
					    $item   = $button.closest('tr.item, tr.shipping'),
					    $items  = $item.find('tbody.meta_items'),
					    index   = $items.find('tr').length + 1,
					    $row    = '<tr data-meta_id="0">' +
						    '<td>' +
						    '<input type="text" placeholder="' + atumOrder.placeholder_name + '" name="meta_key[' + $item.data('atum_order_item_id') + '][new-' + index + ']" />' +
						    '<textarea placeholder="' + atumOrder.placeholder_value + '" name="meta_value[' + $item.data('atum_order_item_id') + '][new-' + index + ']"></textarea>' +
						    '</td>' +
						    '<td width="1%"><button class="remove-atum-order-item-meta button">&times;</button></td>' +
						    '</tr>';
					
					$items.append( $row );
					
					return false;
					
				},
				
				remove: function() {
					
					swal({
						text               : atumOrder.remove_item_meta,
						type               : 'warning',
						showCancelButton   : true,
						confirmButtonText  : atumOrder.continue,
						cancelButtonText   : atumOrder.cancel,
						reverseButtons     : true,
						allowOutsideClick  : false,
						preConfirm         : function () {
							return new Promise(function (resolve, reject) {
								
								var $row = $(this).closest('tr');
								$row.find(':input').val('');
								$row.hide();
								resolve();
								
							});
						}
					}).catch(swal.noop);
					
					return false;
					
				},
				
				set_purchase_price: function(e) {
					
					var $item         = $(e.target).closest('.item'),
					    qty           = parseFloat($item.find('input.quantity').val() || 1),
					    purchasePrice = qty !== 0 ? accounting.unformat( $item.find('input.line_total').val() || 0, atumOrder.mon_decimal_point ) / qty : 0,
					    data          = {
						    atum_order_id     : atumOrder.post_id,
						    atum_order_item_id: $item.data('atum_order_item_id'),
						    action            : 'atum_order_change_purchase_price',
						    security          : atumOrder.atum_order_item_nonce
					    };
					
					data[atumOrder.purchase_price_field] = purchasePrice;
					
					swal({
						html               : atumOrder.confirm_purchase_price.replace('{{number}}', '<strong>' + purchasePrice + '</strong>'),
						type               : 'question',
						showCancelButton   : true,
						confirmButtonText  : atumOrder.continue,
						cancelButtonText   : atumOrder.cancel,
						reverseButtons     : true,
						allowOutsideClick  : false,
						showLoaderOnConfirm: true,
						preConfirm         : function () {
							return new Promise(function (resolve, reject) {
								
								$.ajax({
									url    : ajaxurl,
									data   : data,
									type   : 'POST',
									dataType: 'json',
									success: function (response) {
										
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
					}).then(function() {
						
						swal({
							title            : atumOrder.done,
							text             : atumOrder.purchase_price_changed,
							type             : 'success',
							confirmButtonText: atumOrder.ok
						});
						
					}).catch(swal.noop);
					
				}
			},
			
			select_row: function() {
				
				var $row   = ( $(this).is('tr') ) ? $(this) : $(this).closest('tr'),
				    $table = $(this).closest('table');
				
				if ($row.is('.selected')) {
					$row.removeClass('selected');
				}
				else {
					$row.addClass('selected');
				}
				
				var $rows                = $table.find('tr.selected'),
				    $editControlsWrapper = $('div.atum-order-item-bulk-edit');
				
				if ($rows.length) {
					
					// The Increase/Decrease stock buttons must be only visible when at least one product is selected
					var $stockChangeButtons = $('.bulk-decrease-stock, .bulk-increase-stock');
					if ($('table.atum_order_items').find('tr.item.selected').length) {
						$stockChangeButtons.show();
					}
					else {
						$stockChangeButtons.hide();
					}
					
					$editControlsWrapper.slideDown();
					
					var selected_product = false;
					
					$rows.each(function () {
						if ($(this).is('tr.item')) {
							selected_product = true;
						}
					});
			
				}
				else {
					$editControlsWrapper.slideUp();
				}
				
			},
			
			select_row_child: function( e ) {
				e.stopPropagation();
			},
			
			do_bulk_delete: function( e ) {
				
				if (typeof e !== 'undefined') {
					e.preventDefault();
				}
				
				var $rows    = $('table.atum_order_items').find('tr.selected'),
				    deferred = [];
				
				if ($rows.length) {
					
					if (atum_order_items.askRemoval === true) {
						
						swal({
							text               : atumOrder.remove_item_notice,
							type               : 'warning',
							showCancelButton   : true,
							confirmButtonText  : atumOrder.continue,
							cancelButtonText   : atumOrder.cancel,
							reverseButtons     : true,
							allowOutsideClick  : false,
							showLoaderOnConfirm: true,
							preConfirm         : function () {
								return new Promise(function (resolve, reject) {
									
									deferred = atum_order_items.bulk_delete_items($rows);
									
									if (deferred.length) {
										$.when.apply($, deferred).done(function () {
											atum_order_items.reload_items();
											resolve();
										});
									}
									else {
										resolve();
									}
									
								});
							}
						}).catch(swal.noop);
						
					}
					else {
						
						deferred = atum_order_items.bulk_delete_items($rows);
						atum_order_items.askRemoval = true;
						
						if (deferred.length) {
							$.when.apply($, deferred).done(function () {
								atum_order_items.reload_items(function () {
									swal.close();
								});
							});
						}
						
					}
					
				}
				
			},
			
			bulk_delete_items: function($rows) {
				
				atum_order_items.block();
				
				var delete_items = [],
				    deferred     = [];
				
				$.map($rows, function (row) {
					delete_items.push( parseInt($(row).data('atum_order_item_id'), 10) );
					return;
				});
				
				if (delete_items.length) {
					
					deferred.push( $.ajax({
						url : ajaxurl,
						data: {
							atum_order_id      : atumOrder.post_id,
							atum_order_item_ids: delete_items,
							action             : 'atum_order_remove_item',
							security           : atumOrder.atum_order_item_nonce
						},
						type: 'POST'
					}) );
					
				}
				
				return deferred;
				
			},
			
			do_bulk_increase_stock: function( e ) {
				e.preventDefault();
				atum_order_items.bulk_change_stock('increase');
			},
			
			do_bulk_decrease_stock: function( e ) {
				e.preventDefault();
				atum_order_items.bulk_change_stock('decrease');
			},
			
			bulk_change_stock: function(action) {
				
				atum_order_items.block();
				
				swal({
					title              : atumOrder.are_you_sure,
					text               : (action === 'increase') ? atumOrder.increase_stock_msg : atumOrder.decrease_stock_msg,
					type               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : atumOrder.continue,
					cancelButtonText   : atumOrder.cancel,
					reverseButtons     : true,
					allowOutsideClick  : false,
					showLoaderOnConfirm: true,
					preConfirm         : function () {
						return new Promise(function (resolve, reject) {
							
							var $rows      = $('table.atum_order_items').find('tr.selected'),
							    quantities = {},
							    itemIds    = $.map($rows, function ($row) {
								    return parseInt($($row).data('atum_order_item_id'), 10);
							    });
							
							$rows.each(function () {
								if ($(this).find('input.quantity').length) {
									quantities[$(this).data('atum_order_item_id')] = $(this).find('input.quantity').val();
								}
							});
							
							$.ajax({
								url     : ajaxurl,
								data    : {
									atum_order_id      : atumOrder.post_id,
									atum_order_item_ids: itemIds,
									quantities         : quantities,
									action             : 'atum_order_' + action + '_items_stock',
									security           : atumOrder.atum_order_item_nonce
								},
								method  : 'POST',
								dataType: 'json',
								success : function (response) {
									
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
				}).then(function () {
					
					swal({
						title            : atumOrder.done,
						text             : (action === 'increase') ? atumOrder.stock_increased : atumOrder.stock_decreased,
						type             : 'success',
						confirmButtonText: atumOrder.ok
					});
					
					atum_order_items.unblock();
					
				}, function (dismiss) {
					atum_order_items.unblock();
				});
			
			},
			
			backbone: {
				
				init: function( e, target ) {
					if ( 'atum-modal-add-products' === target ) {
						$( document.body ).trigger( 'wc-enhanced-select-init' );
					}
				},
				
				response: function( e, target, data ) {
					
					if ( 'atum-modal-add-tax' === target ) {
						var rate_id        = data.add_atum_order_tax,
						    manual_rate_id = '';
						
						if ( data.manual_tax_rate_id ) {
							manual_rate_id = data.manual_tax_rate_id;
						}
						
						atum_order_items.backbone.add_tax( rate_id, manual_rate_id );
					}
					
					if ( 'atum-modal-add-products' === target ) {
						atum_order_items.backbone.add_item( data.add_atum_order_items );
					}
					
				},
				
				add_item: function( add_item_ids ) {
					
					if ( add_item_ids ) {
						atum_order_items.block();
						
						var data = {
							action       : 'atum_order_add_item',
							item_to_add  : add_item_ids,
							atum_order_id: atumOrder.post_id,
							security     : atumOrder.atum_order_item_nonce
						};
						
						$.post( ajaxurl, data, function( response ) {
							
							if ( response.success ) {
								$('#atum_order_line_items').append( response.data.html );
							}
							else {
								this.showalert('error', atumOrder.error, response.data.error);
							}
							
							atum_order_items.reloadTooltips();
							atum_order_items.unblock();
							
						}, 'json');
					}
					
				},
				
				add_tax: function( rate_id, manual_rate_id ) {
					
					if ( manual_rate_id ) {
						rate_id = manual_rate_id;
					}
					
					if ( ! rate_id ) {
						return false;
					}
					
					var rates = $('.atum-order-tax-id').map( function() {
						return $(this).val();
					}).get();
					
					// Test if already exists
					if ( -1 === $.inArray( rate_id, rates ) ) {
						
						atum_order_items.load_items_table({
							action       : 'atum_order_add_tax',
							rate_id      : rate_id,
							atum_order_id: atumOrder.post_id,
							security     : atumOrder.atum_order_item_nonce
						}, 'json');
						
					}
					else {
						this.showalert('error', atumOrder.error, atumOrder.tax_rate_already_exists);
					}
				}
			},
			
			stupidtable: {
				
				init: function() {
					$('table.atum_order_items').stupidtable();
					$('table.atum_order_items').on( 'aftertablesort', this.add_arrows );
				},
				
				add_arrows: function( event, data ) {
					
					var th    = $(this).find('th'),
					    arrow = data.direction === 'asc' ? '&uarr;' : '&darr;',
					    index = data.column;
					
					th.find( '.atum-arrow' ).remove();
					th.eq( index ).append( '<span class="atum-arrow">' + arrow + '</span>' );
				}
				
			},
			
			reloadTooltips: function() {
				
				this.$container.find('[data-toggle="tooltip"]').tooltip({
					container: 'body'
				});
			},
			
			toggleExtraFields: function() {
				
				var $atumOrderType = $(this),
				    typeValue      = $atumOrderType.val();
				
				$('[data-dependency]').each(function() {
					var dependency = $(this).data('dependency').split(':');
					
					if (dependency[0] === $atumOrderType.attr('id')) {
						if (dependency[1] === typeValue) {
							$(this).fadeIn();
						}
						else if ($(this).is(':visible')) {
							$(this).hide();
						}
					}
				});
			
			},
			
			toggleSupplierField: function() {
				
				var $body          = $('body'),
				    $dropdownField = $('.dropdown_supplier').parent(),
				    blockMultiple  = ($('#atum_order_has_multiple_suppliers').val() === 'false' && !$body.hasClass('post-new-php'));
				
				if ($(this).is(':checked')) {
					$body.addClass('allow-multiple-suppliers');
					$dropdownField.slideUp();
					if ( blockMultiple ) {
						atum_order_items.$itemsBlocker.removeClass('unblocked');
					}
					else {
						atum_order_items.$itemsBlocker.addClass('unblocked');
					}
				}
				else {
					$body.removeClass('allow-multiple-suppliers');
					$dropdownField.slideDown();
					
					if ($('#supplier').val() && blockMultiple) {
						atum_order_items.$itemsBlocker.addClass('unblocked');
					}
					else {
						atum_order_items.$itemsBlocker.removeClass('unblocked');
					}
				}
				
			},
			
			importOrderItems: function() {
				
				var $wcOrder = $('#wc_order'),
				    orderId  = $wcOrder.val();
				
				if (!orderId || atum_order_items.isEditable == 'false') {
					return false;
				}
				
				swal({
					text               : atumOrder.import_order_items,
					type               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : atumOrder.continue,
					cancelButtonText   : atumOrder.cancel,
					reverseButtons     : true,
					allowOutsideClick  : false,
					preConfirm         : function () {
						return new Promise(function (resolve, reject) {
							
							atum_order_items.load_items_table({
								action       : 'atum_order_import_items',
								wc_order_id  : orderId,
								atum_order_id: atumOrder.post_id,
								security     : atumOrder.import_order_items_nonce
							}, 'json', resolve);
							
						});
					}
				}).catch(swal.noop);
			
			},
			
			showalert: function(type, title, message) {
				
				swal({
					title: title,
					text: message,
					type: type,
					confirmButtonText: atumOrder.ok
				});
			
			}
		};
		
		/**
		 * ATUM Order Notes meta box
		 */
		var atum_order_notes = {
			
			init: function () {
				
				var self = this;
				
				this.$container = $('#atum_order_notes');
				this.$textarea = $('textarea#add_atum_order_note');
				
				this.$container.on('click', 'button.add_note', function() {
					self.add_note();
				})
				.on('click', 'a.delete_note', function() {
					self.delete_note($(this));
				});
				
			},
			
			add_note: function () {
				
				if (!this.$textarea.val()) {
					return;
				}
				
				this.$container.block({
					message   : null,
					overlayCSS: {
						background: '#000',
						opacity   : 0.5
					}
				});
				
				var data = {
					action   : 'atum_order_add_note',
					post_id  : $('#post_ID').val(),
					note     : this.$textarea.val(),
					security : atumOrder.add_note_nonce
				},
				self = this;
				
				$.post(ajaxurl, data, function (response) {
					$('ul.atum_order_notes').prepend(response);
					self.$container.unblock();
					self.$textarea.val('');
				});
				
				return false;
			},
			
			delete_note: function ($el) {
				
				var $note = $el.closest('li.note');
				
				swal({
					text              : atumOrder.delete_note,
					type               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : atumOrder.continue,
					cancelButtonText   : atumOrder.cancel,
					reverseButtons     : true,
					allowOutsideClick  : false,
					preConfirm         : function () {
						return new Promise(function (resolve, reject) {
							
							$note.block({
								message   : null,
								overlayCSS: {
									background: '#000',
									opacity   : 0.5
								}
							});
							
							var data = {
								action  : 'atum_order_delete_note',
								note_id : $note.attr('rel'),
								security: atumOrder.delete_note_nonce
							};
							
							$.post(ajaxurl, data, function () {
								resolve();
							});
							
						});
					}
				}).then(function () {
					$note.remove();
				}).catch(swal.noop);
				
				return false;
			}
		};
		
		// Initialize
		atum_order_notes.init();
		atum_order_items.init();
		
		// Init tooltips
		$('[data-toggle="tooltip"]').tooltip({
			container: 'body'
		});
		
		// Init datepickers
		$( '.date-picker' ).datepicker({
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 1,
			showButtonPanel: true
		});
		
		// Init switchers
		$('.js-switch').each(function () {
			new Switchery(this, { size: 'small' });
		});
	
	});
	
	
})(jQuery);
	
jQuery.noConflict();

/*!
 * Bootstrap v3.3.7 (http://getbootstrap.com)
 * Copyright 2011-2017 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */

/*!
 * Tooltip plugin
 */
+function(t){"use strict";var e=t.fn.jquery.split(" ")[0].split(".");if(e[0]<2&&e[1]<9||1==e[0]&&9==e[1]&&e[2]<1||e[0]>3)throw new Error("Bootstrap's JavaScript requires jQuery version 1.9.1 or higher, but lower than version 4")}(jQuery),+function(t){"use strict";function e(e){return this.each(function(){var o=t(this),n=o.data("bs.tooltip"),s="object"==typeof e&&e;!n&&/destroy|hide/.test(e)||(n||o.data("bs.tooltip",n=new i(this,s)),"string"==typeof e&&n[e]())})}var i=function(t,e){this.type=null,this.options=null,this.enabled=null,this.timeout=null,this.hoverState=null,this.$element=null,this.inState=null,this.init("tooltip",t,e)};i.VERSION="3.3.7",i.TRANSITION_DURATION=150,i.DEFAULTS={animation:!0,placement:"top",selector:!1,template:'<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",title:"",delay:0,html:!1,container:!1,viewport:{selector:"body",padding:0}},i.prototype.init=function(e,i,o){if(this.enabled=!0,this.type=e,this.$element=t(i),this.options=this.getOptions(o),this.$viewport=this.options.viewport&&t(t.isFunction(this.options.viewport)?this.options.viewport.call(this,this.$element):this.options.viewport.selector||this.options.viewport),this.inState={click:!1,hover:!1,focus:!1},this.$element[0]instanceof document.constructor&&!this.options.selector)throw new Error("`selector` option must be specified when initializing "+this.type+" on the window.document object!");for(var n=this.options.trigger.split(" "),s=n.length;s--;){var r=n[s];if("click"==r)this.$element.on("click."+this.type,this.options.selector,t.proxy(this.toggle,this));else if("manual"!=r){var a="hover"==r?"mouseenter":"focusin",l="hover"==r?"mouseleave":"focusout";this.$element.on(a+"."+this.type,this.options.selector,t.proxy(this.enter,this)),this.$element.on(l+"."+this.type,this.options.selector,t.proxy(this.leave,this))}}this.options.selector?this._options=t.extend({},this.options,{trigger:"manual",selector:""}):this.fixTitle()},i.prototype.getDefaults=function(){return i.DEFAULTS},i.prototype.getOptions=function(e){return e=t.extend({},this.getDefaults(),this.$element.data(),e),e.delay&&"number"==typeof e.delay&&(e.delay={show:e.delay,hide:e.delay}),e},i.prototype.getDelegateOptions=function(){var e={},i=this.getDefaults();return this._options&&t.each(this._options,function(t,o){i[t]!=o&&(e[t]=o)}),e},i.prototype.enter=function(e){var i=e instanceof this.constructor?e:t(e.currentTarget).data("bs."+this.type);return i||(i=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,i)),e instanceof t.Event&&(i.inState["focusin"==e.type?"focus":"hover"]=!0),i.tip().hasClass("in")||"in"==i.hoverState?void(i.hoverState="in"):(clearTimeout(i.timeout),i.hoverState="in",i.options.delay&&i.options.delay.show?void(i.timeout=setTimeout(function(){"in"==i.hoverState&&i.show()},i.options.delay.show)):i.show())},i.prototype.isInStateTrue=function(){for(var t in this.inState)if(this.inState[t])return!0;return!1},i.prototype.leave=function(e){var i=e instanceof this.constructor?e:t(e.currentTarget).data("bs."+this.type);return i||(i=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,i)),e instanceof t.Event&&(i.inState["focusout"==e.type?"focus":"hover"]=!1),i.isInStateTrue()?void 0:(clearTimeout(i.timeout),i.hoverState="out",i.options.delay&&i.options.delay.hide?void(i.timeout=setTimeout(function(){"out"==i.hoverState&&i.hide()},i.options.delay.hide)):i.hide())},i.prototype.show=function(){var e=t.Event("show.bs."+this.type);if(this.hasContent()&&this.enabled){this.$element.trigger(e);var o=t.contains(this.$element[0].ownerDocument.documentElement,this.$element[0]);if(e.isDefaultPrevented()||!o)return;var n=this,s=this.tip(),r=this.getUID(this.type);this.setContent(),s.attr("id",r),this.$element.attr("aria-describedby",r),this.options.animation&&s.addClass("fade");var a="function"==typeof this.options.placement?this.options.placement.call(this,s[0],this.$element[0]):this.options.placement,l=/\s?auto?\s?/i,p=l.test(a);p&&(a=a.replace(l,"")||"top"),s.detach().css({top:0,left:0,display:"block"}).addClass(a).data("bs."+this.type,this),this.options.container?s.appendTo(this.options.container):s.insertAfter(this.$element),this.$element.trigger("inserted.bs."+this.type);var h=this.getPosition(),u=s[0].offsetWidth,f=s[0].offsetHeight;if(p){var c=a,d=this.getPosition(this.$viewport);a="bottom"==a&&h.bottom+f>d.bottom?"top":"top"==a&&h.top-f<d.top?"bottom":"right"==a&&h.right+u>d.width?"left":"left"==a&&h.left-u<d.left?"right":a,s.removeClass(c).addClass(a)}var v=this.getCalculatedOffset(a,h,u,f);this.applyPlacement(v,a);var g=function(){var t=n.hoverState;n.$element.trigger("shown.bs."+n.type),n.hoverState=null,"out"==t&&n.leave(n)};t.support.transition&&this.$tip.hasClass("fade")?s.one("bsTransitionEnd",g).emulateTransitionEnd(i.TRANSITION_DURATION):g()}},i.prototype.applyPlacement=function(e,i){var o=this.tip(),n=o[0].offsetWidth,s=o[0].offsetHeight,r=parseInt(o.css("margin-top"),10),a=parseInt(o.css("margin-left"),10);isNaN(r)&&(r=0),isNaN(a)&&(a=0),e.top+=r,e.left+=a,t.offset.setOffset(o[0],t.extend({using:function(t){o.css({top:Math.round(t.top),left:Math.round(t.left)})}},e),0),o.addClass("in");var l=o[0].offsetWidth,p=o[0].offsetHeight;"top"==i&&p!=s&&(e.top=e.top+s-p);var h=this.getViewportAdjustedDelta(i,e,l,p);h.left?e.left+=h.left:e.top+=h.top;var u=/top|bottom/.test(i),f=u?2*h.left-n+l:2*h.top-s+p,c=u?"offsetWidth":"offsetHeight";o.offset(e),this.replaceArrow(f,o[0][c],u)},i.prototype.replaceArrow=function(t,e,i){this.arrow().css(i?"left":"top",50*(1-t/e)+"%").css(i?"top":"left","")},i.prototype.setContent=function(){var t=this.tip(),e=this.getTitle();t.find(".tooltip-inner")[this.options.html?"html":"text"](e),t.removeClass("fade in top bottom left right")},i.prototype.hide=function(e){function o(){"in"!=n.hoverState&&s.detach(),n.$element&&n.$element.removeAttr("aria-describedby").trigger("hidden.bs."+n.type),e&&e()}var n=this,s=t(this.$tip),r=t.Event("hide.bs."+this.type);return this.$element.trigger(r),r.isDefaultPrevented()?void 0:(s.removeClass("in"),t.support.transition&&s.hasClass("fade")?s.one("bsTransitionEnd",o).emulateTransitionEnd(i.TRANSITION_DURATION):o(),this.hoverState=null,this)},i.prototype.fixTitle=function(){var t=this.$element;(t.attr("title")||"string"!=typeof t.attr("data-original-title"))&&t.attr("data-original-title",t.attr("title")||"").attr("title","")},i.prototype.hasContent=function(){return this.getTitle()},i.prototype.getPosition=function(e){e=e||this.$element;var i=e[0],o="BODY"==i.tagName,n=i.getBoundingClientRect();null==n.width&&(n=t.extend({},n,{width:n.right-n.left,height:n.bottom-n.top}));var s=window.SVGElement&&i instanceof window.SVGElement,r=o?{top:0,left:0}:s?null:e.offset(),a={scroll:o?document.documentElement.scrollTop||document.body.scrollTop:e.scrollTop()},l=o?{width:t(window).width(),height:t(window).height()}:null;return t.extend({},n,a,l,r)},i.prototype.getCalculatedOffset=function(t,e,i,o){return"bottom"==t?{top:e.top+e.height,left:e.left+e.width/2-i/2}:"top"==t?{top:e.top-o,left:e.left+e.width/2-i/2}:"left"==t?{top:e.top+e.height/2-o/2,left:e.left-i}:{top:e.top+e.height/2-o/2,left:e.left+e.width}},i.prototype.getViewportAdjustedDelta=function(t,e,i,o){var n={top:0,left:0};if(!this.$viewport)return n;var s=this.options.viewport&&this.options.viewport.padding||0,r=this.getPosition(this.$viewport);if(/right|left/.test(t)){var a=e.top-s-r.scroll,l=e.top+s-r.scroll+o;a<r.top?n.top=r.top-a:l>r.top+r.height&&(n.top=r.top+r.height-l)}else{var p=e.left-s,h=e.left+s+i;p<r.left?n.left=r.left-p:h>r.right&&(n.left=r.left+r.width-h)}return n},i.prototype.getTitle=function(){var t,e=this.$element,i=this.options;return t=e.attr("data-original-title")||("function"==typeof i.title?i.title.call(e[0]):i.title)},i.prototype.getUID=function(t){do t+=~~(1e6*Math.random());while(document.getElementById(t));return t},i.prototype.tip=function(){if(!this.$tip&&(this.$tip=t(this.options.template),1!=this.$tip.length))throw new Error(this.type+" `template` option must consist of exactly 1 top-level element!");return this.$tip},i.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".tooltip-arrow")},i.prototype.enable=function(){this.enabled=!0},i.prototype.disable=function(){this.enabled=!1},i.prototype.toggleEnabled=function(){this.enabled=!this.enabled},i.prototype.toggle=function(e){var i=this;e&&(i=t(e.currentTarget).data("bs."+this.type),i||(i=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,i))),e?(i.inState.click=!i.inState.click,i.isInStateTrue()?i.enter(i):i.leave(i)):i.tip().hasClass("in")?i.leave(i):i.enter(i)},i.prototype.destroy=function(){var t=this;clearTimeout(this.timeout),this.hide(function(){t.$element.off("."+t.type).removeData("bs."+t.type),t.$tip&&t.$tip.detach(),t.$tip=null,t.$arrow=null,t.$viewport=null,t.$element=null})};var o=t.fn.tooltip;t.fn.tooltip=e,t.fn.tooltip.Constructor=i,t.fn.tooltip.noConflict=function(){return t.fn.tooltip=o,this}}(jQuery),+function(t){"use strict";function e(){var t=document.createElement("bootstrap"),e={WebkitTransition:"webkitTransitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd otransitionend",transition:"transitionend"};for(var i in e)if(void 0!==t.style[i])return{end:e[i]};return!1}t.fn.emulateTransitionEnd=function(e){var i=!1,o=this;t(this).one("bsTransitionEnd",function(){i=!0});var n=function(){i||t(o).trigger(t.support.transition.end)};return setTimeout(n,e),this},t(function(){t.support.transition=e(),t.support.transition&&(t.event.special.bsTransitionEnd={bindType:t.support.transition.end,delegateType:t.support.transition.end,handle:function(e){return t(e.target).is(this)?e.handleObj.handler.apply(this,arguments):void 0}})})}(jQuery);