/* ===================
   MENU POPOVER
   =================== */

import BsPopover from 'bootstrap/js/dist/popover'; // Bootstrap 5 popover

import { Menu, MenuItem } from '../interfaces/menu.interface';
import PopoverBase from '../abstracts/_popover-base';
import WPHooks from '../interfaces/wp.hooks';

export default class MenuPopover extends PopoverBase{

	popoverClassName: string = 'menu-popover';
	popoverButtonClassName: string = 'menu-popover-btn';
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private $menuButton: JQuery,
		private menu: Menu
	) {

		super();

		if ( $menuButton.length && typeof menu.items !== 'undefined' && menu.items.length && ! this.getInstance( $menuButton ) ) {
			this.$menuButton.addClass( this.popoverButtonClassName );
			this.bindPopovers();
			this.bindEvents();
		}
		
	}

	/**
	 * Build the menu and add it to the popover
	 */
	bindPopovers() {

		const $menuHtml: JQuery = $( '<ul />' );

		// Prepare the menu's HTML.
		this.menu.items.forEach( ( item: MenuItem ) => {

			const icon: string      = item.icon ? `<i class="atum-icon ${ item.icon }"></i> ` : '',
			      $menuItem: JQuery = $( `<li><a data-name="${ item.name }" href="${ item.link || '#' }">${ icon }${ item.label }</a></li>` );

			if ( item.data ) {

				$.each( item.data, ( key: string, value: string ) => {
					$menuItem.find( 'a' ).data( key, value );
				} );

			}

			$menuHtml.append( $menuItem );

		} );

		// Add the popover to the menu button.
		new BsPopover( this.$menuButton.get(0), {
			title    : this.menu.title || '',
			content  : $( '<div />' ).append( $menuHtml ).get( 0 ), // It supports one element only.
			html     : true,
			customClass: this.popoverClassName,
			placement: this.$menuButton.data( 'bs-placement' ) || 'top',
			trigger  : this.$menuButton.data( 'trigger' ) || 'click',
			container: this.$menuButton.parent().get( 0 ),
		} );

		this.$menuButton

			// Hide any other menu popover opened before opening a new one.
			.on( 'show.bs.popover', ( evt: JQueryEventObject ) => {

				const $shownPopover: JQuery = $( evt.currentTarget );

				$( `.${ this.popoverClassName }` ).each( ( index: number, elem: Element ) => {

					const $currentButton: JQuery = $( `[aria-describedby="${ $( elem ).attr('id') }"]` );

					if ( $currentButton.is( $shownPopover ) ) {
						return;
					}

					this.hidePopover( $currentButton );

				} );

			} )

			// Store the popover ID.
			.on( 'inserted.bs.popover', ( evt: JQueryEventObject ) => {

				const $popover: JQuery = $( `#${ $ ( evt.currentTarget ).attr( 'aria-describedby' ) }` );
				this.wpHooks.doAction( 'atum_menuPopover_inserted', $popover );

			} );

	}

	/**
	 * Bind the events for the menu popover
	 */
	bindEvents() {

		$( 'body' )

			// Hide any other opened popover before opening a new one.
			// NOTE: using the off/on technique to not bind the event once per each instantiated menu.
			.off( 'click.atumMenuPopover', '#wpbody-content' )
			.on( 'click.atumMenuPopover', '#wpbody-content', ( evt: JQueryEventObject ) => {

				if ( ! $( '.popover' ).length ) {
					return;
				}

				const $target: JQuery = $( evt.target );

				if ( ! $target.length || $target.hasClass( this.popoverButtonClassName ) || $target.hasClass( '.popover' ) || $target.closest( '.popover' ).length ) {
					return;
				}

				$( `.popover.${ this.popoverClassName }` ).each( ( index: number, elem: Element ) => {
					this.hidePopover( $( `[aria-describedby="${ $( elem ).attr( 'id' ) }"]` ) );
				} );

			} )

			// Bind the menu items' clicks.
			.off( 'click.atumMenuPopover', `.${ this.popoverClassName } a` )
			.on( 'click.atumMenuPopover', `.${ this.popoverClassName } a`, ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				this.wpHooks.doAction( 'atum_menuPopover_clicked', evt );

				// Once a menu item link is clicked, close it automatically.
				const $popover: JQuery = $( evt.currentTarget ).closest( '.popover' );
				this.hidePopover( $( `[aria-describedby="${ $popover.attr( 'id' ) }"]` ) );

			} );

	}
	
}
