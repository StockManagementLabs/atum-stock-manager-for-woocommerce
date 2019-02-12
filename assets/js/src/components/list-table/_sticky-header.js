/* =======================================
   STICKY HEADER FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';
import StickyColumns from './_sticky-columns';

let StickyHeader = {
	
	init() {
		
		let self = this;
		
		// Add the floating table header.
		Globals.enabledStickyHeader = $('.sticky-header-button').hasClass('active');
		if (Globals.enabledStickyHeader) {
			this.addFloatThead();
		}
		
		// This event will trigger on the table when the header is floated and unfloated.
		Globals.$atumTable.on('floatThead', (evt, isFloated, $floatContainer) => {
			
			if (isFloated) {
				
				$floatContainer.css('height', 'auto');
				$('.jspContainer').height($('.jspPane').height());
				
				// Hide search dropdown on sticky.
				if (Settings.get('searchDropdown') === 'yes') {
					$('#search_column_dropdown').hide();
				}
				
				// Hide on mobile view.
				if ($('#wpadminbar').css('position') === 'absolute') {
					$floatContainer.hide();
				}
				else {
					$floatContainer.show();
				}
				
				// Add the sticky columns to the floating header if needed.
				if (Globals.enabledStickyColumns) {
					
					// Reposition the sticky cols to fit the floating header.
					if (Globals.$stickyCols !== null) {
						Globals.$stickyCols.css('top', -1 * ($floatContainer.height() - 1));
					}
					
					let $floatTheadTable = Globals.$atumList.find('.floatThead-table');
					Globals.$floatTheadStickyCols = StickyColumns.createStickyColumns($floatTheadTable);
					
					if (Globals.$floatTheadStickyCols !== null) {
						
						$floatTheadTable.after(Globals.$floatTheadStickyCols);
						Globals.$floatTheadStickyCols.css('width', Globals.$stickyCols.width() + 1);
						
						// Add the colgroup tag with column widths.
						Globals.$floatTheadStickyCols.prepend('<colgroup />');
						
						let $colGroup = Globals.$floatTheadStickyCols.find('colgroup');
						
						$floatTheadTable.find('thead .item-heads').children().each( (index, elem) => {
							
							let $cell = $(elem),
							    id    = $cell.attr('id');
							
							if ($cell.hasClass('hidden')) {
								return;
							}
							
							if (Globals.$floatTheadStickyCols.find('thead .item-heads').children('#' + id).length) {
								$colGroup.append('<col style="width:' + $cell.width() + 'px;">');
							}
							
						});
						
						// Remove the manage-column class to not conflict with the WP's Screen Options functionality.
						Globals.$floatTheadStickyCols.find('.manage-column').removeClass('manage-column');
						
						$colGroup.prependTo(Globals.$floatTheadStickyCols);
						self.adjustStickyHeaders(Globals.$floatTheadStickyCols, $floatTheadTable);
						
					}
					
				}
				
			}
			else {
				
				$floatContainer.css('height', 0);
				
				if (Globals.enabledStickyColumns) {
					
					// Reset the sticky columns position.
					if (Globals.$stickyCols !== null) {
						Globals.$stickyCols.css('top', 0);
					}
					
					// Remove the floating header's sticky columns.
					if (Globals.$floatTheadStickyCols !== null) {
						Globals.$floatTheadStickyCols.remove();
					}
					
				}
				
			}
			
		});
		
	},
	
	/**
	 * Add the floating header to the table
	 */
	addFloatThead() {
		
		if (!Globals.enabledStickyHeader) {
			return false;
		}
		
		if (typeof Globals.$atumTable.data('floatTheadAttached') !== 'undefined' && Globals.$atumTable.data('floatTheadAttached') !== false) {
			this.reloadFloatThead();
			
			return;
		}
		
		Globals.$atumTable.floatThead({
			responsiveContainer: ($table) => {
				return $table.closest('.jspContainer');
			},
			position           : 'absolute',
			top                : $('#wpadminbar').height(),
			autoReflow         : true,
		});
		
	},
	
	/**
	 * Reload the floating table header
	 */
	reloadFloatThead() {
		
		if (Globals.enabledStickyHeader) {
			this.destroyFloatThead();
			this.addFloatThead();
		}
		
	},
	
	/**
	 * Destroy the floating table header
	 */
	destroyFloatThead() {
		if (typeof Globals.$atumTable.data('floatTheadAttached') !== 'undefined' && Globals.$atumTable.data('floatTheadAttached') !== false) {
			Globals.$atumTable.floatThead('destroy');
		}
	},
	
	/**
	 * Adjust the header heights to match the List Table heights
	 *
	 * @param jQuery $stickyTable
	 * @param jQuery $origTable
	 */
	adjustStickyHeaders($stickyTable, $origTable) {
		
		$.each( ['column-groups', 'item-heads'], (index, className) => {
			$stickyTable.find('.' + className + ' > th').first().css('height', $origTable.find('.' + className + ' > th').first().height());
		});
		
	},
	
}

module.exports = StickyHeader;