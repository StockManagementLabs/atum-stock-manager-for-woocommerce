/* =======================================
   LIST TABLE
   ======================================= */

let ListTable = {
	
	settings  : atumListVars || {},
	filterData: {},
	
	init: function() {
		
		// Initialize selectors
		this.$atumList = $('.atum-list-wrapper')
		this.$atumTable = this.$atumList.find('.atum-list-table')
		this.$editInput = this.$atumList.find('#atum-column-edits')
		this.$searchInput = this.$atumList.find('.atum-post-search')
		
		let self         = this;
		    inputPerPage = this.$atumList.parent().siblings('#screen-meta').find('.screen-per-page').val(),
		    perPage
		
		//
		// Initialize the filters' data
		// -----------------------------
		if (!$.isNumeric(inputPerPage)) {
			perPage = this.settings.perPage || 20
		}
		else {
			perPage = parseInt(inputPerPage)
		}
		
		this.filterData = {
			token          : this.settings.nonce,
			action         : this.$atumList.data('action'),
			screen         : this.$atumList.data('screen'),
			per_page       : perPage,
			show_cb        : this.settings.showCb,
			show_controlled: (this.__query(location.search.substring(1), 'uncontrolled') !== '1' && $.address.parameter('uncontrolled') !== '1') ? 1 : 0,
			order          : this.settings.order,
			orderby        : this.settings.orderby,
		}
		
	},
	
}

module.exports = ListTable;
