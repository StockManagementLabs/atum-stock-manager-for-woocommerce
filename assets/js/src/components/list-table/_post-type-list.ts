/* ========================================
   LIST TABLE COMPONENT FOR POST TYPE LISTS
   ======================================== */

import ActiveRow from './_active-row';
import EnhancedSelect from '../_enhanced-select';
import Globals from './_globals';
import Settings from '../../config/_settings';
import ShowFilters from './_show-filters';

export default class PostTypeList {
	
	$tableContainer: JQuery;
	$scrollPane: JQuery;
	$topTableNav:JQuery;
	jScrollApi: any;
	showFilters: ShowFilters;
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private enhancedSelect: EnhancedSelect
	) {
		
		this.$topTableNav = $('.tablenav.top');
		this.$tableContainer = $('<div class="atum-table-wrapper" />');
		
		this.$topTableNav.after( this.$tableContainer );
		
		// Add placeholder to input search.
		$('#post-search-input').attr('placeholder', this.settings.get('placeholderSearch'));

        // Add nav with dragging functionality.
        $('.subsubsub').after( `
			<div class="list-table-header">
                <div id="scroll-stock_central_nav" class="nav-container-box">
                    <nav id="stock_central_nav" class="nav-with-scroll-effect dragscroll">
                        <div class="overflow-opacity-effect-right"></div>
                        <div class="overflow-opacity-effect-left"></div>
                    </nav>
                </div>
            </div>`);

        $('.nav-with-scroll-effect').prepend($('.subsubsub'));
        $('.list-table-header')
            .append($('.search-box'))
            .addClass('no-margin')
	        .prependTo($('form#posts-filter'));
        
        // Add show filters button for mobile screens.
		this.$topTableNav.find('.bulkactions').after(this.settings.get('showFiltersButton'));
		this.showFilters = new ShowFilters(this.$topTableNav, this.settings);

        // Show add button in head.
        $('.wp-heading-inline').append( $('.page-title-action').show() );

        // Add list-table-header inside form.
        $('#posts-filter').prepend($('.list-table-header'));

        $('#post-query-submit').attr('class', 'btn btn-warning');
        $('#doaction, #doaction2').attr('class', 'btn btn-warning action');
		
		// Table position and id.
		this.$tableContainer.append( this.globals.$atumTable );
		this.globals.$atumTable.attr('id', 'list-table');
		
		// Add active class row function.
		ActiveRow.addActiveClassRow( this.globals.$atumTable );
		
		// Footer position.
		$(window).on('load', () => $('#wpfooter').show() );
		
		this.enhancedSelect.doSelect2( $('select') );
		
	}
	
	/**
	 * Add the horizontal scroll bar to the table
	 */
	/*addScrollBar() {
		
		// Wait until the thumbs are loaded and enable JScrollpane.
		let $tableWrapper: JQuery = this.$tableContainer,
		    scrollOpts: any       = {
			    horizontalGutter: 0,
			    verticalGutter  : 0,
			    resizeSensor    : true,
		    };
		
		this.$scrollPane = (<any>$tableWrapper).jScrollPane(scrollOpts);
		this.jScrollApi  = this.$scrollPane.data('jsp');
		this.addDragScroll();
		
	}
	
	addDragScroll() {
		
		// Drag and drop scrolling on desktops
		let hammertime: any = new Hammer(this.$scrollPane.get(0), {});
		
		hammertime.on('panright panleft', (evt: any) => {
			
			const velocityModifier = 10,
			      displacement     = this.jScrollApi.getContentPositionX() - (evt.distance * (evt.velocityX / velocityModifier));
			
			this.jScrollApi.scrollToX(displacement, false);
			
		});
		
	}*/
	
}