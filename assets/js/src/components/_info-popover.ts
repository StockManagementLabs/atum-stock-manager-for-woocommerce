/* ===================
   INFO POPOVER
   =================== */

import PopoverBase, { IBsPopoverConfig } from '../abstracts/_popover-base';

export interface IinfoPopoverConfig extends IBsPopoverConfig{
	ajaxContent?: Function;
}

export default class InfoPopover extends PopoverBase{

	popoverClassName: string = 'atum-info-popover';
	popoverButtonClassName: string = 'atum-info-popover-btn';
	doingAjax: boolean = false;

	constructor(
		public $triggerButton: JQuery,
		private config: IinfoPopoverConfig,
	) {

		super();

		const defaultConfig: IBsPopoverConfig = {
			customClass: this.popoverClassName
		};

		this.config = { ...defaultConfig, ...this.config };

		if ( $triggerButton.length && ! this.getInstance( $triggerButton ) ) {
			this.$triggerButton.addClass( this.popoverButtonClassName );
			this.bindPopovers();
		}
		
	}

	/**
	 * Build the menu and add it to the popover
	 */
	bindPopovers() {

		// Add the popover to the menu button.
		this.addPopover( this.$triggerButton, this.config );

		if ( this.config.ajaxContent ) {

			this.$triggerButton

				.on( 'inserted.bs.popover', () => {

					if ( this.config.ajaxContent && ! this.doingAjax ) {
						this.config.ajaxContent( this );
					}

				} );

		}

		// Auto-hide popvers.
		$( 'body' )
			.off( 'click.atumInfoPopover' ) // Make sure it's bound just once.
			.on( 'click.atumInfoPopover', ( evt: JQueryEventObject ) => this.maybeHideOtherPopovers( $( evt.target ) ) );

	}
	
}
