/*
 ┌──────────────────────────────────┐
 │                                  │
 │     SEARCH ORDERS BY COLUMN      │
 │                                  │
 └──────────────────────────────────┘
 */

import Tooltip from '../_tooltip';
import Utils from '../../utils/_utils';

export default class SearchOrdersByColumn {

	$searchColumnWrapper: JQuery = $( '#atum-search-by-column' );
	$searchColumnBtn: JQuery = this.$searchColumnWrapper.find( '.search-column-btn' );
	$searchColumnDropdown: JQuery = this.$searchColumnWrapper.find( '#search_column_dropdown' );
	$searchInput: JQuery = this.$searchColumnWrapper.find( 'input[type=search]' );

	constructor(
		private tooltip: Tooltip
	) {

		this.bindEvents();
		this.tooltip.addTooltips( this.$searchColumnWrapper );

		// Check whether we have to initialize the column.
		const activeSearchCol: string = <string>Utils.getUrlParameter( 'atum_search_column' ),
		      $dropdownLinks: JQuery  = this.$searchColumnDropdown.children( 'a' );

		if ( activeSearchCol ) {
			Utils.filterByData( $dropdownLinks, 'value', activeSearchCol ).click();
		}
		// If there is only one column available, pre-select it.
		else if ( $dropdownLinks.length < 3 ) {
			$dropdownLinks.eq( 1 ).click();
		}
		else {
			$dropdownLinks.eq( 0 ).click(); // Force the search input to disable if no option is selected.
		}

	}

	/**
	 * Bind Events
	 */
	bindEvents() {

		this.$searchColumnBtn

			// Bind clicks on search in column button.
			.click( ( evt: JQueryEventObject ) => {
				evt.preventDefault();
				evt.stopPropagation();
				$( evt.currentTarget ).parent().find( '.dropdown-menu' ).toggle();
			} );

		this.$searchColumnDropdown

			// Bind clicks on dropdown menu items.
			.on( 'click', 'a', ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				const $item: JQuery = $( evt.currentTarget ),
				      column: string = $item.data( 'value' ),
				      label: string = $item.text().trim(),
				      noOptionLabel: string = this.$searchColumnDropdown.data( 'no-option' );

				// Enable/Disable the input field.
				this.$searchInput.prop( 'disabled', ! column );

				this.$searchColumnDropdown.children( 'input[type=hidden]' ).val( column );

				this.$searchColumnDropdown.hide()
					.children( 'a.active' ).removeClass( 'active' );
				$item.addClass( 'active' );

				this.$searchColumnBtn.attr( 'data-bs-original-title', noOptionLabel !== label ? `${ noOptionLabel } ${ label }` : label );
				this.$searchColumnBtn.text( label );
				this.$searchColumnBtn.data( 'value', column );

				this.tooltip.destroyTooltips( this.$searchColumnWrapper );
				this.tooltip.addTooltips( this.$searchColumnWrapper );

				if ( column ) {
					this.$searchInput.focus();
				}

			} );

		$( 'body' ).click( () => this.$searchColumnDropdown.hide() );

	}

}