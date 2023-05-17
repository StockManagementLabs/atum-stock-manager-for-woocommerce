/*
 ┌──────────────────────────────────┐
 │                                  │
 │     SEARCH ORDERS BY COLUMN      │
 │                                  │
 └──────────────────────────────────┘
 */

import Tooltip from '../_tooltip';

export default class SearchOrdersByColumn {

	$searchColumnWrapper: JQuery = $( '#atum-search-by-column' );
	$searchColumnBtn: JQuery = $( '#search_column_btn' );
	$searchColumnDropdown: JQuery = $( '#search_column_dropdown' );

	constructor(
		private tooltip: Tooltip
	) {

		this.bindEvents();
		this.tooltip.addTooltips( this.$searchColumnWrapper );

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
				      value: string = $item.data( 'value' ),
				      label: string = $item.text().trim();

				this.$searchColumnDropdown.children( 'input' ).val( value );

				this.$searchColumnDropdown.hide()
					.children( 'a.active' ).removeClass( 'active' );
				$item.addClass( 'active' );

				this.$searchColumnBtn.attr( 'data-bs-original-title', label );
				this.$searchColumnBtn.text( label );
				this.$searchColumnBtn.data( 'value', value );

				this.tooltip.destroyTooltips( this.$searchColumnWrapper );
				this.tooltip.addTooltips( this.$searchColumnWrapper );

			} );

		$( 'body' ).click( () => this.$searchColumnDropdown.hide() );

	}

}