/* =======================================
   GLOBALS FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Utils from '../../utils/_utils';

export default class Globals {
	
	$atumList: JQuery = null;
	$atumTable: JQuery = null;
	$editInput: JQuery = null;
	$searchInput: JQuery = null;
	$autoFilters: JQuery = null;
	autoFiltersNames: string[] = [];
	$searchColumnBtn: JQuery = null;
	$searchColumnDropdown: JQuery = null;
	$stickyCols: JQuery = null;
	$floatTheadStickyCols: JQuery = null;
	enabledStickyColumns: boolean = false;
	enabledStickyHeader: boolean = false;
	$scrollPane: any = null;
	jScrollApi: any = null;
	$collapsedGroups: JQuery = null;
	filterData = {};
	
	constructor(
		protected settings: Settings,
		protected defaults?: any
	) {
		
		this.initProps();
	
	}
	
	initProps() {
		
		// Initialize selectors.
		this.$atumList = ( this.defaults && this.defaults.$atumList ) || $( '.atum-list-wrapper' );
		this.$atumTable = ( this.defaults && this.defaults.$atumTable ) || this.$atumList.find( '.atum-list-table' );
		this.$editInput = ( this.defaults && this.defaults.$editInput ) || this.$atumList.find( '#atum-column-edits' );
		this.$searchInput = ( this.defaults && this.defaults.$searchInput ) || this.$atumList.find( '.atum-post-search' );
		this.$autoFilters = this.$atumList.find( '#filters_container .auto-filter' );
		this.$searchColumnBtn = ( this.defaults && this.defaults.$searchColumnBtn ) || this.$atumList.find( '#search_column_btn' );
		this.$searchColumnDropdown = ( this.defaults && this.defaults.$searchColumnDropdown ) || this.$atumList.find( '#search_column_dropdown' );

		this.$autoFilters.each( ( index: number, elem: Element ) => {
			this.autoFiltersNames.push( $( elem ).attr( 'name' ) );
		} );
		
		const inputPerPage: string = this.$atumList.parent().siblings('#screen-meta').find('.screen-per-page').val();
		let perPage: number;
		
		// Initialize the filters' data
		if (!Utils.isNumeric(inputPerPage)) {
			perPage = this.settings.get('perPage') || 20;
		}
		else {
			perPage = parseInt(inputPerPage);
		}
		
		this.filterData = (this.defaults && this.defaults.filterData) || {
			token          : this.settings.get('nonce'),
			action         : this.$atumList.data('action'),
			screen         : this.$atumList.data('screen'),
			per_page       : perPage,
			paged          : 1,
			show_cb        : this.settings.get('showCb'),
			show_controlled: (Utils.filterQuery(location.search.substring(1), 'uncontrolled') !== '1' && $.address.parameter('uncontrolled') !== '1') ? 1 : 0,
			order          : this.settings.get('order'),
			orderby        : this.settings.get('orderby'),
			s              : '',
			search_column  : '',
			sold_last_days : '',
			view           : '',
			...this.getAutoFiltersValues( false, true )
		}
		
	}
	
	/**
	 * Get an object with all the auto-filters' values
	 *
	 * @param {boolean} getFromAddress Optional. Whether to try to get the value from their corresponding URL params.
	 * @param {boolean} emptyValues    Optional. Whether to return empty values.
	 *
	 * @return {any}
	 */
	getAutoFiltersValues( getFromAddress: boolean = false, emptyValues: boolean = false ): any {
		
		let autoFiltersValues: any = {};
	
		this.$autoFilters.each( ( index: number, elem: Element ) => {
			
			const $elem: JQuery = $( elem ),
			      name: string  = $elem.attr( 'name' );
			
			let value: string;
			
			if ( getFromAddress ) {
				value = $.address.parameter( name ) || '';
			}
			else {
				value = emptyValues ? '' : $elem.val() || '';
			}
			
			autoFiltersValues[ name ] = value;
			
		} );
		
		return autoFiltersValues;
	
	}
	
}
