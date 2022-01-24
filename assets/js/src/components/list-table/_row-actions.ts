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

		if ( ! this.rowActions.length ) {
			return;
		}

		this.prepareActionMenus();
		this.addHooks();
		
	}

	/**
	 * Prepare the action menus for rows' actions
	 */
	prepareActionMenus() {

		this.globals.$atumList.find( '.show-actions' ).each( ( index: number, elem: Element ) => {

			const $button: JQuery    = $( elem ),
			      $titleCell: JQuery = $button.closest( 'tr' ).find( 'td.column-title' );

			// NOTE: we assume that the rowActions comes with the right format (following the IMenuItem interface format).
			const actionsMenu: IMenu = {
				title: ( $titleCell.find( '.atum-title-small' ).length ? $titleCell.find( '.atum-title-small' ) : $titleCell ).text().replace( '↵', '' ).trim(),
				items: this.rowActions,
			};

			new MenuPopover( $button, actionsMenu );

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

	}

}
