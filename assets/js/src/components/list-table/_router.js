/* =======================================
   ROUTER FOR LIST TABLES
   ======================================= */

let Router = {
	
	navigationReady   : false,
	numHashParameters : 0,
	
	init : function() {
		
		if (typeof $.address === 'undefined') {
			return
		}
		
		let self = this
		
		this.bindListLinks()
		
		// Hash history navigation
		$.address.externalChange(function() {
			
			if (ListTable.settings.ajaxFilter !== 'yes') {
				// Force enabled or disabled search button
				let searchInputVal = ListTable.$searchInput.val()
				$('.search-submit').prop('disabled', searchInputVal.length > 0 ? false : true)
			}
			
			let numCurrentParams = $.address.parameterNames().length;
			if (self.navigationReady === true && (numCurrentParams || self.numHashParameters !== numCurrentParams)) {
				self.updateTable()
			}
			
			self.navigationReady = true
			
		})
		.init(function() {
			
			// When accessing externally or reloading the page, update the fields and the list
			if ($.address.parameterNames().length) {
				
				// Init fields from hash parameters
				let s            = $.address.parameter('s'),
				    searchColumn = $.address.parameter('search_column'),
				    optionVal    = ''
				
				
				if (s) {
					ListTable.$searchInput.val(s)
				}
				
				if (searchColumn) {
					
					$('#adv-settings :checkbox').each(function() {
						optionVal = $(this).val()
						if (optionVal.search('calc_') < 0) { // Calc values are not searchable, also we can't search on thumb
							
							if (optionVal !== 'thumb' && optionVal == searchColumn) {
								ListTable.$searchColumnBtn.trigger('setHtmlAndDataValue', [optionVal, $(this).parent().text() + ' <span class="caret"></span>'])
								
								return false;
							}
						}
					})
				}
				
				ListTable.updateTable()
				
			}
			
		})
	
	},
	
	/**
	 * Bind the List Table links that will trigger URL hash changes
	 */
	bindListLinks: function() {
		ListTable.$atumList.find('.subsubsub a, .tablenav-pages a, .item-heads a').address();
	},
	
}

module.exports = Router;