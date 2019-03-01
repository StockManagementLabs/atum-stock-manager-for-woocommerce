/* ========================================
   LIST TABLE COMPONENT FOR POST TYPE LISTS
   ======================================== */

import Settings from '../../config/_settings';
import Hammer from 'hammerjs/hammer.min';

export default class PostTypeList {
	
	settings: Settings;
	$atumTable: JQuery;
	$tableContainer: JQuery;
	$scrollPane: JQuery;
	jScrollApi: any;
	
	constructor(settingsObj: Settings) {
		
		this.settings = settingsObj;
		
		this.$atumTable = $('.wp-list-table');
		$('.top').after('<div id="table-container"></div>');
		this.$tableContainer = $('#table-container');
		
		// Add placeholder to input search.
		$('#post-search-input').attr('placeholder', this.settings.get('placeholderSearch'));
		
		// Change nav and search div position.
		$('#posts-filter').prepend($('.subsubsub'));
		$('.subsubsub').append($('.search-box'));
		$('.wp-heading-inline').append($('.page-title-action'));
		$('.page-title-action').show();
		
		// Table position and id.
		this.$tableContainer.append(this.$atumTable);
		this.$atumTable.attr('id', 'list-table');
		
		// Add active class row function.
		this.addActiveClassRow();
		this.addScrollBar();
		
		// Footer position.
		$(window).on('load', () => {
			$('#wpfooter').show();
		});
		
		(<any>$('select')).select2({
			minimumResultsForSearch: 10,
		});
		
	}
	
	/**
	 * Add/remove row active class when checkbox is clicked
	 */
	addActiveClassRow() {
		
		this.$atumTable.find('tbody .check-column input:checkbox').change( () => {
			
			let $checkboxRow = this.$atumTable.find("#post-" + $(this).val());
			
			if ( $(this).is(':checked') ) {
				$checkboxRow.addClass('active-row');
			}
			else{
				$checkboxRow.removeClass('active-row');
			}
			
		});
		
		$('#cb-select-all-1').change( () => {
			
			$('tbody tr').each( (index: number, elem: any) => {
				
				let $row: JQuery = $(elem);
				
				if ( $row.find('input[type=checkbox]').is(':checked') ) {
					$row.addClass('active-row');
				}
				else {
					$row.removeClass('active-row');
				}
				
			});
			
		});
		
	}
	
	/**
	 * Add the horizontal scroll bar to the table
	 */
	addScrollBar() {
		
		// Wait until the thumbs are loaded and enable JScrollpane.
		let $tableWrapper: JQuery = this.$tableContainer,
		    scrollOpts: any    = {
			    horizontalGutter: 0,
			    verticalGutter  : 0,
			    resizeSensor    : true
		    };
		
		this.$scrollPane = (<any>$tableWrapper).jScrollPane(scrollOpts);
		this.jScrollApi  = this.$scrollPane.data('jsp');
		
		// Drag and drop scrolling on desktops
		let hammertime = new Hammer(this.$scrollPane.get(0), {});
		
		hammertime.on('panright panleft', (evt: any) => {
			
			const paneStartX: number   = this.jScrollApi.getContentPositionX(),
			      offset: number       = 20, // Move 20px each time (knowing that hammer gives the pan event a default threshold of 10)
			      displacement: number = evt.type === 'panright' ? paneStartX - offset : paneStartX + offset;
			
			this.jScrollApi.scrollToX( displacement, false);
			
		});
	}
	
}