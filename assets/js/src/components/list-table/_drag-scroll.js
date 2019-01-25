/* =======================================
   DRAG-SCROLL FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import Tooltip from '../_tooltip';
import dragscroll from 'dragscroll/dragscroll';
import Hammer from 'hammerjs/hammer.min';

let DragScroll = {
	
	init() {
	
		// Load Hammer for table dragging functionality.
		Globals.$atumList.on('atum-scroll-bar-loaded', this.loadHammer);
		
		// Add horizontal drag-scroll to table filters.
		this.initHorizontalDragScroll();
		
		// Re-add the horizontal drag-scroll when the List Table is updated.
		Globals.$atumList.on('atum-table-updated', this.initHorizontalDragScroll);
	
	},
	
	loadHammer() {
		
		// Drag and drop scrolling on desktops.
		const hammertime = new Hammer(Globals.$scrollPane.get(0), {});
		
		hammertime
			
			// Horizontal drag scroll (JScrollPane).
			.on('panright panleft', (evt) => {
				
				const velocityModifier = 10,
				      displacement     = Globals.jScrollApi.getContentPositionX() - (evt.distance * (evt.velocityX / velocityModifier));
				
				Globals.jScrollApi.scrollToX(displacement, false);
				
			})
			
			// Vertical drag scroll (browser scroll bar).
			.on('panup pandown', (evt) => {
				
				const velocityModifier = 10,
				      displacement     = $(window).scrollTop() - (evt.distance * (evt.velocityY / velocityModifier));
				
				$(window).scrollTop(displacement);
				
			})
		
	},
	
	/**
	 * Init horizontal scroll
	 */
	initHorizontalDragScroll() {
		
		let self            = this,
		    tooltipsTimeout = null;
		
		$(window).on('resize', () => {
			self.addHorizontalDragScroll('stock_central_nav', false);
			self.addHorizontalDragScroll('filters_container', false);
		});
		
		$('.nav-with-scroll-effect').on('scroll', (evt) => {
			
			self.addHorizontalDragScroll($(evt.target).attr('id'), true);
			Tooltip.destroyTooltips();
			
			if (tooltipsTimeout !== null) {
				clearTimeout(tooltipsTimeout);
				tooltipsTimeout = null;
			}
			
			tooltipsTimeout = setTimeout( () => {
				Tooltip.addTooltips();
			}, 1000);
			
		});
		
		dragscroll.reset();
		
	},
	
	/**
	 * Add horizontal scroll effect to menu views
	 */
	addHorizontalDragScroll(elementId, checkEnhanced) {
		
		let $nav                  = document.getElementById(elementId),
		    $overflowOpacityRight = $('#scroll-' + elementId + ' .overflow-opacity-effect-right'),
		    $overflowOpacityLeft  = $('#scroll-' + elementId + ' .overflow-opacity-effect-left'),
		    $leftMax              = $nav ? $nav.scrollWidth : 0,
		    $left                 = $nav ? $nav.scrollLeft : 0,
		    $diff                 = $leftMax - $left;
		
		if ( checkEnhanced ) {
			$('.enhanced').select2('close');
		}
		
		if ($diff === $('#' + elementId).outerWidth()) {
			$overflowOpacityRight.hide();
		}
		else {
			$overflowOpacityRight.show();
		}
		
		if ($left === 0) {
			$overflowOpacityLeft.hide();
		}
		else {
			$overflowOpacityLeft.show();
		}
		
		$('#' + elementId).css('cursor', $overflowOpacityLeft.is(':visible') || $overflowOpacityRight.is(':visible') ? 'grab' : 'auto');
		
	},
	
}

module.exports = DragScroll;
