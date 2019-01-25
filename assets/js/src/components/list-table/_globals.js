/* =======================================
   GLOBALS FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Utils from '../../utils/_utils';

let Globals = {
	
	$atumList            : null,
	$atumTable           : null,
	$editInput           : null,
	$searchInput         : null,
	$searchColumnBtn     : null,
	$searchColumnDropdown: null,
	$stickyCols          : null,
	$floatTheadStickyCols: null,
	enabledStickyColumns : false,
	enabledStickyHeader  : false,
	$scrollPane          : null,
	jScrollApi           : null,
	$collapsedGroups     : null,
	filterData           : {},
	
	init() {
		
		// Initialize selectors.
		this.$atumList = $('.atum-list-wrapper');
		this.$atumTable = this.$atumList.find('.atum-list-table');
		this.$editInput = this.$atumList.find('#atum-column-edits');
		this.$searchInput = this.$atumList.find('.atum-post-search');
		this.$searchColumnBtn = this.$atumList.find('#search_column_btn');
		this.$searchColumnDropdown = this.$atumList.find('#search_column_dropdown');
		
		let inputPerPage = this.$atumList.parent().siblings('#screen-meta').find('.screen-per-page').val(),
		    perPage      = null;
		
		// Initialize the filters' data
		if (!$.isNumeric(inputPerPage)) {
			perPage = Settings.get('perPage') || 20;
		}
		else {
			perPage = parseInt(inputPerPage);
		}
		
		this.filterData = {
			token          : Settings.get('nonce'),
			action         : this.$atumList.data('action'),
			screen         : this.$atumList.data('screen'),
			per_page       : perPage,
			show_cb        : Settings.get('showCb'),
			show_controlled: (Utils.filterQuery(location.search.substring(1), 'uncontrolled') !== '1' && $.address.parameter('uncontrolled') !== '1') ? 1 : 0,
			order          : Settings.get('order'),
			orderby        : Settings.get('orderby'),
		}
	
	},
	
}

module.exports = Globals;