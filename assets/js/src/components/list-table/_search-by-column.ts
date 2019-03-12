/* =======================================
   SEARCH BY COLUMN FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';
import { Utils } from '../../utils/_utils';

export default class SearchByColumn {

	constructor(
		private settings: Settings,
		private globals: Globals
	) {
		
		if ($('.atum-post-search-with-dropdown').length) {
			
			this.setup();
			
			$('#adv-settings input:checkbox').change( () => {
				setTimeout( () => this.setup(), 500 ); // Performance.
			} );
		}
		
	}
	
	/**
	 * Fill the search by column dropdown with the active screen options checkboxes
	 */
	setup() {
		
		let $dropdownItem: JQuery = $('<a class="dropdown-item" href="#" />');
		
		this.globals.$searchColumnDropdown.empty();
		
		// Append the no column and the title items.
		this.globals.$searchColumnDropdown.append( $dropdownItem.clone().data('value', '').text( this.globals.$searchColumnDropdown.data('no-option') ) );
		this.globals.$searchColumnDropdown.append( $dropdownItem.clone().data('value', 'title').text( this.globals.$searchColumnDropdown.data('product-title') ) );
		
		$('#adv-settings input:checked').each( (index: number, elem: Element) => {
			
			let $elem: JQuery       = $(elem),
			    optionVal: string   = $elem.val(),
			    columnLabel: string = $elem.parent().text();
			
			// Calc values are not searchable, also we can't search on thumb.
			if (optionVal.search('calc_') < 0 && optionVal !== 'thumb') {
				
				this.globals.$searchColumnDropdown.append( $dropdownItem.clone().data('value', optionVal).text(columnLabel) );
				
				// Most probably, we are on init and ?search_column has a value. Or maybe not, but, if this happens, force change.
				if ($.address.parameter('search_column') !== this.globals.$searchColumnBtn.data('value') && this.globals.$searchColumnBtn.data('value') === optionVal) {
					this.globals.$searchColumnBtn.trigger('atum-search-column-set-data', [optionVal, columnLabel + ' <span class="caret"></span>']);
				}
				
			}
			
		});
		
		this.globals.$searchColumnBtn
		
			// Bind clicks on search by column button.
			.click( (evt: JQueryEventObject) => {
				$(evt.currentTarget).parent().find('.dropdown-menu').toggle();
				evt.stopPropagation();
			})
			
			// Set $searchColumnBtn data-value and html content.
			.on('atum-search-column-set-data', (evt: JQueryEventObject, value: string, html: string) => {
				
				let $searchColBtn: JQuery  = $(evt.currentTarget),
				    $dropDownLinks: JQuery = this.globals.$searchColumnDropdown.children('a');
				
				$searchColBtn.html(html);
				$searchColBtn.data('value', value);
				
				$dropDownLinks.filter('.active').removeClass('active');
				Utils.filterByData($dropDownLinks, 'value', value);
				$dropDownLinks.addClass('active');
				
			});
		
		// Bind clicks on dropdown menu items.
		this.globals.$searchColumnDropdown.find('a').click( (evt: JQueryEventObject) => {
			
			evt.preventDefault();
			
			let $item: JQuery = $(evt.currentTarget);
			
			this.globals.$searchColumnBtn.trigger('atum-search-column-set-data', [$item.data('value'), $item.text() + ' <span class="caret"></span>']);
			
			$item.parents().find('.dropdown-menu').hide();
			this.globals.$searchColumnDropdown.children('a.active').removeClass('active');
			$item.addClass('active');
			
			const fieldType = $.inArray($item.data('value'), this.settings.get('searchableColumns').numeric) > -1 ? 'number' : 'search';
			this.globals.$searchInput.attr('type', fieldType);
			
			if (this.settings.get('ajaxFilter') === 'yes') {
				this.globals.$searchColumnBtn.trigger('atum-search-column-data-changed');
			}
			
			$('.dropdown-toggle').attr('data-original-title', $item.html());
			
		});
		
		$(document).click( () => {
			this.globals.$searchColumnDropdown.hide();
		});
		
	}
	
}

