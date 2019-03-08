/* =======================================
   SCROLL BAR FOR LIST TABLES
   ======================================= */

import Globals from './_globals';
import { Utils } from '../../utils/_utils';

export default class ScrollBar {
	
	globals: Globals;
	
	constructor(globalsObj: Globals) {
		
		this.globals = globalsObj;
		
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
		let $tableWrapper: any    = $('.atum-table-wrapper'),
		    scrollOpts: any       = {
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
			
			this.globals.$scrollPane.on('jsp-scroll-x', (evt: any, scrollPositionX: number, isAtLeft: boolean, isAtRight: boolean) => {
				this.globals.$atumList.trigger('atum-scroll-bar-scroll-x', [evt, scrollPositionX, isAtLeft, isAtRight]);
			});
			
			let $jspContainer: JQuery = this.globals.$atumList.find('.jspContainer'),
			    $jsPane: JQuery       = this.globals.$atumList.find('.jspPane');
			
			$jspContainer.height( $jsPane.height() );
			
			this.globals.$atumList.on('click', '.has-child', () => {
				
				setTimeout( () => $jspContainer.height( $jsPane.height() ), 500);
				
			});
			
			this.globals.$atumList.trigger('atum-scroll-bar-loaded');
			
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
