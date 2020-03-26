/* ===================
   MENU POPOVER
   =================== */

import '../../vendor/bootstrap3-custom.min'; // TODO: USE BOOTSTRAP 4 POPOVERS

import { Menu, MenuItem } from '../interfaces/menu.interface';

export default class MenuPopover {
	
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

		this.menu.items.forEach( ( item: MenuItem ) => {
			const icon: string = item.icon ? `<i class="atum-icon ${ item.icon }"></i> `: '';
			$menuHtml.append(`<li>${ icon }<a data-name="${ item.name }" href="${ item.link || '#' }">${ item.label }</a></li>`);
		} );

		( <any>this.$menuButton ).popover( {
			title    : this.menu.title,
			content  : $menuHtml,
			html     : true,
			template : `
					<div class="popover menu-popover" role="tooltip">
						<div class="popover-arrow"></div>
						<h3 class="popover-title"></h3>
						<div class="popover-content"></div>
					</div>`,
			placement: 'top',
			trigger  : 'click',
			container: 'body',
		} );

		// Bind the menu items when shown.
		this.$menuButton.on( 'shown.bs.popover', ( evt: JQueryEventObject ) => {

			evt.preventDefault();
			const $item: JQuery = $( evt.currentTarget );
			this.$menuButton.trigger( 'atum-menu-popover-item-clicked', [ $item.data( 'name' ), $item.attr( 'href' ) ] );

		} );

	}

	/**
	 * Bind the events for the menu popover
	 */
	bindEvents() {

		// Hide any other opened popover before opening a new one.
		// NOTE: we are using the #wpbody-content element instead of the body tag to avoid closing when clicking within popovers.
		$( '#wpbody-content' ).click( ( evt: JQueryEventObject ) => {

			const $target: JQuery = $( evt.target );

			if ( ! $('.menu-popover').length || $target.is( this.$menuButton ) || $target.closest('.menu-popover').length ) {
				return;
			}

			this.destroyPopover();

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
