/* =======================================
   SCROLL BAR FOR LIST TABLES
   ======================================= */

let ScrollBar = {
	
	jScrollApi : null,
	$scrollPane: null,
	
	init: function() {
		
		let self = this;
		
		$(window).resize(function() {
			
			if (self.$scrollPane && self.$scrollPane.length && typeof self.$scrollPane.data('jsp') !== 'undefined') {
				self.jScrollApi.reinitialise();
			}
			
		}).resize();
		
	},
	
	/**
	 * Add the horizontal scroll bar to the table
	 */
	addScrollBar: function() {
		
		if (this.jScrollApi !== null) {
			this.reloadScrollbar();
			
			return;
		}
		
		// Wait until the thumbs are loaded and enable JScrollpane
		let self          = this,
		    $tableWrapper = $('.atum-table-wrapper'),
		    scrollOpts    = {
			    horizontalGutter: 0,
			    verticalGutter  : 0,
		    };
		
		// Reset the sticky cols position and visibility to avoid flickering
		if (StickyCols.$stickyCols !== null) {
			StickyCols.$stickyCols.hide().css('left', 0);
		}
		
		$tableWrapper.imagesLoaded().then(function() {
			
			self.$scrollPane = $tableWrapper.jScrollPane(scrollOpts);
			self.jScrollApi  = self.$scrollPane.data('jsp');
			
			// Bind events
			self.$scrollPane
				.on('jsp-initialised', function(event, isScrollable) {
					
					// Add the stickyCols table
					if (StickyCols.$stickyCols !== null && !ListTable.$atumList.find('.atum-list-table.cloned').length) {
						ListTable.$atumTable.after(StickyCols.$stickyCols);
						Tooltips.addTooltips();
						ListTable.$atumList.trigger('atum-added-sticky-columns');
					}
					
				})
				.on('jsp-scroll-x', function(event, scrollPositionX, isAtLeft, isAtRight) {
					
					// Handle the sticky cols position and visibility when scrolling
					if (StickyCols.$stickyCols !== null) {
						
						// Hide the sticky cols when reaching the left side of the panel
						if (scrollPositionX <= 0) {
							StickyCols.$stickyCols.hide().css('left', 0);
							
							if (StickyCols.$floatTheadStickyCols !== null) {
								StickyCols.$floatTheadStickyCols.hide().css('left', 0);
							}
							
						}
						// Reposition the sticky cols while scrolling the pane
						else {
							
							StickyCols.$stickyCols.show().css('left', scrollPositionX);
							
							if (StickyCols.$floatTheadStickyCols !== null) {
								StickyCols.$floatTheadStickyCols.show().css('left', scrollPositionX);
							}
							
							// Ensure sticky column heights are matching
							StickyCols.adjustStickyHeaders(StickyCols.$stickyCols, ListTable.$atumTable);
							
						}
						
					}
					
				});
			
			ListTable.$atumList.trigger('atum-scroll-bar-loaded');
			
		});
		
		$('.jspContainer').height($('.jspPane').height());
		
		$('.has-child').on('click', function() {
			setTimeout(function() {
				$('.jspContainer').height($('.jspPane').height());
			}, 500);
		});
		
	},
	
	/**
	 * Reload the scrollbar
	 */
	reloadScrollbar: function() {
		
		let self      = this,
		    positionX = 0;
		
		if (this.jScrollApi !== null) {
			positionX = this.jScrollApi.getContentPositionX();
			this.jScrollApi.destroy();
			this.jScrollApi = null;
		}
		
		this.addScrollBar();
		
		if (positionX > 0) {
			// Wait until the scroll bar is re-added to restore the position
			ListTable.$atumList.on('atum-scroll-bar-loaded', function() {
				self.jScrollApi.scrollToX(positionX);
			});
		}
	},
	
}

module.exports = ScrollBar;