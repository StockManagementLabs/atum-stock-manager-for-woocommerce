/* ========================================
   LIST TABLE COMPONENT FOR POST TYPE LISTS
   ======================================== */

import Settings from '../../config/_settings';
import Hammer from 'hammerjs/hammer.min';
import { ActiveRow } from './_active-row';
import EnhancedSelect from '../_enhanced-select';
import Globals from './_globals';

export default class PostTypeList {
	
	settings: Settings;
	globals: Globals;
	enhancedSelect: EnhancedSelect;
	$tableContainer: JQuery;
	$scrollPane: JQuery;
	jScrollApi: any;
	
	constructor(settingsObj: Settings, globalsObj: Globals, enhancedSelectObj: EnhancedSelect) {
		
		this.settings = settingsObj;
		this.globals = globalsObj;
		this.enhancedSelect = enhancedSelectObj;
		this.$tableContainer = $('<div class="atum-table-wrapper" />');
		
		$('.tablenav.top').after( this.$tableContainer );
		
		// Add placeholder to input search.
		$('#post-search-input').attr('placeholder', this.settings.get('placeholderSearch'));
		
		// Change nav and search div position.
		this.globals.$atumList.prepend($('.subsubsub'));
		$('.subsubsub').append($('.search-box'));
		$('.wp-heading-inline').append($('.page-title-action'));
		$('.page-title-action').show();
		
		// Table position and id.
		this.$tableContainer.append( this.globals.$atumTable );
		this.globals.$atumTable.attr('id', 'list-table');
		
		// Add active class row function.
		ActiveRow.addActiveClassRow( this.globals.$atumTable );
		
		// Footer position.
		$(window).on('load', () => {
			$('#wpfooter').show();
		});
		
		this.enhancedSelect.doSelect2( $('select') );
		
	}
	
	/**
	 * Add the horizontal scroll bar to the table
	 */
	addScrollBar() {
		
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
		
	}
	
}