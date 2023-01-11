/*
   ┌──────────────┐
   │              │
   │ ADD-ONS PAGE │
   │              │
   └──────────────┘
*/

import Settings from '../../config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';
import dragscroll from '../../../vendor/dragscroll';
import Utils from '../../utils/_utils';
import Tooltip from '../_tooltip';

export default class AddonsPage {
	
	$addonsList: JQuery;
	
	constructor(
		private settings: Settings,
		private tooltip: Tooltip
	) {

		this.$addonsList = $( '.atum-addons' );

		this.initHorizontalDragScroll();
		this.bindEvents();
		
	}

	/**
	 * Bind Events
	 */
	bindEvents() {

		this.$addonsList

			// Apply filters
			.on( 'click', '.nav-container-box li', ( evt: JQueryEventObject ) => {

				const $li: JQuery   = $( evt.currentTarget ),
				      $span: JQuery = $li.find( 'span' ),
					  status: string = $li.data('status');

				if ( ! $span.is( '.active') ) {

					$li.siblings().find( 'span' ).removeClass( 'active' );
					$span.addClass( 'active' );

					this.$addonsList.find('.atum-addon').each( ( index, elem ) => {

						const $addon = $( elem );

						if ( 'all' === status || $addon.hasClass( status ) ) {
							$addon.show();

						}
						else {
							$addon.hide();
						}

					});

				}
			})

			// Do key actions.
			.on( 'click', '.addon-key button', ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				const $button: JQuery = $( evt.currentTarget );
				let key: string;

				if ( $button.hasClass( 'cancel-action' ) ) {
					$button.closest( '.actions' ).children().slideToggle( 'fast' );
				}
				else if ( $button.hasClass( 'remove-license' ) ) {
					key = $button.closest( '.addon-key' ).find('.license-key').text();

					Swal.fire( {
						title            : this.settings.get( 'limitedDeactivations' ),
						html             : this.settings.get( 'allowedDeactivations' ),
						icon             : 'warning',
						confirmButtonText: this.settings.get( 'continue' ),
						cancelButtonText : this.settings.get( 'cancel' ),
						showCancelButton : true,
					} ).then( ( result: SweetAlertResult ) => {

						if ( result.isConfirmed ) {
							this.requestLicenseChange( $button, key );
						}

					} );
				}
				else {

					key = $button.siblings( 'input' ).val()

					// If no key is present, show the error directly
					if ( ! key ) {
						this.showErrorAlert( this.settings.get( 'invalidKey' ) );
						return false;
					}

					if ( $button.hasClass( 'install-addon' ) ) {
						this.installAddon( $button );
					}
					// Ask the user to confirm the deactivation
					else if ( $button.hasClass( 'deactivate-key' ) ) {



					}
					else {
						this.requestLicenseChange( $button, key );
					}
				}


			})


