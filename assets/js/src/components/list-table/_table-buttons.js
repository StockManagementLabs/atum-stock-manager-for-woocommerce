/* =======================================
   TABLE BUTTONS FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import Tooltip from '../_tooltip';
import StickyColumns from './_sticky-columns';
import StickyHeader from './_sticky-header';

let TableButtons = {
	
	init() {
		
		let self = this;
		
		// Table style buttons.
		Globals.$atumList.on('click', '.table-style-buttons button', (evt) => {
			
			let $button = $(evt.target),
			    feature = $button.hasClass('sticky-columns-button') ? 'sticky-columns' : 'sticky-header';
			
			$button.toggleClass('active');
			
			self.toggleTableStyle(feature, $button.hasClass('active'));
			
		})
	
	},
	
	/**
	 * Toggle table style feature from table style buttons
	 *
	 * @param {String}  feature
	 * @param {Boolean} enabled
	 */
	toggleTableStyle(feature, enabled) {
		
		Tooltip.destroyTooltips();
		
		// Toggle sticky columns.
		if ('sticky-columns' === feature) {
			
			Globals.enabledStickyColumns = enabled;
			
			if (enabled) {
				Globals.$stickyCols = StickyColumns.createStickyColumns(Globals.$atumTable);
				Globals.$scrollPane.trigger('jsp-initialised'); // Trigger the jScrollPane to add the sticky columns to the table.
			}
			else {
				StickyColumns.destroyStickyColumns();
			}
			
		}
		// Toggle sticky header.
		else {
			
			Globals.enabledStickyHeader = enabled;
			
			if (enabled) {
				StickyHeader.addFloatThead();
			}
			else {
				StickyHeader.destroyFloatThead();
			}
			
		}
		
		// Save the sticky columns status as user meta.
		$.ajax({
			url   : ajaxurl,
			method: 'POST',
			data  : {
				token  : $('.table-style-buttons').data('nonce'),
				action : 'atum_change_table_style_setting',
				feature: feature,
				enabled: enabled,
			},
		})
		
		Tooltip.addTooltips();
		
	},
	
}

module.exports = TableButtons;