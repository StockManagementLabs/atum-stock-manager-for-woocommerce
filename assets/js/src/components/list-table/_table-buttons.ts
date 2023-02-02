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
		this.globals.$atumList.on( 'click', '.table-style-buttons button', ( evt: JQueryEventObject ) => {

			const $button: JQuery = $( evt.currentTarget );

			$button.toggleClass( 'active' );
			this.toggleTableStyle( $button.data( 'feature' ), $button.hasClass( 'active' ), $button.data( 'save-meta') === 1 );

		} );
	
	}
	
	/**
	 * Toggle table style feature from table style buttons
	 *
	 * @param {string}  feature
	 * @param {boolean} enabled
	 * @param {boolean} saveMeta
	 */
	toggleTableStyle( feature: string, enabled: boolean, saveMeta: boolean) {

		this.tooltip.destroyTooltips();

		switch ( feature ) {
			// Toggle sticky columns.
			case 'sticky-columns':
				this.globals.enabledStickyColumns = enabled;

				if ( enabled ) {
					this.globals.$stickyCols = this.stickyCols.createStickyColumns( this.globals.$atumTable );
					this.globals.$scrollPane.trigger( 'jsp-initialised' ); // Trigger the jScrollPane to add the sticky columns to the table.
				}
				else {
					this.stickyCols.destroyStickyColumns();
				}
				break;

			// Toggle sticky header.
			case 'sticky-header':
				this.globals.enabledStickyHeader = enabled;

				if ( enabled ) {
					this.stickyHeader.addFloatThead();
				}
				else {
					this.stickyHeader.destroyFloatThead();
				}
				break;

			case 'expand':
				if ( enabled ) {
					this.globals.$atumTable.find( 'tr' ).not( '.expanded' )
						.find( '.has-child' ).click();
				}
				else {
					this.globals.$atumTable.find( 'tr.expanded' )
						.find( '.has-child' ).click();
				}
				break;
		}

		if ( saveMeta ) {

			// Save the sticky columns status as user meta.
			$.ajax( {
				url   : window[ 'ajaxurl' ],
				method: 'POST',
				data  : {
					action  : 'atum_change_table_style_setting',
					security: $( '.table-style-buttons' ).data( 'nonce' ),
					feature : feature,
					enabled : enabled,
				},
			} );

		}

		this.tooltip.addTooltips();

	}
	
}
