/* =======================================
   SCROLL BAR FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import StickyHeader from './_sticky-header';
import Tooltip from '../_tooltip';
import { Utils } from '../../utils/_utils';

export default class ScrollBar {
	
	globals: Globals;
	stickyHeader: StickyHeader;
	tooltip: Tooltip;
	
	constructor(globalsObj: Globals, stickyHeaderObj: StickyHeader, tooltipObj: Tooltip) {
		
		this.globals = globalsObj;
		this.stickyHeader = stickyHeaderObj;
		this.tooltip = tooltipObj;
		
		// Init the table scrollbar.
		this.addScrollBar();
		
		// Reinitialise on window resizing.
		$(window).resize( () => {
			
			if (this.globals.$scrollPane && this.globals.$scrollPane.length && typeof this.globals.$scrollPane.data('jsp') !== 'undefined') {
				this.globals.jScrollApi.reinitialise();
			}
			
		}).resize();
		
		// Reload the scroll bar after the List Table is updated.
		this.globals.$atumList.on('atum-table-updated', () => {
			if (this.globals.$collapsedGroups === null) {
				this.reloadScrollbar();
			}
		})
		
		// Reload the scroll bar when the column groups are restored.
		this.globals.$atumList.on('atum-column-groups-restored', () => this.reloadScrollbar());
		
	}
	
	/**
	 * Add the horizontal scroll bar to the table
	 */
	addScrollBar() {
		
		if (this.globals.jScrollApi !== null) {
			this.reloadScrollbar();
			
			return;
		}
		
		// Wait until the thumbs are loaded and enable JScrollpane.
		let $tableWrapper: any = $('.atum-table-wrapper'),
		    scrollOpts    = {
			    horizontalGutter: 0,
			    verticalGutter  : 0,
		    };
		
		// Reset the sticky cols position and visibility to avoid flickering.
		if (this.globals.$stickyCols !== null) {
			this.globals.$stickyCols.hide().css('left', 0);
		}
		
		Utils.imagesLoaded($tableWrapper).then( () => {
			
			this.globals.$scrollPane = $tableWrapper.jScrollPane(scrollOpts);
			this.globals.jScrollApi  = this.globals.$scrollPane.data('jsp');
			
			// Bind Scroll-X events.
			this.globals.$scrollPane.on('jsp-scroll-x', (evt: any, scrollPositionX: number, isAtLeft: boolean, isAtRight: boolean) => {
					
				// Handle the sticky cols position and visibility when scrolling.
				if (this.globals.enabledStickyColumns === true && this.globals.$stickyCols !== null) {
					
					// Add the stickyCols table (if enabled).
					if (!this.globals.$atumList.find('.atum-list-table.cloned').length) {
						this.globals.$atumTable.after(this.globals.$stickyCols);
						this.tooltip.addTooltips();
						this.globals.$atumList.trigger('atum-added-sticky-columns');
					}
					
					// Hide the sticky cols when reaching the left side of the panel.
					if (scrollPositionX <= 0) {
						
						this.globals.$stickyCols.hide().css('left', 0);
						
						if (this.globals.$floatTheadStickyCols !== null) {
							this.globals.$floatTheadStickyCols.hide().css('left', 0);
						}
						
					}
					// Reposition the sticky cols while scrolling the pane.
					else {
						
						this.globals.$stickyCols.show().css('left', scrollPositionX);
						
						if (this.globals.$floatTheadStickyCols !== null) {
							this.globals.$floatTheadStickyCols.show().css('left', scrollPositionX);
						}
						
						// Ensure sticky column heights are matching.
						this.stickyHeader.adjustStickyHeaders(this.globals.$stickyCols, this.globals.$atumTable);
						
					}
					
				}
				
			});
			
			this.globals.$atumList.trigger('atum-scroll-bar-loaded');
			
		})
		
		$('.jspContainer').height($('.jspPane').height());
		
		$('.has-child').on('click', () => {
			
			setTimeout( () => $('.jspContainer').height( $('.jspPane').height() ), 500);
			
		});
		
	}
	
	/**
	 * Reload the scrollbar
	 */
	reloadScrollbar() {
		
		let positionX: number = 0;
		
		if (this.globals.jScrollApi !== null) {
			positionX = this.globals.jScrollApi.getContentPositionX();
			this.globals.jScrollApi.destroy();
			this.globals.jScrollApi = null;
		}
		
		this.addScrollBar();
		
		if (positionX > 0) {
			// Wait until the scroll bar is re-added to restore the position.
			this.globals.$atumList.on('atum-scroll-bar-loaded', () => {
				this.globals.jScrollApi.scrollToX(positionX);
			});
		}
		
	}
	
}
