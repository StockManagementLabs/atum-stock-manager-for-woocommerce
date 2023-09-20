/*
 ┌──────────────┐
 │              │
 │ POPOVER BASE │
 │              │
 └──────────────┘
 */

import BsPopover from 'bootstrap/js/dist/popover'; // Bootstrap 5 popover

export interface IBsPopoverConfig {
	allowList?: any;
	animation?: boolean;
	boundary?: string | Element;
	container?: string | Element | false;
	content?: string | Element | Function;
	customClass?: string | Function;
	delay?: number;
	fallbackPlacements?: string[];
	html?: boolean;
	offset?: number | string | Function;
	placement?: 'auto' | 'top' | 'bottom' | 'left' | 'right' | Function;
	popperConfig?: any;
	sanitize?: boolean;
	sanitizeFn?: null | Function;
	selector?: string | false;
	template?: string;
	title?: string | Element | Function;
	trigger?: string;
}

export default abstract class PopoverBase {

	abstract popoverClassName: string;

	/**
	 * Bind the popovers
	 *
	 * @param {JQuery} $popoverButtons The buttons where are attached the popovers.
	 */
	abstract bindPopovers( $popoverButtons: JQuery );

	/**
	 * Add the popover to any button
	 *
	 * @param {JQuery}           $button
	 * @param {IBsPopoverConfig} config
	 */
	addPopover( $button: JQuery, config: IBsPopoverConfig ): BsPopover {

		$button.data( 'atum-popover', this );
		return new BsPopover( $button.get( 0 ), config );

	}

	/**
	 * Destroy the popovers
	 *
	 * @param {JQuery}   $popoverButton The button that holds the popover to destroy.
	 * @param {Function} callback       Optional. Any callback function that will be triggered after destroying.
	 */
	destroyPopover( $popoverButton: JQuery, callback?: Function ) {

		if ( $popoverButton.length ) {

			if ( $popoverButton.length > 1 ) {
				// Recursive call.
				$popoverButton.each( ( index: number, elem: Element ) => this.destroyPopover( $( elem ), callback ) );
			}
			else {

				const popover: BsPopover = this.getInstance( $popoverButton );

				if ( popover ) {
					popover.dispose();

					if ( callback ) {
						callback();
					}
				}

			}

		}

	}

	/**
	 * Hides a popover
	 *
	 * @param {JQuery} $popoverButton
	 */
	hidePopover( $popoverButton: JQuery ) {

		if ( ! $popoverButton.length || ! $( '.popover' ).length ) {
			return;
		}
		else if ( $popoverButton.length > 1 ) {
			// Recursive call.
			$popoverButton.each( ( index: number, elem: Element ) => this.hidePopover( $( elem ) ) );
		}
		else {

			// Only hide the popovers added by this component.
			if ( ! this.isValidPopover( $popoverButton ) ) {
				return;
			}

			const popover: BsPopover = this.getInstance( $popoverButton );

			if ( popover ) {
				popover.hide();
			}

		}

	}

	/**
	 * Hide all the other opened popovers when opening the current one
	 *
	 * @param {JQuery} $target
	 */
	maybeHideOtherPopovers( $target: JQuery ) {

		if ( ! $( '.popover' ).length ) {
			return;
		}

		if (
			! $target.length || $target.hasClass( 'select2-selection__choice__remove' ) ||
			$target.closest( '.select2-container--open' ).length ||
			$target.hasClass( this.popoverClassName ) || $target.closest( `.${ this.popoverClassName }` ).length
		) {
			return;
		}

		// Hide all the opened popovers.
		$( `.popover.${ this.popoverClassName }` ).each( ( index: number, elem: Element ) => {

			const $editButton: JQuery = $( `[aria-describedby="${ $( elem ).attr( 'id' ) }"]` );

			if ( ! $editButton.is( $target ) && ! $target.closest( $editButton ).length ) {
				this.hidePopover( $editButton );
			}

		} );

	}

	/**
	 * Get the BsPopover instance for any button
	 *
	 * @param {JQuery} $popoverButton
	 */
	getInstance( $popoverButton: JQuery ): BsPopover {
		return BsPopover.getInstance( $popoverButton.get( 0 ) );
	}

	/**
	 * Check if the popover linked to the passed button belongs to the current component's context
	 *
	 * @param {JQuery} $popoverButton
	 *
	 * @return {boolean}
	 */
	isValidPopover( $popoverButton: JQuery ): boolean {

		const popoverId: string = $popoverButton.attr( 'aria-describedby' );

		if (  ! popoverId || typeof popoverId === 'undefined' ) {
			return false;
		}

		const $popover: JQuery = $( `#${ popoverId }` );

		return $popover.length && $popover.hasClass( this.popoverClassName );

	}
}