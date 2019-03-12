/* =======================================
   ROUTER FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';
import ListTable from './_list-table';

export default class Router {
	
	navigationReady: boolean = false;
	numHashParameters: number = 0;
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private listTable: ListTable
	) {
		
		if (typeof $.address === 'undefined') {
			return;
		}
		
		// Hash history navigation.
		$.address.externalChange( () => {
			
			if (this.settings.get('ajaxFilter') !== 'yes') {
				// Force enabled or disabled search button.
				let searchInputVal: any = this.globals.$searchInput.val();
				$('.search-submit').prop('disabled', searchInputVal.length > 0 ? false : true);
			}
			
			let numCurrentParams: number = $.address.parameterNames().length;
			if (this.navigationReady === true && (numCurrentParams || this.numHashParameters !== numCurrentParams)) {
				this.listTable.updateTable();
			}
			
			this.navigationReady = true;
			
		})
		.init( () => {
			
			// When accessing externally or reloading the page, update the fields and the list.
			if ($.address.parameterNames().length) {
				
				// Init fields from hash parameters.
				let s: string            = $.address.parameter('s'),
				    searchColumn: string = $.address.parameter('search_column'),
				    optionVal: string    = '';
				
				if (s) {
					this.globals.$searchInput.val(s);
				}
				
				if (searchColumn) {
					
					$('#adv-settings :checkbox').each( (index: number, elem: Element) => {
						
						optionVal = $(elem).val();
						
						// Calc values are not searchable, also we can't search on thumb.
						if (optionVal.search('calc_') < 0 && optionVal !== 'thumb' && optionVal == searchColumn) {
							this.globals.$searchColumnBtn.trigger('atum-search-column-set-data', [optionVal, $(elem).parent().text() + ' <span class="caret"></span>']);
							return false;
						}
						
					});
					
				}
				
				this.listTable.updateTable();
				
			}
			
		});
		
		// Bind List Table links.
		this.bindListLinks();
		
		// Bind pagination input textbox.
		this.bindPageInput();
		
		// Re-bind the links after the List Table is updated.
		this.globals.$atumList.on('atum-table-updated', () => this.bindListLinks());
		
		// Bind Views, Pagination and Sortable links.
		this.globals.$atumList.on('click', '.tablenav-pages a, .item-heads a, .subsubsub a', (evt: JQueryEventObject) => {
			evt.preventDefault();
			this.updateHash();
		});
	
	}
	
	/**
	 * Bind the List Table links that will trigger URL hash changes
	 */
	bindListLinks() {
		this.globals.$atumList.find('.subsubsub a, .tablenav-pages a, .item-heads a').address();
	}
	
	/**
	 * Bind pagination input textbox
	 */
	bindPageInput() {
		
		this.globals.$atumList.on('keypress', '#current-page-selector', (evt: JQueryEventObject) => {
			if (evt.which === 13) {
				$.address.parameter('paged', $(evt.currentTarget).data('current'));
				this.updateHash();
			}
		});
		
	}
	
	/**
	 * Update the URL hash with the current filters
	 */
	updateHash() {
		
		Object.assign(this.globals.filterData, {
			view          : $.address.parameter('view') || this.globals.$atumList.find('.subsubsub a.current').attr('id') || '',
			product_cat   : this.globals.$atumList.find('.dropdown_product_cat').val() || '',
			product_type  : this.globals.$atumList.find('.dropdown_product_type').val() || '',
			supplier      : this.globals.$atumList.find('.dropdown_supplier').val() || '',
			extra_filter  : this.globals.$atumList.find('.dropdown_extra_filter').val() || '',
			paged         : parseInt($.address.parameter('paged') || this.globals.$atumList.find('.current-page').val() || this.settings.get('paged')),
			//s             : self.$searchInput.val() || '',
			//search_column : self.$searchColumnBtn.data('value') || '',
			s             : $.address.parameter('s') || '',
			search_column : $.address.parameter('search_column') || '',
			sold_last_days: $.address.parameter('sold_last_days') || '',
			orderby       : $.address.parameter('orderby') || this.settings.get('orderby'),
			order         : $.address.parameter('order') || this.settings.get('order'),
		});

		// Update the URL hash parameters.
		$.each(['view', 'product_cat', 'product_type', 'supplier', 'paged', 'order', 'orderby', 's', 'search_column', 'extra_filter', 'sold_last_days'], (index: number, elem: any) => {
			
			// Disable auto-update on each iteration until all the parameters have been set.
			this.navigationReady = false;
			
			// If it's not saved on the filter data, continue.
			if ( !this.globals.filterData.hasOwnProperty(elem) ) {
				return true;
			}
			
			$.address.parameter(elem, this.globals.filterData[elem]);
			
		});
		
		// Restore navigation and update if needed.
		let numCurrentParams: number = $.address.parameterNames().length;
		if (numCurrentParams || this.numHashParameters !== numCurrentParams) {
			this.listTable.updateTable();
		}
		
		this.navigationReady   = true;
		this.numHashParameters = numCurrentParams;
		
	}
	
}
