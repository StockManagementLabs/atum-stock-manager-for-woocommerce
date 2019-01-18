/* =======================================
   SEARCH BY COLUMN FOR LIST TABLES
   ======================================= */

let SearchByColumn = {
	
	$searchColumnBtn      : ListTable.$atumList.find('#search_column_btn'),
	$searchColumnDropdown : ListTable.$atumList.find('#search_column_dropdown'),
	$searchInput          : ListTable.$atumList.find('.atum-post-search-with-dropdown'),
	
	init: function() {
		
		if (this.$searchInput.length) {
			
			let self = this
			this.setup()
			
			$('#adv-settings input[type=checkbox]').change(function() {
				setTimeout(self.setup, 500) // Performance
			});
		}
	
	},
	
	/**
	 * Fill the search by column dropdown with the active screen options checkboxes
	 */
	setup: function() {
		
		let self         = this,
		    dropdownItem = '<a class="dropdown-item" href="#"></a>'
		
		this.$searchColumnDropdown.empty()
		
		// Append the no column and the title items.
		this.$searchColumnDropdown.append($(dropdownItem).data('value', '').text(this.$searchColumnDropdown.data('no-option')))
		this.$searchColumnDropdown.append($(dropdownItem).data('value', 'title').text(this.$searchColumnDropdown.data('product-title')))
		
		$('#adv-settings input:checked').each(function() {
			
			let optionVal   = $(this).val(),
			    columnLabel = $(this).parent().text()
			
			if (optionVal.search('calc_') < 0 && optionVal !== 'thumb') { // Calc values are not searchable, also we can't search on thumb
				
				self.$searchColumnDropdown.append($(dropdownItem).data('value', optionVal).text(columnLabel))
				
				// Most probably, we are on init and ?search_column has a value. Or maybe not, but, if this happens, force change
				if ($.address.parameter('search_column') !== self.$searchColumnBtn.data('value') && self.$searchColumnBtn.data('value') === optionVal) {
					self.$searchColumnBtn.trigger('setHtmlAndDataValue', [optionVal, columnLabel + ' <span class="caret"></span>'])
				}
				
			}
			
		});
		
		this.$searchColumnBtn.click(function(evt) {
			$(this).parent().find('.dropdown-menu').toggle();
			evt.stopPropagation();
		});
		
		// TODO click on drop element
		this.$searchColumnDropdown.find('a').click(function(evt) {
			
			evt.preventDefault();
			
			self.$searchColumnBtn.trigger('setHtmlAndDataValue', [$(this).data('value'), $(this).text() + ' <span class="caret"></span>'])
			
			$(this).parents().find('.dropdown-menu').hide()
			self.$searchColumnDropdown.children('a.active').removeClass('active')
			$(this).addClass('active')
			
			const fieldType = $.inArray($(this).data('value'), ListTable.settings.searchableColumns.numeric) > -1 ? 'number' : 'search'
			self.$searchInput.attr('type', fieldType)
			
			if (self.settings.ajaxFilter === 'yes') {
				self.$searchColumnBtn.trigger('search_column_data_changed')
			}
			
			$('.dropdown-toggle').attr('data-original-title', $(this).html())
			
		});
		
		$(document).click(function() {
			self.$searchColumnDropdown.hide()
		});
		
	},
	
}

module.exports = SearchByColumn;
