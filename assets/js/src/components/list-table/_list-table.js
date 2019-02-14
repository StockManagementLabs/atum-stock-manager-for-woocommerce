/* =======================================
   LIST TABLE
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';
import Utils from '../../utils/_utils';
import Tooltip from '../_tooltip';
import EnhancedSelect from '../_enhanced-select';

let ListTable = {
	
	doingAjax  : null,
	isRowExpanding : {},
	
	init() {
		
		// Bind events.
		this.events();
		
	},
	
	/**
	 * Bind List Table events
	 */
	events() {
		
		let self = this;
		
		// Bind active class rows.
		this.addActiveClassRow();
		
		Globals.$atumList
		
			//
			// Trigger expanding/collapsing event in inheritable products.
			// ------------------------------------------
			.on('click', '.calc_type .has-child', (evt) => {
				$(evt.target).closest('tr').trigger('atum-list-expand-row');
			})
			//
			// Triggers the expand/collapse row action
			//
			.on('atum-list-expand-row', 'tbody tr', (evt, expandableRowClass, stopRowSelector, stopPropagation) => {
				self.expandRow($(evt.target), expandableRowClass, stopRowSelector, stopPropagation);
			})
			
			//
			// Expandable rows' checkboxes.
			// ----------------------------
			.on('change', '.check-column input:checkbox', (evt) => self.checkDescendats( $(evt.target) ))
			
			//
			// "Control all products" button.
			// ------------------------------
			.on('click', '#control-all-products', (evt) => {
				
				let $button = $(evt.target);
				
				$.ajax({
					url       : ajaxurl,
					method    : 'POST',
					dataType  : 'json',
					beforeSend: () => $button.prop('disabled', true).after('<span class="atum-spinner"><span></span></span>'),
					data      : {
						token : $(this).data('nonce'),
						action: 'atum_control_all_products',
					},
					success   : () => location.reload(),
				});
				
			});
		
		
		//
		// Global save for edited cells.
		// -----------------------------
		$('body').on('click', '#atum-update-list', (evt) => self.saveData($(evt.target)));
		
		
		//
		// Warn the user about unsaved changes before navigating away.
		// -----------------------------------------------------------
		$(window).bind('beforeunload', () => {
			
			if (!Globals.$editInput.val()) {
				return;
			}
			
			// Prevent multiple prompts - seen on Chrome and IE.
			if (navigator.userAgent.toLowerCase().match(/msie|chrome/)) {
				
				if (window.aysHasPrompted) {
					return;
				}
				
				window.aysHasPrompted = true;
				window.setTimeout( () => {
					window.aysHasPrompted = false;
				}, 900);
				
			}
			
			return false;
			
		})
		
		// Display hidden footer.
		.on('load', () => $('#wpfooter').show());
		
	},
	
	/**
	 * Add/remove row active class when checkbox is clicked.
	 */
	addActiveClassRow() {
		
		Globals.$atumList.find('tbody .check-column input:checkbox').change( (evt) => {
			
			let $checkboxRow = Globals.$atumList.find("[data-id='" + $(evt.target).val() + "']");
			
			if ($(this).is(':checked')) {
				$checkboxRow.addClass('active-row');
			}
			else {
				$checkboxRow.removeClass('active-row');
			}
			
		});
		
		// Selet all rows checkbox.
		$('#cb-select-all-1').change( () => {
			
			Globals.$atumTable.find('tbody tr').each( (index, elem) => {
				
				let $elem = $(elem);
				
				if ($elem.find('.check-column input[type=checkbox]').is(':checked')) {
					$elem.addClass('active-row');
				}
				else {
					$elem.removeClass('active-row');
				}
				
			});
			
		});
		
	},
	
	/**
	 * Send the ajax call and replace table parts with updated version
	 */
	updateTable() {
		
		let self = this;
		
		if (this.doingAjax && this.doingAjax.readyState !== 4) {
			this.doingAjax.abort();
		}
		
		// Overwrite the filterData with the URL hash parameters
		Globals.filterData = $.extend(Globals.filterData, {
			view          : $.address.parameter('view') || '',
			product_cat   : $.address.parameter('product_cat') || '',
			product_type  : $.address.parameter('product_type') || '',
			supplier      : $.address.parameter('supplier') || '',
			extra_filter  : $.address.parameter('extra_filter') || '',
			paged         : $.address.parameter('paged') || '',
			order         : $.address.parameter('order') || '',
			orderby       : $.address.parameter('orderby') || '',
			search_column : $.address.parameter('search_column') || '',
			sold_last_days: $.address.parameter('sold_last_days') || '',
			s             : $.address.parameter('s') || '',
		});
		
		this.doingAjax = $.ajax({
			url       : ajaxurl,
			dataType  : 'json',
			method    : 'GET',
			data      : Globals.filterData,
			beforeSend: () => {
				Tooltip.destroyTooltips();
				self.addOverlay();
			},
			// Handle the successful result.
			success   : (response) => {
				
				self.doingAjax = null;
				
				if (typeof response === 'undefined' || !response) {
					return false;
				}
				
				// Update table with the coming rows.
				if (typeof response.rows !== 'undefined' && response.rows.length) {
					Globals.$atumList.find('#the-list').html(response.rows);
					self.restoreMeta();
				}
				
				// Change page url parameter.
				if (response.paged > 0) {
					$.address.parameter('paged', response.paged);
				}
				
				// Update column headers for sorting.
				if (typeof response.column_headers !== 'undefined' && response.column_headers.length) {
					Globals.$atumList.find('tr.item-heads').html(response.column_headers);
				}
				
				// Update the views filters.
				if (typeof response.views !== 'undefined' && response.views.length) {
					Globals.$atumList.find('.subsubsub').replaceWith(response.views);
				}
				
				// Update table navs.
				if (typeof response.extra_t_n !== 'undefined') {
					
					if (response.extra_t_n.top.length) {
						Globals.$atumList.find('.tablenav.top').replaceWith(response.extra_t_n.top);
					}
					
					if (response.extra_t_n.bottom.length) {
						Globals.$atumList.find('.tablenav.bottom').replaceWith(response.extra_t_n.bottom);
					}
					
				}
				
				// Update the totals row.
				if (typeof response.totals !== 'undefined') {
					Globals.$atumList.find('tfoot tr.totals').html(response.totals);
				}
				
				// If there are active filters, show the reset button.
				if ($.address.parameterNames().length) {
					Globals.$atumList.find('.reset-filters').removeClass('hidden');
				}
				
				// Regenerate the UI.
				Tooltip.addTooltips();
				EnhancedSelect.maybeRestoreEnhancedSelect();
				self.addActiveClassRow();
				self.removeOverlay();
				
				// Custom trigger after updating.
				Globals.$atumList.trigger('atum-table-updated');
				
			},
			error     : () => self.removeOverlay(),
		});
		
	},
	
	/**
	 * Add the overlay effect while loading data
	 */
	addOverlay() {
		
		$('.atum-table-wrapper').block({
			message   : null,
			overlayCSS: {
				background: '#000',
				opacity   : 0.5,
			},
		});
		
	},
	
	/**
	 * Remove the overlay effect once the data is fully loaded
	 */
	removeOverlay() {
		$('.atum-table-wrapper').unblock();
	},
	
	/**
	 * Set the table cell value with right format
	 *
	 * @param jQuery        $metaCell  The cell where will go the value.
	 * @param String|Number value      The value to set in the cell.
	 */
	setCellValue($metaCell, value) {
		
		let symbol      = $metaCell.data('symbol') || '',
		    currencyPos = Globals.$atumTable.data('currency-pos');
		
		if (symbol) {
			value = currencyPos === 'left' ? symbol + value : value + symbol;
		}
		
		$metaCell.addClass('unsaved').text(value);
		
	},
	
	/**
	 * Restore the edited meta after loading new table rows
	 */
	restoreMeta() {
		
		let self       = this,
		    editedCols = Globals.$editInput.val();
		
		if (editedCols) {
			
			editedCols = $.parseJSON(editedCols);
			
			$.each( editedCols, (itemId, meta) => {
				
				// Filter the meta cell that was previously edited.
				let $metaCell = $('tr[data-id="' + itemId + '"] .set-meta');
				
				if ($metaCell.length) {
					
					$.each(meta, (key, value) => {
						
						$metaCell = $metaCell.filter('[data-meta="' + key + '"]');
						
						if ($metaCell.length) {
							
							self.setCellValue($metaCell, value);
							
							// Add the extra meta too.
							let extraMeta = $metaCell.data('extra-meta');
							
							if (typeof extraMeta === 'object') {
								
								$.each(extraMeta, (index, extraMetaObj) => {
									
									// Restore the extra meta from the edit input
									if (editedCols[itemId].hasOwnProperty(extraMetaObj.name)) {
										extraMeta[index]['value'] = editedCols[itemId][extraMetaObj.name];
									}
									
								})
								
								$metaCell.data('extra-meta', extraMeta);
								
							}
							
						}
						
					});
					
				}
				
			});
			
		}
		
	},
	
	/**
	 * Every time a cell is edited, update the input value
	 *
	 * @param jQuery $metaCell  The table cell that is being edited.
	 * @param jQuery $popover   The popover attached to the above cell.
	 */
	updateEditedColsInput($metaCell, $popover) {
		
		let editedCols = Globals.$editInput.val(),
		    itemId     = $metaCell.closest('tr').data('id'),
		    meta       = $metaCell.data('meta'),
		    symbol     = $metaCell.data('symbol') || '',
		    custom     = $metaCell.data('custom') || 'no',
		    currency   = $metaCell.data('currency') || '',
		    value      = symbol ? $metaCell.text().replace(symbol, '') : $metaCell.text(),
		    newValue   = $popover.find('.meta-value').val();
		
		// Update the cell value.
		this.setCellValue($metaCell, newValue);
		
		// Initialize the JSON object.
		if (editedCols) {
			editedCols = $.parseJSON(editedCols);
		}
		
		editedCols = editedCols || {};
		
		if (!editedCols.hasOwnProperty(itemId)) {
			editedCols[itemId] = {};
		}
		
		if (!editedCols[itemId].hasOwnProperty(meta)) {
			editedCols[itemId][meta] = {};
		}
		
		// Add the meta value to the object.
		editedCols[itemId][meta] = newValue;
		editedCols[itemId][meta + '_custom'] = custom;
		editedCols[itemId][meta + '_currency'] = currency;
		
		// Add the extra meta data (if any).
		if ($popover.hasClass('with-meta')) {
			
			let extraMeta = $metaCell.data('extra-meta');
			
			$popover.find('input').not('.meta-value').each( (index, input) => {
				
				let value = $(input).val();
				editedCols[itemId][input.name] = value;
				
				// Save the meta values in the cell data for future uses.
				if (typeof extraMeta === 'object') {
					
					$.each(extraMeta, (index, elem) => {
						
						if (elem.name === input.name) {
							extraMeta[index]['value'] = value;
							
							return false;
						}
						
					});
					
				}
				
			});
			
		}
		
		Globals.$editInput.val( JSON.stringify(editedCols) );
		Globals.$atumList.trigger('atum-edited-cols-input-updated', [$metaCell]);
		
	},
	
	/**
	 * Check if we need to add the Update button
	 */
	maybeAddSaveButton() {
		
		let $tableTitle = Globals.$atumList.siblings('.wp-heading-inline');
		
		if (!$tableTitle.find('#atum-update-list').length) {
			
			$tableTitle.append($('<button/>', {
				id   : 'atum-update-list',
				class: 'page-title-action button-primary',
				text : Settings.get('saveButton'),
			}));
			
			// Check whether to show the first edit popup.
			if (typeof swal === 'function' && typeof Settings.get('firstEditKey') !== 'undefined') {
				
				swal({
					title             : Settings.get('important'),
					text              : Settings.get('preventLossNotice'),
					type              : 'warning',
					confirmButtonText : Settings.get('ok'),
					confirmButtonColor: '#00b8db',
				});
				
			}
		}
		
	},
	
	/**
	 * Save the edited columns
	 *
	 * @param jQuery $button The "Save Data" button.
	 */
	saveData($button) {
		
		if (typeof $.atumDoingAjax === 'undefined') {
			
			let self = this,
			    data = {
				    token : Settings.get('nonce'),
				    action: 'atum_update_data',
				    data  : Globals.$editInput.val(),
			    };
			
			if (typeof Settings.get('firstEditKey') !== 'undefined') {
				data.first_edit_key = Settings.get('firstEditKey');
			}
			
			$.atumDoingAjax = $.ajax({
				url       : ajaxurl,
				method    : 'POST',
				dataType  : 'json',
				data      : data,
				beforeSend: () => {
					$button.prop('disabled', true);
					self.addOverlay();
				},
				success   : (response) => {
					
					if (typeof response === 'object' && typeof response.success !== 'undefined') {
						const noticeType = response.success ? 'updated' : 'error';
						Utils.addNotice(noticeType, response.data);
					}
					
					if (typeof response.success !== 'undefined' && response.success) {
						$button.remove();
						Globals.$editInput.val('');
						self.updateTable();
					}
					else {
						$button.prop('disabled', false);
					}
					
					$.atumDoingAjax = undefined;
					
					if (typeof Settings.get('firstEditKey') !== 'undefined') {
						delete Settings.get('firstEditKey');
					}
					
				},
				error     : () => {
					
					$.atumDoingAjax = undefined;
					$button.prop('disabled', false);
					self.removeOverlay();
					
					if (typeof Settings.get('firstEditKey') !== 'undefined') {
						delete Settings.get('firstEditKey');
					}
			
				},
			});
			
		}
		
	},
	
	/**
	 * Expand/Collapse rows with childrens
	 *
	 * @param jQuery  $row
	 * @param String  expandableRowClass
	 * @param String  stopRowSelector
	 * @param Boolean stopPropagation
	 *
	 * @return void|boolean
	 */
	expandRow($row, expandableRowClass, stopRowSelector, stopPropagation) {
		
		const rowId = $row.data('id');
		
		if (typeof expandableRowClass === 'undefined') {
			expandableRowClass = 'expandable';
		}
		
		if (typeof stopRowSelector === 'undefined') {
			stopRowSelector = '.main-row';
		}
		
		// Sync the sticky columns table.
		if (Globals.$stickyCols !== null && (typeof stopPropagation === 'undefined' || stopPropagation !== true)) {
			
			let $siblingTable = $row.closest('.atum-list-table').siblings('.atum-list-table'),
			    $syncRow      = $siblingTable.find('tr[data-id=' + rowId.toString().replace('c', '') + ']');
			
			this.expandRow($syncRow, expandableRowClass, stopRowSelector, true);
			
		}
		
		// Avoid multiple clicks before expanding.
		if (typeof this.isRowExpanding[rowId] !== 'undefined' && this.isRowExpanding[rowId] === true) {
			return false;
		}
		
		this.isRowExpanding[rowId] = true;
		
		let self      = this,
		    $rowTable = $row.closest('table'),
		    $nextRow  = $row.next(),
		    childRows = [];
		
		if ($nextRow.length) {
			$row.toggleClass('expanded');
			Tooltip.destroyTooltips();
		}
		
		// Loop until reaching the next main row.
		while (!$nextRow.filter(stopRowSelector).length) {
			
			if (!$nextRow.length) {
				break;
			}
			
			if (!$nextRow.hasClass(expandableRowClass)) {
				$nextRow = $nextRow.next();
				continue;
			}
			
			childRows.push($nextRow);
			
			if ( ($rowTable.is(':visible') && !$nextRow.is(':visible')) || (!$rowTable.is(':visible') && $nextRow.css('display') === 'none')) {
				$nextRow.addClass('expanding').show(300);
			}
			else {
				$nextRow.addClass('collapsing').hide(300);
			}
			
			$nextRow = $nextRow.next();
			
		}
		
		// Re-enable the expanding again once the animation is completed.
		setTimeout( () => {
			
			delete self.isRowExpanding[rowId];
			
			// Do this only when all the rows has been already expanded.
			if (!Object.keys(self.isRowExpanding).length && (typeof stopPropagation === 'undefined' || stopPropagation !== true)) {
				Tooltip.addTooltips();
			}
			
			$.each(childRows, (index, $childRow) => {
				$childRow.removeClass('expanding collapsing');
			})
			
		}, 320);
		
		Globals.$atumList.trigger('atum-after-expand-row', [$row, expandableRowClass, stopRowSelector]);
		
	},
	
	/**
	 * Checks/Unchecks the descendants rows when checking/unchecking their container
	 *
	 * @param jQuery $parentCheckbox
	 */
	checkDescendats($parentCheckbox) {
		
		let $containerRow = $parentCheckbox.closest('tr');
		
		// Handle clicks on the header checkbox.
		if ($parentCheckbox.closest('td').hasClass('manage-column')) {
			// Call this method recursively for all the checkboxes in the current page.
			Globals.$atumTable.find('tr.variable, tr.group').find('input:checkbox').change();
		}
		
		if (!$containerRow.hasClass('variable') && !$containerRow.hasClass('group')) {
			return;
		}
		
		let $nextRow = $containerRow.next('.expandable');
		
		// If is not expanded, expand it
		if (!$containerRow.hasClass('expanded') && $parentCheckbox.is(':checked')) {
			$containerRow.find('.calc_type .has-child').click();
		}
		
		// Check/Uncheck all the children rows.
		while ($nextRow.length) {
			$nextRow.find('.check-column input:checkbox').prop('checked', $parentCheckbox.is(':checked'));
			$nextRow = $nextRow.next('.expandable');
		}
		
	},
	
}

module.exports = ListTable;
