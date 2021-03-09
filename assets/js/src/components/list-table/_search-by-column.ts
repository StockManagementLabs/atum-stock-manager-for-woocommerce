/* =======================================
   SEARCH BY COLUMN FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';
import Utils from '../../utils/_utils';

export default class SearchByColumn {

	constructor(
		private settings: Settings,
		private globals: Globals
	) {

		if ( $( '.atum-post-search-with-dropdown' ).length ) {

			this.setup();

			// Rearrange the dropdown items when changing the visible columns from Screen Options.
			$( '#adv-settings input:checkbox' ).change( () => this.setup() );

			this.events();

		}
		
	}
	
	/**
	 * Fill the search by column dropdown with the active screen options checkboxes
	 */
	setup() {

		const $dropdownItem: JQuery = $( '<a class="dropdown-item" href="#" />' );

		this.globals.$searchColumnDropdown.empty();

		// Append the no column and the title items.
		this.globals.$searchColumnDropdown.append( $dropdownItem.clone().data( 'value', '' ).addClass( 'active' ).text( this.globals.$searchColumnDropdown.data( 'no-option' ) ) );
		this.globals.$searchColumnDropdown.append( $dropdownItem.clone().data( 'value', 'title' ).text( this.globals.$searchColumnDropdown.data( 'product-title' ) ) );

		// Reset the button value.
		this.globals.$searchColumnBtn.trigger( 'atum-search-column-set-data', [ '', this.globals.$searchColumnDropdown.data( 'no-option' ) ] );

		$( '#adv-settings input:checked' ).each( ( index: number, elem: Element ) => {

			const $elem: JQuery       = $( elem ),
			      optionVal: string   = $elem.val(),
			      columnLabel: string = $elem.parent().text().trim();

			// Calc values are not searchable, also we can't search on thumb and supplier has its own filter.
			if ( optionVal.search( 'calc_' ) < 0 && optionVal !== 'thumb' && optionVal !== '_supplier' ) {

				this.globals.$searchColumnDropdown.append( $dropdownItem.clone().data( 'value', optionVal ).text( columnLabel ) );

				// Most probably, we are on init and ?search_column has a value. Or maybe not, but, if this happens, force change.
				if ( $.address.parameter( 'search_column' ) !== this.globals.$searchColumnBtn.data( 'value' ) && this.globals.$searchColumnBtn.data( 'value' ) === optionVal ) {
					this.globals.$searchColumnBtn.trigger( 'atum-search-column-set-data', [ optionVal, columnLabel ] );
				}

			}

		} );
		
	}
	
	/**
	 * Bind events
	 */
	events() {
		
		this.globals.$searchColumnBtn

			// Bind clicks on search by column button.
			.click( ( evt: JQueryEventObject ) => {
				evt.stopPropagation();
				$( evt.currentTarget ).parent().find( '.dropdown-menu' ).toggle();
			} )

			// Set $searchColumnBtn data-value and label.
			.on( 'atum-search-column-set-data', ( evt: JQueryEventObject, value: string, label: string ) => {

				const $searchColBtn: JQuery  = $( evt.currentTarget ),
				      $dropDownLinks: JQuery = this.globals.$searchColumnDropdown.children( 'a' );

				$searchColBtn.text( label );
				$searchColBtn.data( 'value', value );
				$searchColBtn.attr( 'data-original-title', label === this.globals.$searchColumnDropdown.data( 'no-option' ) ? this.globals.$searchColumnDropdown.data( 'no-option-title' ) : label );

				$dropDownLinks.filter( '.active' ).removeClass( 'active' );
				Utils.filterByData( $dropDownLinks, 'value', value ).addClass( 'active' );

			} );
		
		// Bind clicks on dropdown menu items.
		this.globals.$searchColumnDropdown.on( 'click', 'a', ( evt: JQueryEventObject ) => {

			evt.preventDefault();

			const $item: JQuery = $( evt.currentTarget );

			this.globals.$searchColumnBtn.trigger( 'atum-search-column-set-data', [ $item.data( 'value' ), $item.text().trim() ] );

			$item.parents().find( '.dropdown-menu' ).hide();
			this.globals.$searchColumnDropdown.children( 'a.active' ).removeClass( 'active' );
			$item.addClass( 'active' );

			const fieldType: string = $.inArray( $item.data( 'value' ), this.settings.get( 'searchableColumns' ).numeric ) > -1 ? 'number' : 'search';
			this.globals.$searchInput.attr( 'type', fieldType );

			if ( this.settings.get( 'ajaxFilter' ) === 'yes' ) {
				this.globals.$searchColumnBtn.trigger( 'atum-search-column-data-changed' );
			}

			this.globals.$searchColumnBtn.attr( 'data-original-title', $item.html() );

		} );

		$( document ).click( () => this.globals.$searchColumnDropdown.hide() );
		
	}
	
}
