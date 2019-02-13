/* =======================================
   SCROLL BAR FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import StickyHeader from './_sticky-header';
import Tooltip from '../_tooltip';

let ScrollBar = {
	
	init() {
		
		let self = this;
		
		// Init the table scrollbar.
		this.addScrollBar();
		
		// Reinitialise on window resizing.
		$(window).resize( () => {
			
			if (Globals.$scrollPane && Globals.$scrollPane.length && typeof Globals.$scrollPane.data('jsp') !== 'undefined') {
				Globals.jScrollApi.reinitialise();
			}
			
		}).resize();
		
		// Reload the scroll bar after the List Table is updated.
		Globals.$atumList.on('atum-table-updated', () => {
			if (Globals.$collapsedGroups === null) {
				self.reloadScrollbar();
			}
		})
		
		// Reload the scroll bar when the column groups are restored.
		Globals.$atumList.on('atum-column-groups-restored', () => this.reloadScrollbar);
		
	},
	
	/**
	 * Add the horizontal scroll bar to the table
	 */
	addScrollBar() {
		
		if (Globals.jScrollApi !== null) {
			this.reloadScrollbar();
			
			return;
		}
		
		// Wait until the thumbs are loaded and enable JScrollpane.
		let $tableWrapper = $('.atum-table-wrapper'),
		    scrollOpts    = {
			    horizontalGutter: 0,
			    verticalGutter  : 0,
		    };
		
		// Reset the sticky cols position and visibility to avoid flickering.
		if (Globals.$stickyCols !== null) {
			Globals.$stickyCols.hide().css('left', 0);
		}
		
		$tableWrapper.imagesLoaded().then( () => {
			
			Globals.$scrollPane = $tableWrapper.jScrollPane(scrollOpts);
			Globals.jScrollApi  = Globals.$scrollPane.data('jsp');
			
			// Bind Scroll-X events.
			Globals.$scrollPane.on('jsp-scroll-x', (evt, scrollPositionX, isAtLeft, isAtRight) => {
					
				// Handle the sticky cols position and visibility when scrolling.
				if (Globals.enabledStickyColumns === true && Globals.$stickyCols !== null) {
					
					// Add the stickyCols table (if enabled).
					if (!Globals.$atumList.find('.atum-list-table.cloned').length) {
						Globals.$atumTable.after(Globals.$stickyCols);
						Tooltip.addTooltips();
						Globals.$atumList.trigger('atum-added-sticky-columns');
					}
					
					// Hide the sticky cols when reaching the left side of the panel.
					if (scrollPositionX <= 0) {
						
						Globals.$stickyCols.hide().css('left', 0);
						
						if (Globals.$floatTheadStickyCols !== null) {
							Globals.$floatTheadStickyCols.hide().css('left', 0);
						}
						
					}
					// Reposition the sticky cols while scrolling the pane.
					else {
						
						Globals.$stickyCols.show().css('left', scrollPositionX);
						
						if (Globals.$floatTheadStickyCols !== null) {
							Globals.$floatTheadStickyCols.show().css('left', scrollPositionX);
						}
						
						// Ensure sticky column heights are matching.
						StickyHeader.adjustStickyHeaders(Globals.$stickyCols, Globals.$atumTable);
						
					}
					
				}
				
			});
			
			Globals.$atumList.trigger('atum-scroll-bar-loaded');
			
		})
		
		$('.jspContainer').height($('.jspPane').height());
		
		$('.has-child').on('click', () => {
			
			setTimeout( () => {
				$('.jspContainer').height($('.jspPane').height());
			}, 500);
			
		});
		
	},
	
	/**
	 * Reload the scrollbar
	 */
	reloadScrollbar() {
		
		let positionX = 0;
		
		if (Globals.jScrollApi !== null) {
			positionX = Globals.jScrollApi.getContentPositionX();
			Globals.jScrollApi.destroy();
			Globals.jScrollApi = null;
		}
		
		this.addScrollBar();
		
		if (positionX > 0) {
			// Wait until the scroll bar is re-added to restore the position.
			Globals.$atumList.on('atum-scroll-bar-loaded', () => {
				Globals.jScrollApi.scrollToX(positionX);
			})
		}
	},
	
}

module.exports = ScrollBar;