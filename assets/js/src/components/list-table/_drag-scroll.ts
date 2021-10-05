/* =======================================
   DRAG-SCROLL FOR LIST TABLES
   ======================================= */

/**
 * Third party plugins
 */
import dragscroll from '../../../vendor/dragscroll';             // A patched fork of dragscroll
import Hammer from 'hammerjs/hammer.min';                        // From node_modules

import Globals from './_globals';
import TableCellPopovers from '../_table-cell-popovers';
import Tooltip from '../_tooltip';
import Utils from '../../utils/_utils';
import WPHooks from '../../interfaces/wp.hooks';

export default class DragScroll {

	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private globals: Globals,
		private tooltip: Tooltip,
		private popover?: TableCellPopovers
	) {
		
		// Add horizontal drag-scroll to table filters.
		this.initHorizontalDragScroll();

		this.addHooks();

	}

	/**
	 * Add hooks
	 */
	addHooks() {

		// Load Hammer for table dragging functionality.
		this.wpHooks.addAction( 'atum_scrollBar_loaded', 'atum', () => this.loadHammer() );

		// Re-add the horizontal drag-scroll when the List Table is updated.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => this.initHorizontalDragScroll() );

	}

	/**
	 * Load Hammer
	 */
	loadHammer() {
		
		// Drag and drop scrolling on desktops.
		const hammertime: any = new Hammer( this.globals.$scrollPane.get( 0 ), {} );
		
		hammertime

			.on( 'panstart', () => {
				// As the popoover is not being repositioned when scrolling horizontally, we have to destroy it.
				if ( this.popover ) {
					this.popover.destroyPopover();
				}
			} )
			
			// Horizontal drag scroll (JScrollPane).
			.on( 'panright panleft', ( evt: any ) => {

				const velocityModifier: number = 10,
				      displacement: number     = this.globals.jScrollApi.getContentPositionX() - ( evt.distance * ( evt.velocityX / velocityModifier ) );

				this.globals.jScrollApi.scrollToX( displacement, false );

			} )
			
			// Vertical drag scroll (browser scroll bar).
			.on( 'panup pandown', ( evt: any ) => {

				const velocityModifier: number = 10,
				      displacement: number     = $( window ).scrollTop() - ( evt.distance * ( evt.velocityY / velocityModifier ) );

				$( window ).scrollTop( displacement );

			} );
		
	}

	/**
	 * Add mouse wheel support to the draggable elements
	 */
	addMouseWheelSupport() {

		$( '.nav-with-scroll-effect' ).off( 'wheel DOMMouseScroll' ).on( 'wheel DOMMouseScroll', ( evt: JQueryEventObject ) => {

			const $nav: JQuery = $( evt.currentTarget );

			// If the navscroll fits in its current container and doesn't need any scroll, just return.
			if (
				$nav.find( '.overflow-opacity-effect-right' ).is( ':hidden' ) &&
				$nav.find( '.overflow-opacity-effect-left' ).is( ':hidden' )
			) {
				return;
			}

			const navEl: Element     = $nav.get( 0 ),
			      originalEvent: any = evt.originalEvent;

			if ( ( originalEvent.wheelDelta || originalEvent.detail ) > 0 ) {
				navEl.scrollLeft -= 60;
			}
			else {
				navEl.scrollLeft += 60;
			}

			return false;

		} );

	}
	
	/**
	 * Init horizontal drag scroll
	 */
	initHorizontalDragScroll() {

		const $navScrollContainers: JQuery = $( '.nav-with-scroll-effect' );

		// As we are running this method multiple times, make sure we unbind the namespaced events before rebinding.
		$( window ).off( 'resize.atum' ).on( 'resize.atum', () => {

			$navScrollContainers.each( ( index: number, elem: Element ) => {
				this.addHorizontalDragScroll( $( elem ) );
			} );

		} ).trigger( 'resize.atum' );

		$( '.tablenav.top' ).find( 'input.btn' ).css( 'visibility', 'visible' );

		$navScrollContainers.css( 'visibility', 'visible' ).off( 'scroll.atum' ).on( 'scroll.atum', ( evt: JQueryEventObject ) => {

			this.addHorizontalDragScroll( $( evt.currentTarget ), true );
			this.tooltip.destroyTooltips();

			Utils.delay( () => this.tooltip.addTooltips(), 1000 );

		} );

		this.addMouseWheelSupport();
		dragscroll.reset();

	}
	
	/**
	 * Add horizontal scroll effect to menu views
	 *
	 * @param {JQuery}  $nav
	 * @param {boolean} checkEnhanced
	 */
	addHorizontalDragScroll( $nav: JQuery, checkEnhanced: boolean = false ) {

		if ( ! $nav.length ) {
			return;
		}

		const $overflowOpacityRight: JQuery = $nav.find( '.overflow-opacity-effect-right' ),
		      $overflowOpacityLeft: JQuery  = $nav.find( '.overflow-opacity-effect-left' );

		if ( checkEnhanced ) {
			( <any> $( '.enhanced' ) ).select2( 'close' );
		}

		const navEl: Element = $nav.get( 0 );

		// Show/hide the right opacity element.
		if ( this.navIsLeft( navEl ) ) {
			$overflowOpacityRight.hide();
		}
		else {
			$overflowOpacityRight.show();
		}

		// Show/hide the right opacity element.
		if ( this.navIsRight( navEl ) ) {
			$overflowOpacityLeft.hide();
		}
		else {
			$overflowOpacityLeft.show();
		}

		$nav.css( 'cursor', $overflowOpacityLeft.is( ':visible' ) || $overflowOpacityRight.is( ':visible' ) ? 'grab' : 'auto' );
		
	}

	/**
	 * Check whether the nav scroll container has reached the left hand side
	 *
	 * @param {Element} navEl
	 *
	 * @return {boolean}
	 */
	navIsLeft( navEl: Element ): boolean {
		return ( navEl.scrollWidth - navEl.scrollLeft ) === parseInt( $( navEl ).outerWidth().toString() );
	}

	/**
	 * Check whether the nav scroll container has reached the right hand side
	 *
	 * @param {Element} navEl
	 *
	 * @return {boolean}
	 */
	navIsRight( navEl: Element ): boolean {
		return navEl.scrollLeft === 0;
	}
	
}
