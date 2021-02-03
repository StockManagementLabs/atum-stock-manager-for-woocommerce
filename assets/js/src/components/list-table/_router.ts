/* =======================================
   ROUTER FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';
import ListTable from './_list-table';
import Utils from '../../utils/_utils';
import WPHooks from '../../interfaces/wp.hooks';

export default class Router {
	
	navigationReady: boolean = false;
	numHashParameters: number = 0;
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private listTable: ListTable
	) {

		if ( typeof $.address === 'undefined' ) {
			return;
		}
		
		// Hash history navigation.
		$.address.externalChange( () => {

			if ( this.settings.get( 'ajaxFilter' ) !== 'yes' ) {
				// Force enabled or disabled search button.
				const searchInputVal: any = this.globals.$searchInput.val();
				$( '.search-submit' ).prop( 'disabled', searchInputVal.length > 0 ? false : true );
			}

			const numCurrentParams: number = $.address.parameterNames().length;
			if ( this.navigationReady === true && ( numCurrentParams || this.numHashParameters !== numCurrentParams ) ) {
				this.listTable.updateTable();
			}

			this.navigationReady = true;
			
		} )
		.init( () => {
			
			// When accessing externally or reloading the page, update the fields and the list.
			if ( $.address.parameterNames().length ) {
				
				// Init fields from hash parameters.
				let s: string            = decodeURIComponent( $.address.parameter( 's' ) || '' ),
				    searchColumn: string = $.address.parameter( 'search_column' ) || '',
				    optionVal: string    = '';

				if ( s ) {
					this.globals.$searchInput.val( s );
				}

				if ( searchColumn ) {

					// Activate the dropdown item coming from the hash.
					const $selectedSearchColumn: JQuery = this.globals.$searchColumnDropdown.find( '.dropdown-item' ).filter( ( index: number, elem: Element ) => {
						return $( elem ).data( 'value' ) === searchColumn;
					} ).addClass( 'active' );
					this.globals.$searchColumnBtn.attr( 'data-original-title', $selectedSearchColumn.text().trim() ).text( $selectedSearchColumn.text().trim() );

					// Update the Screen Options' checkboxes.
					$( '#adv-settings :checkbox' ).each( ( index: number, elem: Element ) => {

						optionVal = $( elem ).val();

						// Calc values are not searchable, also we can't search on thumb.
						if ( optionVal.search( 'calc_' ) < 0 && optionVal !== 'thumb' && optionVal == searchColumn ) {
							this.globals.$searchColumnBtn.trigger( 'atum-search-column-set-data', [ optionVal, $( elem ).parent().text().trim() ] );
							return false;
						}

					} );

				}
				
				this.listTable.updateTable();
				
			}
			
		});
		
		// Bind List Table links.
		this.bindListLinks();
		
		// Bind pagination input textbox.
		this.bindPageInput();
		
		this.bindEvents();
		this.addHooks();
	
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// Bind Views, Pagination and Sortable links.
		this.globals.$atumList.on( 'click', '.tablenav-pages a, .item-heads a, .subsubsub a', ( evt: JQueryEventObject ) => {
			evt.preventDefault();

			if ( ! $( evt.currentTarget ).hasClass( 'current' ) ) {
				this.updateHash();
			}
		} );

	}

	/**
	 * Add hooks
	 */
	addHooks() {

		// Re-bind the links after the List Table is updated.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => this.bindListLinks() );

	}
	
	/**
	 * Bind the List Table links that will trigger URL hash changes
	 */
	bindListLinks() {
		this.globals.$atumList.find( '.subsubsub a, .tablenav-pages a, .item-heads a' ).address();
	}
	
	/**
	 * Bind pagination input textbox
	 */
	bindPageInput() {

		this.globals.$atumList.on( 'keypress', '#current-page-selector', ( evt: JQueryEventObject ) => {
			if ( evt.which === 13 ) {
				$.address.parameter( 'paged', $( evt.currentTarget ).data( 'current' ) );
				this.updateHash();
			}
		} );
		
	}
	
	/**
	 * Update the URL hash with the current filters
	 */
	updateHash() {

		const beforeFilters: any = { ...this.globals.filterData }; // Deconstruct the object.

		Object.assign( this.globals.filterData, {
			view          : $.address.parameter( 'view' ) || this.globals.$atumList.find( '.subsubsub a.current' ).attr( 'id' ) || '',
			paged         : parseInt( $.address.parameter( 'paged' ) || this.globals.$atumList.find( '.current-page' ).val() || this.settings.get( 'paged' ) ),
			s             : decodeURIComponent( $.address.parameter( 's' ) || '' ),
			search_column : $.address.parameter( 'search_column' ) || '',
			sold_last_days: $.address.parameter( 'sold_last_days' ) || '',
			orderby       : $.address.parameter( 'orderby' ) || this.settings.get( 'orderby' ),
			order         : $.address.parameter( 'order' ) || this.settings.get( 'order' ),
			...this.globals.getAutoFiltersValues(),
		} );
		
		// If the filter data has not changed, we don't need to update the hash.
		if ( Utils.areEquivalent( beforeFilters, this.globals.filterData ) ) {
			return;
		}

		// Update the URL hash parameters.
		$.each( [ 'view', 'paged', 'order', 'orderby', 's', 'search_column', 'sold_last_days', 'date_from', 'date_to', ...this.globals.autoFiltersNames ], ( index: number, elem: string ) => {

			// Disable auto-update on each iteration until all the parameters have been set.
			this.navigationReady = false;

			// If it's not saved on the filter data, continue.
			if ( ! this.globals.filterData.hasOwnProperty( elem ) ) {
				return true;
			}

			$.address.parameter( elem, this.globals.filterData[ elem ] );

		} );

		// Restore navigation and update if needed.
		const numCurrentParams: number = $.address.parameterNames().length;
		if ( numCurrentParams || this.numHashParameters !== numCurrentParams ) {
			this.listTable.updateTable();
		}

		this.navigationReady = true;
		this.numHashParameters = numCurrentParams;
		
	}
	
}
