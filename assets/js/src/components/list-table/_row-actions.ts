/* =======================================
   ROW ACTIONS FOR LIST TABLES
   ======================================= */

import { IMenu, IMenuItem } from '../../interfaces/menu.interface';
import MenuPopover from '../_menu-popover';
import Settings from '../../config/_settings';
import WPHooks from '../../interfaces/wp.hooks';

export default class RowActions {

	rowActions: IMenuItem[] = [];
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private $listTable: JQuery,
	) {

		this.rowActions = this.settings.get( 'rowActions' );

		this.prepareActionMenus();
		this.addHooks();
		
	}

	/**
	 * Prepare the action menus for rows' actions
	 */
	prepareActionMenus() {

		this.$listTable.find( '.show-actions' ).each( ( index: number, elem: Element ) => {

			const $button: JQuery = $( elem );

			// If there are no row actions, hide the button.
			if ( ! this.rowActions || ! this.rowActions.length ) {
				$button.hide();
				return;
			}

			const $row: JQuery                 = $button.closest( 'tr' ),
			      $titleCell: JQuery           = $row.find( 'td.column-title, td.column-name' ).length ? $row.find( 'td.column-title, td.column-name' ).first() : $row.find( '.row-title' ),
			      filteredActions: IMenuItem[] = this.filterRowActions( $row );

			if ( ! filteredActions.length ) {
				$button.hide();
				return;
			}

			// NOTE: we assume that the rowActions comes with the right format (following the IMenuItem interface format).
			const actionsMenu: IMenu = {
				title: this.sanitizeRowTitle( $titleCell ),
				items: filteredActions,
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

	/**
	 * Filter some actions where there is a conditional clause
	 *
	 * @param {JQuery} $row
	 *
	 * @return {IMenuItem[]}
	 */
	filterRowActions( $row: JQuery ): IMenuItem[] {

		return this.rowActions.filter( ( rowAction: IMenuItem ): IMenuItem|boolean => {

			if ( !rowAction.conditional ) {
				return rowAction;
			}
			else {

				let value: any;

				// Filter the rows by CSS class name(s).
				if ( rowAction.conditional.hasOwnProperty( 'class' ) ) {

					switch ( typeof rowAction.conditional.class ) {
						case 'undefined':
							return rowAction;

						case 'object':
							// Make sure it isn't an associative array that JS interpreted as an object.
							value = Array.isArray( rowAction.conditional.class ) ? rowAction.conditional.class : Object.values( rowAction.conditional.class );
							break;

						default:
							value = rowAction.conditional.class;
							break;
					}

					const rowClasses: string = $row.attr( 'class' );

					return Array.isArray( value ) ? value.some( ( subst: string ) => rowClasses.includes( subst ) ) : $row.hasClass( value );

				}
				// Filter the rows by data key/value(s).
				else if ( rowAction.conditional.hasOwnProperty( 'data' ) ) {

					switch ( typeof rowAction.conditional.data.value ) {
						case 'undefined':
							return rowAction;

						case 'object':
							// Make sure it isn't an associative array that JS interpreted as an object.
							value = Array.isArray( rowAction.conditional.data.value ) ? rowAction.conditional.data.value : Object.values( rowAction.conditional.data.value );
							break;

						default:
							value = rowAction.conditional.data.value;
							break;
					}

					const rowData: any = $row.data( rowAction.conditional.data.key );

					return Array.isArray( value ) ? value.some( ( value: any ) => rowData == value ) : rowData == value;

				}

			}

		} );

	}

}
