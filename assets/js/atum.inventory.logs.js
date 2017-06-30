/**
 * Atum Inventory Logs
 *
 * @copyright Stock Management Labs ©2017
 * @since 1.2.4
 */

(function ($) {
	'use strict';
	
	$(function () {
		
		/**
		 * Log Items meta box
		 * Based on the WooCommerce Order's "items meta box"
		 */
		var atum_log_items = {
			
			init: function() {
				
				this.$container = $( '#atum_inventory_log_items' );
				this.stupidtable.init();
				this.isEditable = $('#log_is_editable').val();
				
				// Bind events
				this.$container
					.on( 'click', 'button.add-line-item', this.add_line_item )
					.on( 'click', '.cancel-action', this.cancel )
					.on( 'click', 'button.add-log-item', this.add_item )
					.on( 'click', 'button.add-log-fee', this.add_fee )
					.on( 'click', 'button.add-log-shipping', this.add_shipping )
					.on( 'click', 'button.add-log-tax', this.add_tax )
					.on( 'click', 'button.save-action', this.save_line_items )
					.on( 'click', 'a.delete-log-tax', this.delete_tax )
					.on( 'click', 'button.calculate-action', this.recalculate )
					.on( 'click', 'a.edit-log-item', this.edit_item )
					.on( 'click', 'a.delete-log-item', this.delete_item )
					.on( 'click', 'tr.item, tr.fee, tr.shipping', this.select_row )
					.on( 'click', 'tr.item :input, tr.fee :input, tr.shipping :input, tr.item a, tr.fee a, tr.shipping a', this.select_row_child )
					.on( 'click', 'button.bulk-delete-items', this.do_bulk_delete )
					/*.on( 'click', 'button.bulk-increase-stock', this.do_bulk_increase_stock )
					.on( 'click', 'button.bulk-decrease-stock', this.do_bulk_reduce_stock )*/
					
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
					.on( 'click', 'button.add_log_item_meta', this.item_meta.add )
					.on( 'click', 'button.remove_log_item_meta', this.item_meta.remove );
				
				$( document.body )
					.on( 'wc_backbone_modal_loaded', this.backbone.init )
					.on( 'wc_backbone_modal_response', this.backbone.response );
				
				// Trigger log type dependent fields
				$('#log_type').change(this.toggleExtraFields);
				
				// Ask for importing the order items after linking an order
				$('#log_order').change(this.importOrderItems);
				
			},
			
			block: function() {
				
				this.$container.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
				
			},
			
			unblock: function() {
				this.$container.unblock();
			},
			
			reload_items: function() {
				
				this.load_items_table({
					log_id  : atumInventoryLogs.post_id,
					action  : 'atum_load_log_items',
					security: atumInventoryLogs.log_item_nonce
				});
			},
			
			load_items_table: function(data, dataType) {
				
				var self = this;
				this.block();
				dataType = (typeof dataType !== 'undefined') ? dataType : 'html';
				
				$.ajax({
					url:  ajaxurl,
					data: data,
					dataType: dataType,
					type: 'POST',
					success: function( response ) {
						
						if ( (typeof response === 'object' && response.success === true) || typeof response !== 'object') {
							var itemsTable = (dataType === 'html') ? response : response.data.html;
							self.$container.find( '.inside' ).empty().append( itemsTable );
							self.reloadTooltips();
							self.stupidtable.init();
						}
						else if (typeof response === 'object' && response.success === false) {
							window.alert( response.data.error );
						}
						
						self.unblock();
						
					}
				});
				
			},
			
			// When the qty is changed, increase or decrease costs
			quantity_changed: function() {
				
				var $row          = $(this).closest('tr.item'),
				    qty           = $(this).val(),
				    o_qty         = $(this).attr('data-qty'),
				    line_total    = $('input.line_total', $row),
				    line_subtotal = $('input.line_subtotal', $row);
				
				// Totals
				var unit_total = accounting.unformat( line_total.attr( 'data-total' ), atumInventoryLogs.mon_decimal_point ) / o_qty;
				line_total.val(
					parseFloat( accounting.formatNumber( unit_total * qty, atumInventoryLogs.rounding_precision, '' ) )
						.toString()
						.replace( '.', atumInventoryLogs.mon_decimal_point )
				);
				
				var unit_subtotal = accounting.unformat( line_subtotal.attr( 'data-subtotal' ), atumInventoryLogs.mon_decimal_point ) / o_qty;
				line_subtotal.val(
					parseFloat( accounting.formatNumber( unit_subtotal * qty, atumInventoryLogs.rounding_precision, '' ) )
						.toString()
						.replace( '.', atumInventoryLogs.mon_decimal_point )
				);
				
				// Taxes
				$( 'input.line_tax', $row ).each( function() {
					
					var $line_total_tax    = $(this),
					    tax_id             = $line_total_tax.data('tax_id'),
					    unit_total_tax     = accounting.unformat($line_total_tax.attr('data-total_tax'), atumInventoryLogs.mon_decimal_point) / o_qty,
					    $line_subtotal_tax = $('input.line_subtotal_tax[data-tax_id="' + tax_id + '"]', $row),
					    unit_subtotal_tax  = accounting.unformat($line_subtotal_tax.attr('data-subtotal_tax'), atumInventoryLogs.mon_decimal_point) / o_qty;
					
					if ( 0 < unit_total_tax ) {
						$line_total_tax.val(
							parseFloat( accounting.formatNumber( unit_total_tax * qty, atumInventoryLogs.rounding_precision, '' ) )
								.toString()
								.replace( '.', atumInventoryLogs.mon_decimal_point )
						);
					}
					
					if ( 0 < unit_subtotal_tax ) {
						$line_subtotal_tax.val(
							parseFloat( accounting.formatNumber( unit_subtotal_tax * qty, atumInventoryLogs.rounding_precision, '' ) )
								.toString()
								.replace( '.', atumInventoryLogs.mon_decimal_point )
						);
					}
				});
				
				$( this ).trigger( 'quantity_changed' );
			},
			
			add_line_item: function() {
				
				$( 'div.atum-log-add-item' ).slideDown();
				$( 'div.atum-log-data-row-toggle' ).not( 'div.atum-log-add-item' ).slideUp();
				
				return false;
				
			},
			
			cancel: function() {
				
				$( 'div.atum-log-data-row-toggle' ).not( 'div.atum-log-bulk-actions' ).slideUp();
				$( 'div.atum-log-bulk-actions, div.atum-log-totals-items' ).slideDown();
				$( '.atum-log-edit-line-item .atum-log-edit-line-item-actions' ).show();
				
				// Reload the items
				if ( 'true' === $( this ).attr( 'data-reload' ) ) {
					atum_log_items.reload_items();
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
				
				atum_log_items.block();
				
				var data = {
					action  : 'atum_add_log_fee',
					log_id  : atumInventoryLogs.post_id,
					dataType: 'json',
					security: atumInventoryLogs.log_item_nonce
				};
				
				$.post( ajaxurl, data, function( response ) {
					
					if ( response.success ) {
						$( 'table.atum_log_items tbody#log_fee_line_items' ).append( response.data.html );
					}
					else {
						window.alert( response.data.error );
					}
					
					atum_log_items.unblock();
					
				});
				
				return false;
				
			},
			
			add_shipping: function() {
				
				atum_log_items.block();
				
				var data = {
					action  : 'atum_add_log_shipping',
					log_id  : atumInventoryLogs.post_id,
					security: atumInventoryLogs.log_item_nonce,
					dataType: 'json'
				};
				
				$.post( ajaxurl, data, function( response ) {
					
					if ( response.success ) {
						$( 'table.atum_log_items tbody#log_shipping_line_items' ).append( response.data.html );
					}
					else {
						window.alert( response.data.error );
					}
					
					atum_log_items.unblock();
				});
				
				return false;
				
			},
			
			add_tax: function() {
				
				$(this).WCBackboneModal({
					template: 'atum-modal-add-tax'
				});
				return false;
				
			},
			
			edit_item: function() {
				
				$( this ).closest( 'tr' ).find( '.view' ).hide();
				$( this ).closest( 'tr' ).find( '.edit' ).show();
				$( this ).hide();
				$( 'button.add-line-item' ).click();
				$( 'button.cancel-action' ).attr( 'data-reload', true );
				
				return false;
				
			},
			
			delete_item: function() {
				
				var answer = window.confirm( atumInventoryLogs.remove_item_notice );
				
				if ( answer ) {
					
					var $item       = $(this).closest('tr.item, tr.fee, tr.shipping'),
					    log_item_id = $item.attr('data-log_item_id');
					
					atum_log_items.block();
					
					$.ajax({
						url:     ajaxurl,
						data:    {
							log_id      : atumInventoryLogs.post_id,
							log_item_ids: log_item_id,
							action      : 'atum_remove_log_item',
							security    : atumInventoryLogs.log_item_nonce
						},
						type:    'POST',
						success: function() {
							$item.remove();
							atum_log_items.unblock();
						}
					});
				}
				
				return false;
				
			},
			
			delete_tax: function() {
				
				if ( window.confirm( atumInventoryLogs.delete_tax_notice ) ) {
					
					atum_log_items.load_items_table({
						action  : 'atum_remove_log_tax',
						rate_id : $(this).attr('data-rate_id'),
						log_id  : atumInventoryLogs.post_id,
						security: atumInventoryLogs.log_item_nonce
					});
					
				}
				
				return false;
				
			},
			
			recalculate: function() {
				
				if ( window.confirm( atumInventoryLogs.calc_totals ) ) {
					
					/*var country  = '',
					    state    = '',
					    postcode = '',
					    city     = '';
					
					if ( 'shipping' === atumInventoryLogs.tax_based_on ) {
						country  = $( '#_shipping_country' ).val();
						state    = $( '#_shipping_state' ).val();
						postcode = $( '#_shipping_postcode' ).val();
						city     = $( '#_shipping_city' ).val();
					}
					
					if ( 'billing' === atumInventoryLogs.tax_based_on || !country ) {
						country  = $( '#_billing_country' ).val();
						state    = $( '#_billing_state' ).val();
						postcode = $( '#_billing_postcode' ).val();
						city     = $( '#_billing_city' ).val();
					}*/
					
					atum_log_items.load_items_table({
						action  : 'atum_calc_line_taxes',
						log_id  : atumInventoryLogs.post_id,
						items   : $('table.atum_log_items :input[name], .atum-log-totals-items :input[name]').serialize(),
						/*country : country,
						 state   : state,
						 postcode: postcode,
						 city    : city,*/
						security: atumInventoryLogs.calc_totals_nonce
					});
					
				}
				
				return false;
				
			},
			
			save_line_items: function() {
				
				atum_log_items.load_items_table({
					log_id  : atumInventoryLogs.post_id,
					items   : $('table.atum_log_items :input[name], .atum-log-totals-items :input[name]').serialize(),
					action  : 'atum_save_log_items',
					security: atumInventoryLogs.log_item_nonce
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
						    '<input type="text" placeholder="' + atumInventoryLogs.placeholder_name + '" name="meta_key[' + $item.attr('data-log_item_id') + '][new-' + index + ']" />' +
						    '<textarea placeholder="' + atumInventoryLogs.placeholder_value + '" name="meta_value[' + $item.attr('data-log_item_id') + '][new-' + index + ']"></textarea>' +
						    '</td>' +
						    '<td width="1%"><button class="remove_log_item_meta button">&times;</button></td>' +
						    '</tr>';
					
					$items.append( $row );
					
					return false;
					
				},
				
				remove: function() {
					
					if ( window.confirm( atumInventoryLogs.remove_item_meta ) ) {
						var $row = $( this ).closest( 'tr' );
						$row.find( ':input' ).val( '' );
						$row.hide();
					}
					return false;
					
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
				
				var $rows  = $table.find('tr.selected');
				
				if ($rows.length) {
					$('div.atum-log-item-bulk-edit').slideDown();
					
					var selected_product = false;
					
					$rows.each(function () {
						if ($(this).is('tr.item')) {
							selected_product = true;
						}
					});
					
					/*if (selected_product) {
						$('.bulk-increase-stock, .bulk-decrease-stock').show();
					}
					else {
						$('.bulk-increase-stock, .bulk-decrease-stock').hide();
					}*/
				}
				else {
					$('div.atum-log-item-bulk-edit').slideUp();
				}
				
			},
			
			select_row_child: function( e ) {
				e.stopPropagation();
			},
			
			do_bulk_delete: function( e ) {
				
				e.preventDefault();
				var $table = $('table.atum_log_items'),
				    $rows  = $table.find('tr.selected');
				
				if ($rows.length && window.confirm(atumInventoryLogs.remove_item_notice)) {
					
					atum_log_items.block();
					
					var delete_items = [],
					    deferred     = [];
					
					$.map($rows, function (row) {
						
						delete_items.push( parseInt($(row).data('log_item_id'), 10) );
						return;
						
					});
					
					if (delete_items.length) {
						
						deferred.push($.ajax({
							url : ajaxurl,
							data: {
								log_id      : atumInventoryLogs.post_id,
								log_item_ids: delete_items,
								action      : 'atum_remove_log_item',
								security    : atumInventoryLogs.log_item_nonce
							},
							type: 'POST'
						}));
						
					}
					
					if (deferred) {
						
						$.when.apply($, deferred).done(function () {
							atum_log_items.reload_items();
							atum_log_items.unblock();
						});
						
					}
					else {
						atum_log_items.unblock();
					}
				}
				
			},
			
			/*do_bulk_increase_stock: function( e ) {
				
				e.preventDefault();
				atum_log_items.block();
				
				var $table     = $('table.atum_log_items'),
				    $rows      = $table.find('tr.selected'),
				    quantities = {},
				    item_ids   = $.map($rows, function ($row) {
					    return parseInt($($row).data('log_item_id'), 10);
				    });
				
				$rows.each(function () {
					if ($(this).find('input.quantity').length) {
						quantities[$(this).attr('data-log_item_id')] = $(this).find('input.quantity').val();
					}
				});
				
				var data = {
					log_id      : atumInventoryLogs.post_id,
					log_item_ids: item_ids,
					log_item_qty: quantities,
					action      : 'atum_increase_log_item_stock',
					security    : atumInventoryLogs.log_item_nonce
				};
				
				$.ajax({
					url    : ajaxurl,
					data   : data,
					type   : 'POST',
					success: function (response) {
						window.alert(response);
						atum_log_items.unblock();
					}
				});
				
			},
			
			do_bulk_reduce_stock: function( e ) {
				
				e.preventDefault();
				atum_log_items.block();
				
				var $table     = $('table.atum_log_items'),
				    $rows      = $table.find('tr.selected'),
				    quantities = {},
				    item_ids   = $.map($rows, function ($row) {
					    return parseInt($($row).data('log_item_id'), 10);
				    });
				
				$rows.each(function () {
					if ($(this).find('input.quantity').length) {
						quantities[$(this).attr('data-log_item_id')] = $(this).find('input.quantity').val();
					}
				});
				
				var data = {
					log_id      : atumInventoryLogs.post_id,
					log_item_ids: item_ids,
					log_item_qty: quantities,
					action      : 'atum_reduce_log_item_stock',
					security    : atumInventoryLogs.log_item_nonce
				};
				
				$.ajax({
					url    : ajaxurl,
					data   : data,
					type   : 'POST',
					success: function (response) {
						window.alert(response);
						atum_log_items.unblock();
					}
				});
				
			},*/
			
			backbone: {
				
				init: function( e, target ) {
					if ( 'atum-modal-add-products' === target ) {
						$( document.body ).trigger( 'wc-enhanced-select-init' );
					}
				},
				
				response: function( e, target, data ) {
					
					if ( 'atum-modal-add-tax' === target ) {
						var rate_id        = data.add_log_tax,
						    manual_rate_id = '';
						
						if ( data.manual_tax_rate_id ) {
							manual_rate_id = data.manual_tax_rate_id;
						}
						
						atum_log_items.backbone.add_tax( rate_id, manual_rate_id );
					}
					
					if ( 'atum-modal-add-products' === target ) {
						atum_log_items.backbone.add_item( data.add_log_items );
					}
					
				},
				
				add_item: function( add_item_ids ) {
					
					if ( add_item_ids ) {
						atum_log_items.block();
						
						var data = {
							action     : 'atum_add_log_item',
							item_to_add: add_item_ids,
							dataType   : 'json',
							log_id     : atumInventoryLogs.post_id,
							security   : atumInventoryLogs.log_item_nonce
						};
						
						$.post( ajaxurl, data, function( response ) {
							
							if ( response.success ) {
								$( 'table.atum_log_items tbody#log_line_items' ).append( response.data.html );
							}
							else {
								window.alert( response.data.error );
							}
							
							atum_log_items.reloadTooltips();
							atum_log_items.unblock();
							
						});
					}
					
				},
				
				add_tax: function( rate_id, manual_rate_id ) {
					
					if ( manual_rate_id ) {
						rate_id = manual_rate_id;
					}
					
					if ( ! rate_id ) {
						return false;
					}
					
					var rates = $( '.log-tax-id' ).map( function() {
						return $( this ).val();
					}).get();
					
					// Test if already exists
					if ( -1 === $.inArray( rate_id, rates ) ) {
						
						atum_log_items.load_items_table({
							action  : 'atum_add_log_tax',
							rate_id : rate_id,
							log_id  : atumInventoryLogs.post_id,
							security: atumInventoryLogs.log_item_nonce
						}, 'json');
						
					}
					else {
						window.alert( atumInventoryLogs.tax_rate_already_exists );
					}
				}
			},
			
			stupidtable: {
				
				init: function() {
					$( '.atum_log_items' ).stupidtable();
					$( '.atum_log_items' ).on( 'aftertablesort', this.add_arrows );
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
				
				var $logType     = $(this),
				    logTypeValue = $logType.val();
				
				$('[data-dependency]').each(function() {
					var dependency = $(this).data('dependency').split(':');
					
					if (dependency[0] === $logType.attr('id')) {
						if (dependency[1] === logTypeValue) {
							$(this).fadeIn();
						}
						else if ($(this).is(':visible')) {
							$(this).hide();
						}
					}
				});
			
			},
			
			importOrderItems: function() {
				
				var $logOrder = $('#log_order'),
				    orderId   = $logOrder.val();
				
				if (!orderId || atum_log_items.isEditable == 'false') {
					return false;
				}
				
				var	c = confirm(atumInventoryLogs.import_order_items);
				
				if (c === true) {
					
					atum_log_items.load_items_table({
						action     : 'atum_import_order_items',
						order_id   : orderId,
						log_id     : atumInventoryLogs.post_id,
						security   : atumInventoryLogs.import_order_items_nonce
					}, 'json');
					
				}
			
			}
		};
		
		/**
		 * Log Notes meta box
		 */
		var atum_log_notes = {
			
			init: function () {
				
				var self = this;
				
				this.$container = $('#atum_inventory_log_notes');
				this.$textarea = $('textarea#add_log_note');
				
				this.$container.on('click', 'button.add_note', function() {
					self.add_log_note();
				})
				.on('click', 'a.delete_note', function() {
					self.delete_log_note($(this));
				});
				
			},
			
			add_log_note: function () {
				
				if (!this.$textarea.val()) {
					return;
				}
				
				this.$container.block({
					message   : null,
					overlayCSS: {
						background: '#fff',
						opacity   : 0.6
					}
				});
				
				var data = {
					action   : 'atum_add_log_note',
					post_id  : $('#post_ID').val(),
					note     : this.$textarea.val(),
					security : atumInventoryLogs.add_log_note_nonce
				},
				self = this;
				
				$.post(ajaxurl, data, function (response) {
					$('ul.log_notes').prepend(response);
					self.$container.unblock();
					self.$textarea.val('');
				});
				
				return false;
			},
			
			delete_log_note: function ($el) {
				
				if (window.confirm(atumInventoryLogs.delete_note)) {
					
					var $note = $el.closest('li.note');
					
					$note.block({
						message   : null,
						overlayCSS: {
							background: '#fff',
							opacity   : 0.6
						}
					});
					
					var data = {
						action  : 'atum_delete_log_note',
						note_id : $note.attr('rel'),
						security: atumInventoryLogs.delete_log_note_nonce
					};
					
					$.post(ajaxurl, data, function () {
						$note.remove();
					});
				}
				
				return false;
			}
		};
		
		// Initialize
		atum_log_notes.init();
		atum_log_items.init();
		
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