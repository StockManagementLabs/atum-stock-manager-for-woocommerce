/* =======================================
   SEARCH BY COLUMN FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';

let SearchByColumn = {
	
	init() {
		
		if ($('.atum-post-search-with-dropdown').length) {
			
			let self = this;
			
			this.setup();
			
			$('#adv-settings input:checkbox').change( () => {
				setTimeout(self.setup, 500); // Performance.
			});
		}
	
	},
	
	/**
	 * Fill the search by column dropdown with the active screen options checkboxes
	 */
	setup() {
		
		let dropdownItem = '<a class="dropdown-item" href="#"></a>';
		
		Globals.$searchColumnDropdown.empty();
		
		// Append the no column and the title items.
		Globals.$searchColumnDropdown.append($(dropdownItem).data('value', '').text(Globals.$searchColumnDropdown.data('no-option')));
		Globals.$searchColumnDropdown.append($(dropdownItem).data('value', 'title').text(Globals.$searchColumnDropdown.data('product-title')));
		
		$('#adv-settings input:checked').each( (index, elem) => {
			
			let optionVal   = $(elem).val(),
			    columnLabel = $(elem).parent().text();
			
			if (optionVal.search('calc_') < 0 && optionVal !== 'thumb') { // Calc values are not searchable, also we can't search on thumb.
				
				Globals.$searchColumnDropdown.append($(dropdownItem).data('value', optionVal).text(columnLabel));
				
				// Most probably, we are on init and ?search_column has a value. Or maybe not, but, if this happens, force change.
				if ($.address.parameter('search_column') !== Globals.$searchColumnBtn.data('value') && Globals.$searchColumnBtn.data('value') === optionVal) {
					Globals.$searchColumnBtn.trigger('atum-search-column-set-data', [optionVal, columnLabel + ' <span class="caret"></span>']);
				}
				
			}
			
		})
		
		
		Globals.$searchColumnBtn
		
			// Bind clicks on search by column button.
			.click( (evt) => {
				$(evt.target).parent().find('.dropdown-menu').toggle();
				evt.stopPropagation();
			})
			
			// Set $searchColumnBtn data-value and html content.
			.on('atum-search-column-set-data', (evt, value, html) => {
				
				let $searchColBtn = $(evt.target);
				
				$searchColBtn.html(html);
				$searchColBtn.data('value', value);
				
				Globals.$searchColumnDropdown.children('a.active').removeClass('active');
				Globals.$searchColumnDropdown.children('a').filterByData('value', value).addClass('active');
				
			});
		
		// Bind clicks on dropdown menu items.
		Globals.$searchColumnDropdown.find('a').click( (evt) => {
			
			evt.preventDefault();
			
			let $item = $(evt.target);
			
			Globals.$searchColumnBtn.trigger('atum-search-column-set-data', [$item.data('value'), $item.text() + ' <span class="caret"></span>']);
			
			$item.parents().find('.dropdown-menu').hide();
			Globals.$searchColumnDropdown.children('a.active').removeClass('active');
			$item.addClass('active');
			
			const fieldType = $.inArray($item.data('value'), Settings.get('searchableColumns').numeric) > -1 ? 'number' : 'search';
			Globals.$searchInput.attr('type', fieldType);
			
			if (Settings.get('ajaxFilter') === 'yes') {
				Globals.$searchColumnBtn.trigger('atum-search-column-data-changed');
			}
			
			$('.dropdown-toggle').attr('data-original-title', $item.html());
			
		});
		
		$(document).click( () => {
			Globals.$searchColumnDropdown.hide();
		});
		
	},
	
}

module.exports = SearchByColumn;
