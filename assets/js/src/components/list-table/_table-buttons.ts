/* =======================================
   TABLE BUTTONS FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import Tooltip from '../_tooltip';
import StickyColumns from './_sticky-columns';
import StickyHeader from './_sticky-header';

export default class TableButtons {
	
	constructor(
		private globals: Globals,
		private tooltip: Tooltip,
		private stickyCols: StickyColumns,
		private stickyHeader: StickyHeader
	) {
		
		// Table style buttons.
		this.globals.$atumList.on('click', '.table-style-buttons button', (evt: JQueryEventObject) => {
			
			let $button: JQuery = $(evt.currentTarget),
			    feature: string = $button.hasClass('sticky-columns-button') ? 'sticky-columns' : 'sticky-header';
			
			$button.toggleClass('active');
			
			this.toggleTableStyle(feature, $button.hasClass('active'));
			
		});
	
	}
	
	/**
	 * Toggle table style feature from table style buttons
	 *
	 * @param String  feature
	 * @param Boolean enabled
	 */
	toggleTableStyle(feature: string, enabled: boolean) {
		
		this.tooltip.destroyTooltips();
		
		// Toggle sticky columns.
		if ('sticky-columns' === feature) {
			
			this.globals.enabledStickyColumns = enabled;
			
			if (enabled) {
				this.globals.$stickyCols = this.stickyCols.createStickyColumns(this.globals.$atumTable);
				this.globals.$scrollPane.trigger('jsp-initialised'); // Trigger the jScrollPane to add the sticky columns to the table.
			}
			else {
				this.stickyCols.destroyStickyColumns();
			}
			
		}
		// Toggle sticky header.
		else {
			
			this.globals.enabledStickyHeader = enabled;
			
			if (enabled) {
				this.stickyHeader.addFloatThead();
			}
			else {
				this.stickyHeader.destroyFloatThead();
			}
			
		}
		
		// Save the sticky columns status as user meta.
		$.ajax({
			url   : window['ajaxurl'],
			method: 'POST',
			data  : {
				action  : 'atum_change_table_style_setting',
				security: $('.table-style-buttons').data('nonce'),
				feature : feature,
				enabled : enabled,
			},
		})
		
		this.tooltip.addTooltips();
		
	}
	
}