			// Show the key fields.
			.on( 'click', '.show-key', ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				const $button: JQuery     = $( evt.currentTarget );

				$button.closest( '.actions' ).children().slideToggle( 'fast' );
			} )

			// Refresh license for expired keys
			.on( 'click', '.expired .refresh-status', ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				const $link: JQuery = $( evt.currentTarget );

				$.ajax( {
					url       : window[ 'ajaxurl' ],
					method    : 'POST',
					dataType  : 'json',
					data      : {
						action  : 'atum_refresh_license',
						security: this.$addonsList.data( 'nonce' ),
						addon   : $link.closest( '.atum-addon' ).data( 'addon' ),
					},
					beforeSend: () => {
						this.beforeAjax( $link );
					},
					success   : ( response: any ) => {

						this.afterAjax( $link );

						if ( response.success === true ) {
							location.reload();
						}
						else {
							this.showErrorAlert( response.data );
						}

					}

				});

			} )


	}

	/**
	 * Install addon
	 *
	 * @param {JQuery} $button
	 */
	installAddon( $button: JQuery ) {

		const $addonBlock: JQuery = $button.closest( '.atum-addon' );

		$.ajax( {
			url       : window[ 'ajaxurl' ],
			method    : 'POST',
			dataType  : 'json',
			data      : {
				action  : 'atum_install_addon',
				security: this.$addonsList.data( 'nonce' ),
				addon   : $addonBlock.data( 'addon' ),
				slug    : $addonBlock.data( 'addon-slug' ),
				key     : $addonBlock.find( '.addon-key input' ).val(),
			},
			beforeSend: () => {
				this.beforeAjax( $button );
			},
			success   : ( response: any ) => {

				this.afterAjax( $button );

				if ( response.success === true ) {
					this.showSuccessAlert( response.data );
				}
				else {
					this.showErrorAlert( response.data );
				}

			},
		} );
	}
	
	/**
	 * Send the Ajax request to change a license status
	 *
	 * @param {JQuery} $button
	 * @param {string} key
	 */
	requestLicenseChange( $button: JQuery, key: string ) {

		$.ajax( {
			url       : window[ 'ajaxurl' ],
			method    : 'POST',
			dataType  : 'json',
			data      : {
				action  : $button.data( 'action' ),
				security: this.$addonsList.data( 'nonce' ),
				addon   : $button.closest( '.atum-addon' ).data( 'addon' ),
				key     : key,
			},
			beforeSend: () => {
				this.beforeAjax( $button );
			},
			success   : ( response: any ) => {

				this.afterAjax( $button );

				switch ( response.success ) {

					case false:
						this.showErrorAlert( response.data );
						break;

					case true:
						this.showSuccessAlert( response.data );
						break;

					case 'activate':

						Swal.fire( {
							title              : this.settings.get( 'activation' ),
							html               : response.data,
							icon               : 'info',
							showCancelButton   : true,
							showLoaderOnConfirm: true,
							confirmButtonText  : this.settings.get( 'activate' ),
							allowOutsideClick  : false,
							preConfirm         : (): Promise<any> => {

								return new Promise( ( resolve: Function, reject: Function ) => {

									$.ajax( {
										url     : window[ 'ajaxurl' ],
										method  : 'POST',
										dataType: 'json',
										data    : {
											action  : 'atum_activate_license',
											security: this.$addonsList.data( 'nonce' ),
											addon   : $button.closest( '.atum-addon' ).data( 'addon' ),
											key     : key,
										},
										success : ( response: any ) => {

											if ( response.success !== true ) {
												Swal.showValidationMessage( response.data );
											}

											resolve();

										},
									} );

								} );

							},
						} )
						.then( ( result: SweetAlertResult ) => {

							if ( result.isConfirmed ) {
								this.showSuccessAlert( this.settings.get( 'addonActivated' ), this.settings.get( 'activated' ) );
							}

						} );

						break;

				}

			},
		} );
		
	}
	
	/**
	 * Show a success alert
	 *
	 * @param {string} message
	 * @param {string} title
	 */
	showSuccessAlert( message: string, title?: string ) {

		if ( ! title ) {
			title = this.settings.get( 'success' );
		}

		Swal.fire( {
			title            : title,
			html             : message,
			icon             : 'success',
			confirmButtonText: this.settings.get( 'ok' ),
		} )
		.then( () => location.reload() );

	}
	
	/**
	 * Default error message
	 *
	 * @param {string} message
	 */
	showErrorAlert( message: string ) {

		Swal.fire( {
			title            : this.settings.get( 'error' ),
			html             : message,
			icon             : 'error',
			confirmButtonText: this.settings.get( 'ok' ),
		} );

	}
	
	/**
	 * Actions before an ajax request
	 *
	 * @param {JQuery} $button
	 */
	beforeAjax( $button: JQuery ) {

		$button.parent().find( '.button, button' ).prop( 'disabled', true );
		$button.css( 'visibility', 'hidden' ).after( '<div class="atum-loading"></div>' );

	}
	
	/**
	 * Actions after an ajax request
	 *
	 * @param {JQuery} $button
	 */
	afterAjax( $button: JQuery ) {

		$button.siblings( '.atum-loading' ).remove();
		$button.parent().find( '.button, button' ).prop( 'disabled', false );
		$button.css( 'visibility', 'visible' );

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
		if ( this.navIsRight( navEl ) ) {
			$overflowOpacityRight.hide();
		}
		else {
			$overflowOpacityRight.show();
		}

		// Show/hide the left opacity element.
		if ( this.navIsLeft( navEl ) ) {
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
		return navEl.scrollLeft === 0;
	}

	/**
	 * Check whether the nav scroll container has reached the right hand side
	 *
	 * @param {Element} navEl
	 *
	 * @return {boolean}
	 */
	navIsRight( navEl: Element ): boolean {

		// Sometimes the scroll values can have decimals, and it needs to be compensated or the right side won't be reached.
		const compensate: boolean      = ! Number.isInteger( navEl.scrollWidth ) || ! Number.isInteger( navEl.scrollLeft ),
		      scrollDifference: number = Math.ceil( navEl.scrollWidth - navEl.scrollLeft ),
		      navWidth: number         = Math.ceil( parseFloat( $( navEl ).outerWidth().toString() ) );

		if ( ! compensate ) {
			return scrollDifference <= navWidth;
		}
		else if ( scrollDifference > navWidth ) {
			return ( scrollDifference - 1 ) <= navWidth;
		}
		else {
			return true;
		}

	}
	
}