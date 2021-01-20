/* =======================================
   SCROLL BAR FOR LIST TABLES
   ======================================= */

/**
 * Third party plugins
 */
import '../../../vendor/jquery.jscrollpane';               // A fixed version compatible with webpack

import Globals from './_globals';
import Utils from '../../utils/_utils';
import WPHooks from '../../interfaces/wp.hooks';

export default class ScrollBar {

	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private globals: Globals
	) {
		
		// Init the table scrollbar.
		this.addScrollBar();
		
		this.bindEvents();
		this.addHooks();
		
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// Reinitialise on window resizing.
		$( window ).resize( () => {

			if ( this.globals.$scrollPane && this.globals.$scrollPane.length && typeof this.globals.$scrollPane.data( 'jsp' ) !== 'undefined' ) {
				this.globals.jScrollApi.reinitialise();
			}

		} ).resize();

	}

	/**
	 * Add hooks
	 */
	addHooks() {

		// Reload the scroll bar after the List Table is updated.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => {
			if ( this.globals.$collapsedGroups === null ) {
				this.reloadScrollbar();
			}
		} );

		// Reload the scroll bar when the column groups are restored.
		this.wpHooks.addAction( 'atum_columnGroups_groupsRestored', 'atum', () => this.reloadScrollbar() );

	}
	
	/**
	 * Add the horizontal scroll bar to the table
	 */
	addScrollBar() {

		if ( this.globals.jScrollApi !== null ) {
			this.reloadScrollbar();

			return;
		}

		// Wait until the thumbs are loaded and enable JScrollpane.
		let $tableWrapper: any = $( '.atum-table-wrapper' ),
		    scrollOpts: any    = {
			    horizontalGutter: 0,
			    verticalGutter  : 0,
		    };
		
		// Reset the sticky cols position and visibility to avoid flickering.
		if ( this.globals.$stickyCols !== null ) {
			this.globals.$stickyCols.hide().css( 'left', 0 );
		}

		Utils.imagesLoaded( $tableWrapper ).then( () => {

			this.globals.$scrollPane = $tableWrapper.jScrollPane( scrollOpts );
			this.globals.jScrollApi = this.globals.$scrollPane.data( 'jsp' );

			this.globals.$scrollPane.on( 'jsp-scroll-x', ( evt: any, scrollPositionX: number, isAtLeft: boolean, isAtRight: boolean ) => {
				this.globals.$atumList.trigger( 'atum-scroll-bar-scroll-x', [ evt, scrollPositionX, isAtLeft, isAtRight ] );
			} );

			const $jspContainer: JQuery = this.globals.$atumList.find( '.jspContainer' ),
			      $jsPane: JQuery       = this.globals.$atumList.find( '.jspPane' );

			$jspContainer.height( $jsPane.height() );

			this.globals.$atumList.on( 'click', '.has-child', () => setTimeout( () => $jspContainer.height( $jsPane.height() ), 500 ) );

			this.wpHooks.doAction( 'atum_scrollBar_loaded' );
			
		});
		
	}
	
	/**
	 * Reload the scrollbar
	 */
	reloadScrollbar() {

		let positionX: number = 0;

		if ( this.globals.jScrollApi !== null ) {
			positionX = this.globals.jScrollApi.getContentPositionX();
			this.globals.jScrollApi.destroy();
			this.globals.jScrollApi = null;
		}

		this.addScrollBar();

		if ( positionX > 0 ) {
			// Wait until the scroll bar is re-added to restore the position.
			this.wpHooks.addAction( 'atum_scrollBar_loaded', 'atum', () => this.globals.jScrollApi.scrollToX( positionX ) );
		}
		
	}
	
}
