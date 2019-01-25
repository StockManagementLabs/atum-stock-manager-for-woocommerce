/* =======================================
   ROUTER FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings'
import Globals from './_globals'
import ListTable from './_list-table'

let Router = {
	
	navigationReady   : false,
	numHashParameters : 0,
	
	init() {
		
		if (typeof $.address === 'undefined') {
			return
		}
		
		let self = this
		
		// Hash history navigation.
		$.address.externalChange( () => {
			
			if (Settings.get('ajaxFilter') !== 'yes') {
				// Force enabled or disabled search button.
				let searchInputVal = Globals.$searchInput.val()
				$('.search-submit').prop('disabled', searchInputVal.length > 0 ? false : true)
			}
			
			let numCurrentParams = $.address.parameterNames().length;
			if (self.navigationReady === true && (numCurrentParams || self.numHashParameters !== numCurrentParams)) {
				ListTable.updateTable()
			}
			
			self.navigationReady = true
			
		})
		.init( () => {
			
			// When accessing externally or reloading the page, update the fields and the list.
			if ($.address.parameterNames().length) {
				
				// Init fields from hash parameters.
				let s            = $.address.parameter('s'),
				    searchColumn = $.address.parameter('search_column'),
				    optionVal    = ''
				
				
				if (s) {
					Globals.$searchInput.val(s)
				}
				
				if (searchColumn) {
					
					$('#adv-settings :checkbox').each( (index, elem) => {
						optionVal = $(elem).val()
						if (optionVal.search('calc_') < 0) { // Calc values are not searchable, also we can't search on thumb.
							
							if (optionVal !== 'thumb' && optionVal == searchColumn) {
								Globals.$searchColumnBtn.trigger('atum-search-column-set-data', [optionVal, $(elem).parent().text() + ' <span class="caret"></span>'])
								
								return false
							}
						}
					})
					
				}
				
				ListTable.updateTable()
				
			}
			
		})
		
		// Bind List Table links.
		this.bindListLinks()
		
		// Bind pagination input textbox.
		this.bindPageInput()
		
		// Re-bind the links after the List Table is updated.
		Globals.$atumList.on('atum-table-updated', this.bindListLinks)
		
		// Bind Views, Pagination and Sortable links.
		Globals.$atumList.on('click', '.tablenav-pages a, .item-heads a, .subsubsub a', (evt) => {
			evt.preventDefault()
			self.updateHash()
		})
	
	},
	
	/**
	 * Bind the List Table links that will trigger URL hash changes
	 */
	bindListLinks() {
		Globals.$atumList.find('.subsubsub a, .tablenav-pages a, .item-heads a').address()
	},
	
	/**
	 * Bind pagination input textbox
	 */
	bindPageInput() {
		
		let self = this
		
		Globals.$atumList.on('keypress', '#current-page-selector', (evt) => {
			if (evt.which === 13) {
				$.address.parameter('paged', $(evt.target).data('current'))
				self.updateHash()
			}
		})
		
	},
	
	/**
	 * Update the URL hash with the current filters
	 */
	updateHash() {
		
		let self             = this,
		    numCurrentParams = $.address.parameterNames().length
		
		Globals.filterData = $.extend(Globals.filterData, {
			view          : $.address.parameter('view') || Globals.$atumList.find('.subsubsub a.current').attr('id') || '',
			product_cat   : Globals.$atumList.find('.dropdown_product_cat').val() || '',
			product_type  : Globals.$atumList.find('.dropdown_product_type').val() || '',
			supplier      : Globals.$atumList.find('.dropdown_supplier').val() || '',
			extra_filter  : Globals.$atumList.find('.dropdown_extra_filter').val() || '',
			paged         : parseInt($.address.parameter('paged') || Globals.$atumList.find('.current-page').val() || Settings.get('paged')),
			//s             : self.$searchInput.val() || '',
			//search_column : self.$searchColumnBtn.data('value') || '',
			s             : $.address.parameter('s') || '',
			search_column : $.address.parameter('search_column') || '',
			sold_last_days: $.address.parameter('sold_last_days') || '',
			orderby       : $.address.parameter('orderby') || Settings.get('orderby'),
			order         : $.address.parameter('order') || Settings.get('order'),
		})
		
		// Update the URL hash parameters.
		$.each(['view', 'product_cat', 'product_type', 'supplier', 'paged', 'order', 'orderby', 's', 'search_column', 'extra_filter', 'sold_last_days'], (index, elem) => {
			
			// Disable auto-update on each iteration until all the parameters have been set.
			self.navigationReady = false
			
			// If it's not saved on the filter data, continue.
			if ( typeof Globals.filterData[elem] === 'undefined' ) {
				return true
			}
			
			// If it's the default value, is not needed.
			if (typeof Settings.get(elem) !== 'undefined' && Settings.get(elem) === Globals.filterData[elem]) {
				$.address.parameter(elem, '')
				
				return true
			}
			
			$.address.parameter(elem, Globals.filterData[elem])
			
		})
		
		// Restore navigation and update if needed.
		if (numCurrentParams || this.numHashParameters !== numCurrentParams) {
			ListTable.updateTable();
		}
		
		this.navigationReady   = true
		this.numHashParameters = numCurrentParams
		
	},
	
}

module.exports = Router