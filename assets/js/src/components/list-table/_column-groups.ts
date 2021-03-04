/* =======================================
   COLUMN GROUPS FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import StickyHeader from './_sticky-header';
import WPHooks from '../../interfaces/wp.hooks';

export default class ColumnGroups {

	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private globals: Globals,
		private stickyHeader: StickyHeader
	) {
		
		this.bindEvents();
		this.addHooks();
	
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// Hide/Show the toggleable group of columns with the toggler button.
		this.globals.$atumList.on( 'click', '.group-toggler', ( evt: JQueryEventObject ) => {

			const $groupToggler: any = $( evt.currentTarget );

			this.toggleGroupColumns( $groupToggler );
			//$groupToggler.tooltip( 'hide' ); // TODO: WHY NOT TO USE THE TOOLTIP COMPONENT.

		} ).find( '.column-groups th[data-collapsed="1"] .group-toggler' ).click();

		// Show the toggleable group columns when opening the screen options.
		// to avoid the hidden columns to be disabled when switching column visibilities.
		$( '#show-settings-link' ).click( ( evt: JQueryEventObject ) => {

			if ( ! $( evt.currentTarget ).hasClass( 'screen-meta-active' ) ) {
				this.globals.$atumTable.find( '.column-groups' ).find( 'th.collapsed' ).find( '.group-toggler' ).click();
			}

		} );

		// Hide/Show/Colspan column groups.
		$( '#adv-settings .metabox-prefs input' ).change( ( evt: JQueryEventObject ) => {

			this.globals.$atumList.find( 'thead .column-groups th' ).each( ( index: number, elem: Element ) => {

				const $elem: JQuery = $( elem ),
				    // These th only have one class.
				    cols: number  = this.globals.$atumList.find( `thead .col-${ $elem.attr( 'class' ) }:visible` ).length;

				if ( cols ) {
					// NOTE: for the first group we must count the checkbox column.
					$elem.show().attr( 'colspan', 0 === index ? cols + 1 : cols );
				}
				else {
					$elem.hide();
				}

			} );

			// If the column being hidden/shown is the thumbnail, adjust the totals row heading's colspan.
			const $checkbox: JQuery  = $( evt.currentTarget ),
			      $totalsRow: JQuery = this.globals.$atumList.find( 'tr.totals' );

			if ( $checkbox.attr( 'id' ) === 'thumb-hide' && $totalsRow.length ) {
				$totalsRow.find( 'td.totals-heading' ).attr( 'colspan', $checkbox.is( ':checked' ) ? 2 : 1 );
			}

		} );

	}

	/**
	 * Add hooks
	 */
	addHooks() {

		// Restore the collapsed groups after the List Table is updated.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => {

			if ( this.globals.$collapsedGroups !== null ) {
				this.restoreCollapsedGroups();
			}

		} );

	}
	
	/**
	 * Show/Hide the group of columns with the group-toggler button.
	 *
	 * @param jQuery $toggler
	 */
	toggleGroupColumns($toggler: JQuery) {

		const $curGroupCell: JQuery = $toggler.closest( 'th' ),
		      groupClass: string    = $curGroupCell.attr( 'class' ).replace( 'collapsed', '' ),
		      $groupCells: JQuery   = this.globals.$atumTable.find( '.item-heads, tbody, .totals' ).find( 'th, td' ).filter( '.' + groupClass );
		
		// Show/hide the column group text.
		$toggler.siblings().toggle();
		
		// Expand group columns.
		if ( $curGroupCell.hasClass( 'collapsed' ) ) {

			// Remove the ghost column.
			this.globals.$atumTable.find( `.ghost-column.${ groupClass }` ).remove();
			$curGroupCell.attr( 'colspan', $curGroupCell.data( 'colspan' ) ).removeData( 'colspan' );
			$groupCells.removeAttr( 'style' );

		}
		// Collapse group columns.
		else {

			$groupCells.hide();
			$curGroupCell.data( 'colspan', $curGroupCell.attr( 'colspan' ) ).removeAttr( 'colspan' );

			// Add a ghost column.
			const ghostColOpts: any = {
				class: `ghost-column ${ groupClass }`,
			};

			// The header could be floating (so in another table).
			$( '<th />', ghostColOpts ).insertBefore( this.globals.$atumTable.find( `thead .item-heads th.${ groupClass }` ).first() );
			$( '<th />', ghostColOpts ).insertBefore( this.globals.$atumTable.find( `tfoot .item-heads th.${ groupClass }` ).first() );
			$( '<th />', ghostColOpts ).insertBefore( this.globals.$atumTable.find( `tfoot .totals th.${ groupClass }` ).first() );

			this.globals.$atumTable.find( 'tbody tr' ).each( ( index: number, elem: Element ) => {
				$( '<td />', ghostColOpts ).insertBefore( $( elem ).find( `td.${ groupClass }` ).first() );
			} );

		}

		$curGroupCell.toggleClass( 'collapsed' );

		// Set the collapsed group columns array.
		this.globals.$collapsedGroups = this.globals.$atumTable.find( '.column-groups' ).children( '.collapsed' );

		this.stickyHeader.reloadFloatThead();
		this.wpHooks.doAction( 'atum_columnGroups_groupsRestored' );
		
	}
	
	/**
	 * Restore all the collapsed groups to its collapsed stage
	 */
	restoreCollapsedGroups() {

		this.globals.$collapsedGroups.each( ( index: number, elem: Element ) => {

			const $groupCell: JQuery = $( elem );
			$groupCell.removeClass( 'collapsed' ).attr( 'colspan', $groupCell.data( 'colspan' ) );
			$groupCell.children( 'span' ).not( '.group-toggler' ).show();

			this.toggleGroupColumns( $groupCell.find( '.group-toggler' ) );

		} );
		
	}
	
}
