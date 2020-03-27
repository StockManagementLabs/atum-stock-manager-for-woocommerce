/* ===================
   MENU POPOVER
   =================== */

import '../../vendor/bootstrap3-custom.min'; // TODO: USE BOOTSTRAP 4 POPOVERS

import { Menu, MenuItem } from '../interfaces/menu.interface';

export default class MenuPopover {

	popoverClassName: string = 'menu-popover';
	popoverId: string = '';
	
	constructor(
		private $menuButton: JQuery,
		private menu: Menu
	) {

		if ( $menuButton.length && menu.items.length ) {
			this.buildMenu();
			this.bindEvents();
		}
		
	}

	/**
	 * Build the menu and add it to the popover
	 */
	buildMenu() {

		const $menuHtml: JQuery = $('<ul />');

		// Prepare the menu's HTML.
		this.menu.items.forEach( ( item: MenuItem ) => {
			const icon: string = item.icon ? `<i class="atum-icon ${ item.icon }"></i> `: '';
			$menuHtml.append(`<li>${ icon }<a data-name="${ item.name }" href="${ item.link || '#' }">${ item.label }</a></li>`);
		} );

		// Add the popover to the menu button.
		( <any>this.$menuButton ).popover( {
			title    : this.menu.title,
			content  : $menuHtml,
			html     : true,
			template : `
					<div class="popover ${ this.popoverClassName }" role="tooltip">
						<div class="popover-arrow"></div>
						<h3 class="popover-title"></h3>
						<div class="popover-content"></div>
					</div>`,
			placement: 'top',
			trigger  : 'click',
		} );

		this.$menuButton

			// Hide any other menu popover opened before opening a new one.
			.on( 'show.bs.popover', () => {

				$( `.${ this.popoverClassName }` ).each( ( index: number, elem: Element ) => {
					(<any>$( `[aria-describedby="${ $( elem ).attr('id') }"]` )).popover( 'destroy' );
				} );

			} )

			// Store the popover ID.
			.on( 'shown.bs.popover', () => this.popoverId = $( `.${ this.popoverClassName }` ).attr( 'id' ) );

	}

	/**
	 * Bind the events for the menu popover
	 */
	bindEvents() {

		// Hide any other opened popover before opening a new one.
		// NOTE: we are using the #wpbody-content element instead of the body tag to avoid closing when clicking within popovers.
		$( '#wpbody-content' ).click( ( evt: JQueryEventObject ) => {

			const $target: JQuery = $( evt.target );

			if ( ! this.popoverId || ! $( `#${ this.popoverId }` ).length || $target.is( this.$menuButton ) || $target.closest(`#${ this.popoverId }`).length ) {
				return;
			}

			this.destroyPopover();

		} );

		// Bind the menu items' clicks.
		$( 'body' ).on( 'click', `.${ this.popoverClassName } a`, ( evt: JQueryEventObject ) => {

			evt.preventDefault();
			const $menuItem: JQuery = $( evt.currentTarget );

			// Avoid triggering multiple times as this event is binded once per registered component.
			if ( ! this.popoverId || this.popoverId !== $menuItem.closest( `.${ this.popoverClassName }` ).attr( 'id' ) ) {
				return;
			}

			this.$menuButton.trigger( 'atum-menu-popover-item-clicked', [ this.$menuButton.closest( 'tr' ), $menuItem.data( 'name' ), $menuItem.attr( 'href' ) ] );

		} );

	}

	/**
	 * Destroy the popover
	 */
	destroyPopover() {

		( <any>this.$menuButton ).popover( 'destroy' );
		this.$menuButton.removeAttr( 'data-popover' );

		// Give a small lapse to complete the 'fadeOut' animation before re-building.
		setTimeout( () => this.buildMenu(), 300 );

	}
	
}
