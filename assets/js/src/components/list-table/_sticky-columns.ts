/* =======================================
   STICKY COLUMNS FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';

export default class StickyColumns {
	
	constructor(
		private settings: Settings,
		private globals: Globals
	) {
		
		// Make the first columns sticky.
		this.globals.enabledStickyColumns = $('.sticky-columns-button').hasClass('active');
		if (this.globals.enabledStickyColumns) {
			this.globals.$stickyCols = this.createStickyColumns(this.globals.$atumTable);
		}
	
	}
	
	/**
	 * Make the first table columns sticky
	 *
	 * @param jQuery The table that will be used as a base to generate the sticky columns.
	 *
	 * @return jQuery|null The sticky cols (if enabled) or null.
	 */
	createStickyColumns($table?: JQuery): JQuery|null {
		
		// If there are no sticky columns in this table, do not continue.
		if (!this.settings.get('stickyColumns').length) {
			return null;
		}
		
		let $stickyCols: JQuery = $table.clone();
		
		// Remove table header and footer.
		$stickyCols.addClass('cloned').removeAttr('style').hide().find('colgroup, fthfoot').remove();

		// Remove all the columns that won't be sticky.
		$stickyCols.find('tr').each( (index: number, elem: Element) => {
			
			let $row: JQuery = $(elem);
			
			// Add a prefix to the row ID to avoid problems when expanding/collapsing rows.
			$row.data('id', `c${ $row.data('id') }`);
			
			// Remove all the column groups except first one.
			if ($row.hasClass('column-groups')) {
				
				let $colGroups = $row.children();
				$colGroups.not(':first-child').remove();
				$colGroups.first().attr('colspan', this.settings.get('stickyColumns').length);
				
			}
			// Remove all the non-sticky columns.
			else {
				
				let columnNames   = this.settings.get('stickyColumns'),
				    columnClasses = [];
				
				$.each(columnNames, (index: number, columnName: string) => {
					columnClasses.push(`.column-${ columnName }`);
				})
				
				$row.children().not(columnClasses.join(',')).remove();
				
			}

			// Apply the same height than the original table row.
			let copyStyle: any = getComputedStyle( $table.find('tr')[ index ] );
			$row.css( 'height', copyStyle['height'] );

		});
		
		// Do not add sticky columns with a low columns number.
		if ($stickyCols.find('thead .item-heads').children().not('.hidden').length <= 2) {
			return null;
		}
		
		// Remove the manage-column class to not conflict with the WP's Screen Options functionality.
		$stickyCols.find('.manage-column').removeClass('manage-column');

		if ($('.no-items').length) {
			return null;
		}
		
		return $stickyCols;
		
	}
	
	/**
	 * Destroy the sticky columns previously set for the table
	 *
	 * @param jQuery The table that is holding the sticky columns.
	 */
	destroyStickyColumns() {
		
		if (this.globals.$stickyCols !== null) {
			this.globals.$stickyCols.remove();
		}
		
		if (this.globals.$floatTheadStickyCols !== null) {
			this.globals.$floatTheadStickyCols.remove();
		}
		
	}

	/**
	 * Destroy and create sticky columns
	 */
	refreshStickyColumns() {

		this.destroyStickyColumns();
		this.globals.$stickyCols = this.createStickyColumns( this.globals.$atumTable );

	}
	
}
