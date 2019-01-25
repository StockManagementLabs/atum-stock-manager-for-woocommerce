/* =======================================
   COLUMN GROUPS FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import StickyHeader from './_sticky-header';

let ColumnGroups = {
	
	init() {
		
		let self = this;
		
		// Hide/Show the toggleable group of columns with the toggler button.
		Globals.$atumList.on('click', '.group-toggler', (evt) => {
			
			self.toggleGroupColumns($(evt.target));
			$(evt.target).tooltip('hide');
			
		}).find('.column-groups th[data-collapsed="1"] .group-toggler').click();
		
		// Show the toggleable group columns when opening the screen options.
		// to avoid the hidden columns to be disabled when switching column visibilities.
		$('#show-settings-link').click( (evt) => {
			
			if (!$(evt.target).hasClass('screen-meta-active')) {
				Globals.$atumTable.find('.column-groups').find('th.collapsed').find('.group-toggler').click();
			}
			
		});
		
		// Hide/Show/Colspan column groups.
		$('#adv-settings .metabox-prefs input').change( () => {
			
			Globals.$atumList.find('thead .column-groups th').each( (index, elem) => {
				
				let $elem = $(elem),
				    // These th only have one class.
				    cols  = Globals.$atumList.find('thead .col-' + $elem.attr('class') + ':visible').length;
				
				if (cols) {
					$elem.show().attr('colspan', cols);
				}
				else {
					$elem.hide();
				}
				
			});
			
		});
		
		// Restore the collapsed groups after the List Table is updated.
		Globals.$atumList.on('atum-table-updated', () => {
			if (Globals.$collapsedGroups !== null) {
				self.restoreCollapsedGroups();
			}
		});
	
	},
	
	/**
	 * Show/Hide the group of columns with the group-toggler button.
	 *
	 * @param {jQuery} $toggler
	 */
	toggleGroupColumns($toggler) {
		
		let $curGroupCell = $toggler.closest('th'),
		    groupClass    = $curGroupCell.attr('class').replace('collapsed', ''),
		    $groupCells   = Globals.$atumTable.find('.item-heads, tbody, .totals').find('th, td').filter('.' + groupClass);
		
		// Show/hide the column group text.
		$toggler.siblings().toggle();
		
		// Expand group columns.
		if ($curGroupCell.hasClass('collapsed')) {
			
			// Remove the ghost column.
			Globals.$atumTable.find('.ghost-column.' + groupClass).remove();
			$curGroupCell.attr('colspan', $curGroupCell.data('colspan')).removeData('colspan');
			$groupCells.removeAttr('style');
			
		}
		// Collapse group columns.
		else {
			
			$groupCells.hide();
			$curGroupCell.data('colspan', $curGroupCell.attr('colspan')).removeAttr('colspan');
			
			// Add a ghost column.
			const ghostColOpts = {
				class: 'ghost-column ' + groupClass,
			}
			
			// The header could be floating (so in another table).
			$('<th />', ghostColOpts).insertBefore( Globals.$atumTable.find('thead .item-heads th.' + groupClass).first() );
			$('<th />', ghostColOpts).insertBefore( Globals.$atumTable.find('tfoot .item-heads th.' + groupClass).first() );
			$('<th />', ghostColOpts).insertBefore( Globals.$atumTable.find('tfoot .totals th.' + groupClass).first() );
			
			Globals.$atumTable.find('tbody tr').each( (index, elem) => {
				$('<td />', ghostColOpts).insertBefore( $(elem).find('td.' + groupClass).first() );
			});
			
		}
		
		$curGroupCell.toggleClass('collapsed');
		
		// Set the collapsed group columns array.
		Globals.$collapsedGroups = Globals.$atumTable.find('.column-groups').children('.collapsed');
		
		StickyHeader.reloadFloatThead();
		
		Globals.$atumList.trigger('atum-column-groups-restored');
		
	},
	
	/**
	 * Restore all the collapsed groups to its collapsed stage
	 */
	restoreCollapsedGroups() {
		
		let self = this;
		
		Globals.$collapsedGroups.each( (index, elem) => {
			
			let $groupCell = $(elem);
			$groupCell.removeClass('collapsed').attr('colspan', $groupCell.data('colspan'));
			$groupCell.children('span').not('.group-toggler').show();
			
			self.toggleGroupColumns($groupCell.find('.group-toggler'));
			
		});
		
	},
	
}

module.exports = ColumnGroups;