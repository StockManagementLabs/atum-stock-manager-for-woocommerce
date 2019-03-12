/* =======================================
   GLOBALS FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import { Utils } from '../../utils/_utils';

export default class Globals {
	
	$atumList: JQuery = null;
	$atumTable: JQuery = null;
	$editInput: JQuery = null;
	$searchInput: JQuery = null;
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
		private settings: Settings,
		private defaults?: any
	) {
		
		// Initialize selectors.
		this.$atumList = (defaults && defaults.$atumList) || $('.atum-list-wrapper');
		this.$atumTable = (defaults && defaults.$atumTable) || this.$atumList.find('.atum-list-table');
		this.$editInput = (defaults && defaults.$editInput) || this.$atumList.find('#atum-column-edits');
		this.$searchInput = (defaults && defaults.$searchInput) || this.$atumList.find('.atum-post-search');
		this.$searchColumnBtn = (defaults && defaults.$searchColumnBtn) || this.$atumList.find('#search_column_btn');
		this.$searchColumnDropdown = (defaults && defaults.$searchColumnDropdown) || this.$atumList.find('#search_column_dropdown');
		
		let inputPerPage = this.$atumList.parent().siblings('#screen-meta').find('.screen-per-page').val(),
		    perPage      = null;
		
		// Initialize the filters' data
		if (!$.isNumeric(inputPerPage)) {
			perPage = this.settings.get('perPage') || 20;
		}
		else {
			perPage = parseInt(inputPerPage);
		}
		
		this.filterData = (defaults && defaults.filterData) || {
			token          : this.settings.get('nonce'),
			action         : this.$atumList.data('action'),
			screen         : this.$atumList.data('screen'),
			per_page       : perPage,
			show_cb        : this.settings.get('showCb'),
			show_controlled: (Utils.filterQuery(location.search.substring(1), 'uncontrolled') !== '1' && $.address.parameter('uncontrolled') !== '1') ? 1 : 0,
			order          : this.settings.get('order'),
			orderby        : this.settings.get('orderby'),
		}
	
	}
	
}
