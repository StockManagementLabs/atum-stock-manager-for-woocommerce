/* =======================================
   ROW ACTIONS FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import Settings from '../../config/_settings';
import { IMenu, IMenuItem } from '../../interfaces/menu.interface';
import MenuPopover from '../_menu-popover';
import WPHooks from '../../interfaces/wp.hooks';

export default class RowActions {

	rowActions: IMenuItem[] = [];
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private globals: Globals,
	) {

		this.rowActions = this.settings.get( 'rowActions' );

		this.prepareActionMenus();
		this.addHooks();
		
	}

	/**
	 * Prepare the action menus for rows' actions
	 */
	prepareActionMenus() {

		this.globals.$atumList.find( '.show-actions' ).each( ( index: number, elem: Element ) => {

			const $button: JQuery    = $( elem );

			// If there are no row actions, hide the button.
			if ( ! this.rowActions || ! this.rowActions.length ) {
				$button.hide();
				return;
			}

			const $row: JQuery       = $button.closest( 'tr' ),
			      $titleCell: JQuery = $row.find( 'td.column-title' ).length ? $row.find( 'td.column-title' ) : $row.find( '.row-title' );

			// NOTE: we assume that the rowActions comes with the right format (following the IMenuItem interface format).
			const actionsMenu: IMenu = {
				title: this.sanitizeRowTitle( $titleCell ),
				items: this.rowActions,
			};

			new MenuPopover( $button, actionsMenu, 'body' ); // Added the body as container to avoid having problems with the overflow:hidden on tables with few rows.

		} );

	}

	/**
	 * Add hooks
	 */
	addHooks() {

		// Re-add the action menus after the list table is updaded.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => this.prepareActionMenus() );

		// Add the "no actions" message if there are no menu items. NOTE: A high priority is important here.
		this.wpHooks.addAction( 'atum_menuPopover_inserted', 'atum', ( $popover: JQuery ) => {

			if ( ! $popover.find( 'li' ).length ) {
				$popover.find( 'ul' ).append( `<li class="no-actions">${ this.settings.get( 'noActions' ) }</li>` );
			}

		}, 999 );

		// Allow updating the row actions externally for specific views.
		this.wpHooks.addAction( 'atum_listTable_updateRowActions', 'atum', ( rowActions: IMenuItem[] ) => this.rowActions = rowActions );

	}

	/**
	 * Extract the text for the menu popover from the row title
	 *
	 * @param {JQuery} $titleCell
	 *
	 * @return {string}
	 */
	sanitizeRowTitle( $titleCell: JQuery ): string {

		return `<span>${ ( $titleCell.find( '.atum-title-small' ).length ? $titleCell.find( '.atum-title-small' ) : $titleCell ).text().replace( '↵', '' ).trim() }</span>`;

	}

}
