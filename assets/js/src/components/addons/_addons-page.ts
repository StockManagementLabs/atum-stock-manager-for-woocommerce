/*
   ┌──────────────┐
   │              │
   │ ADD-ONS PAGE │
   │              │
   └──────────────┘
*/

import dragscroll from '../../../vendor/dragscroll';
import Settings from '../../config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';
import Tooltip from '../_tooltip';
import Utils from '../../utils/_utils';

export default class AddonsPage {
	
	$addonsList: JQuery;
	$noResults: JQuery;
	
	constructor(
		private settings: Settings,
		private tooltip: Tooltip
	) {

		this.$addonsList = $( '.atum-addons' );
		this.$noResults = this.$addonsList.find( '.no-results' );

		this.prepareMenu();
		this.initHorizontalDragScroll();
		this.bindEvents();
		
	}

	/**
	 * Prepare the top menu items
	 */
	prepareMenu() {

		const $addonsMenu: JQuery = this.$addonsList.find( '.nav-container-box' );

		$addonsMenu.find( 'li' ).each( ( index: number, elem: Element ) => {

			const $elem: JQuery  = $( elem ),
			      status: string = $elem.data( 'status' );

			if ( 'all' === status ) {
				return;
			}

			if ( ! this.$addonsList.find( `.atum-addon.${ status }` ).length && ! this.$addonsList.find( `.atum-addon .actions.${ status }` ).length ) {
				$elem.hide();
			}

		} );

		$addonsMenu.removeAttr( 'style' );

	}

	/**
	 * Bind Events
	 */
	bindEvents() {

		this.$addonsList

			// Apply filters
			.on( 'click', '.nav-container-box li', ( evt: JQueryEventObject ) => {

				const $li: JQuery          = $( evt.currentTarget ),
				      $span: JQuery        = $li.find( 'span' ),
				      status: string       = $li.data( 'status' ),
				      $searchInput: JQuery = $( '#addons-search' );

				// Only show the search on the "All" view.
				if ( 'all' === status ) {
					$searchInput.parent().show();
				}
				else {
					$searchInput.val( '' ).parent().removeClass( 'is-searching' ).hide();
				}

				if ( ! $span.hasClass( 'active') ) {

					$li.siblings().find( 'span' ).removeClass( 'active' );
					$span.addClass( 'active' );

					this.$addonsList.find( '.atum-addon' ).each( ( index: number, elem: Element ) => {

						const $addon: JQuery = $( elem );

						if ( 'all' === status || $addon.hasClass( status ) || $addon.find( '.actions' ).hasClass( status ) ) {
							$addon.show();
						}
						else {
							$addon.hide();
						}

					} );

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

					key = $button.closest( '.addon-key' ).find( '.license-key' ).text();

					const isTrial: boolean = $button.closest( '.actions' ).hasClass( 'trial' );

					Swal.fire( {
						title              : this.settings.get( isTrial ? 'trialDeactivation' : 'limitedDeactivations' ),
						html               : this.settings.get( isTrial ? 'trialWillDisable' : 'allowedDeactivations' ),
						icon               : 'warning',
						confirmButtonText  : this.settings.get( 'continue' ),
						cancelButtonText   : this.settings.get( 'cancel' ),
						showCancelButton   : true,
						showLoaderOnConfirm: true,
						preConfirm         : (): Promise<void> => this.requestLicenseChange( $button, key, true ),
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
						this.maybeInstallAddon( $button );
					}
					else {
						this.requestLicenseChange( $button, key );
					}
				}


			})

			// Show the key fields.
			.on( 'click', '.show-key', ( evt: JQueryEventObject ) => {

				evt.preventDefault();
				$( evt.currentTarget ).closest( '.actions' ).children().slideToggle( 'fast' );

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
						security: this.settings.get( 'nonce' ),
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

			// Search addons.
			.on( 'keyup paste search', '#addons-search', ( evt: JQueryEventObject ) => {

				const $input: JQuery  = $( evt.currentTarget ),
				      term: string    = $input.val().toLowerCase(),
				      $addons: JQuery = this.$addonsList.find( '.atum-addon' );

				this.$noResults.find( '.no-results__term' ).text( term );

				if ( ! term ) {
					this.$noResults.hide();
					this.$addonsList.find( '.nav-container-box .all' ).click();
					$addons.show();
					$input.parent().removeClass( 'is-searching' );
				}
				else {

					$input.parent().addClass( 'is-searching' );

					let numHidden: number = 0;

					$addons.each( ( index: number, elem: Element ) => {

						const $addon: JQuery = $( elem );

						if ( $addon.text().toLowerCase().includes( term ) ) {
							$addon.show();
						}
						else {
							$addon.hide();
							numHidden++;
						}

					} );

					if ( numHidden >= $addons.length ) {
						this.$noResults.show();
					}
					else {
						this.$noResults.hide();
					}

				}

			} )


	}

	/**
	 * Validate license before installing an addon
	 *
	 * @param {JQuery} $button
	 */
	maybeInstallAddon( $button: JQuery ) {

		const $addonBlock: JQuery = $button.closest( '.atum-addon' ),
		      addon: string       = $addonBlock.data( 'addon' ),
		      key: string         = $addonBlock.find( '.addon-key input' ).val();

		// First check if it is a trial license.
		$.ajax( {
			url       : window[ 'ajaxurl' ],
			method    : 'POST',
			dataType  : 'json',
			data: {
				action  : 'atum_validate_license',
				security: this.settings.get( 'nonce' ),
				addon   : addon,
				key     : key,
			},
			beforeSend: () => {
				this.beforeAjax( $button );
			},
			success: ( response: any ) => {

				if ( true === response.success ) {
					this.installAddon( addon, key ).then( () => this.afterAjax( $button ) );
				}
				else {
					this.licenseChangeResponse( response.success, response.data, addon, key );
					this.afterAjax( $button );
				}

			}
		} );

	}

	/**
	 * Install an add-on
	 *
	 * @param {string}  addon
	 * @param {string}  key
	 * @param {boolean} isSwal
	 */
	installAddon( addon: string, key: string, isSwal: boolean = false ): Promise<void> {

		return new Promise( ( resolve: Function ) => {

			$.ajax( {
				url       : window[ 'ajaxurl' ],
				method    : 'POST',
				dataType  : 'json',
				data: {
					action  : 'atum_install_addon',
					security: this.settings.get( 'nonce' ),
					addon   : addon,
					key     : key,
				},
				success   : ( response: any ) => {

					if ( response.success === true ) {
						this.showSuccessAlert( response.data );
					}
					else if ( isSwal ) {
						Swal.showValidationMessage( `<span>${ response.data }</span>` );
					}
					else {
						this.showErrorAlert( response.data );
					}

					resolve();

				},
			} );

		} );

	}

	/**
	 * Extend a trial license (if possible)
	 *
	 * @param {string}  addon
	 * @param {string}  key
	 * @param {boolean} isSwal
	 *
	 * @return {Promise<void>}
	 */
	extendTrial( addon: string, key: string, isSwal: boolean = false ): Promise<void> {

		return new Promise( ( resolve: Function ) => {

			$.ajax( {
				url     : window[ 'ajaxurl' ],
				method  : 'POST',
				dataType: 'json',
				data    : {
					action  : 'atum_extend_trial',
					security: this.settings.get( 'nonce' ),
					addon   : addon,
					key     : key,
				},
				success : ( response: any ) => {

					this.licenseChangeResponse( response.success, response.data, addon, key, isSwal );
					resolve();

				},
			} );

		} );

	}
	
	/**
	 * Send the Ajax request to change a license status
	 *
	 * @param {JQuery}  $button
	 * @param {string}  key
	 * @param {boolean} isSwal
	 *
	 * @return {Promise<void>}
	 */
	requestLicenseChange( $button: JQuery, key: string, isSwal: boolean = false ): Promise<void> {

		return new Promise( ( resolve: Function ) => {

			const addon: string = $button.closest( '.atum-addon' ).data( 'addon' );

			$.ajax( {
				url       : window[ 'ajaxurl' ],
				method    : 'POST',
				dataType  : 'json',
				data      : {
					action  : $button.data( 'action' ),
					security: this.settings.get( 'nonce' ),
					addon   : addon,
					key     : key,
				},
				beforeSend: () => this.beforeAjax( $button ),
				success   : ( response: any ) => {

					this.afterAjax( $button );
					this.licenseChangeResponse( response.success, response.data, addon, key, isSwal );
					resolve();

				},
			} );

		} );
		
	}

	/**
	 * Handle the responses of a license change request
	 *
	 * @param {boolean | string} resp
	 * @param {string}           message
	 * @param {string}           addon
	 * @param {string}           key
	 * @param {boolean}          isSwal
	 */
	licenseChangeResponse( resp: boolean|string, message: string, addon: string, key: string, isSwal: boolean = false ) {

		switch ( resp ) {

			case false:
				if ( isSwal ) {
					Swal.showValidationMessage( `<span>${ message }</span>` );
				}
				else {
					this.showErrorAlert( message );
				}
				break;

			case true:
				this.showSuccessAlert( message );
				break;

			case 'activate':
				Swal.fire( {
					title              : this.settings.get( 'activation' ),
					html               : message,
					icon               : 'info',
					showCancelButton   : true,
					showLoaderOnConfirm: true,
					confirmButtonText  : this.settings.get( 'activate' ),
					allowOutsideClick  : false,
					preConfirm         : (): Promise<void> => {

						return new Promise( ( res: Function ) => {

							$.ajax( {
								url     : window[ 'ajaxurl' ],
								method  : 'POST',
								dataType: 'json',
								data: {
									action  : 'atum_activate_license',
									security: this.settings.get( 'nonce' ),
									addon   : addon,
									key     : key,
								},
								success : ( r: any ) => {

									if ( r.success !== true ) {
										Swal.showValidationMessage( `<span>${ r.data }</span>` );
									}

									res();

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

			case 'trial':
				Swal.fire( {
					icon               : 'info',
					title              : this.settings.get( 'trial' ),
					html               : message,
					showCancelButton   : true,
					showCloseButton    : true,
					confirmButtonText  : this.settings.get( 'agree' ),
					cancelButtonText   : this.settings.get( 'cancel' ),
					reverseButtons     : true,
					showLoaderOnConfirm: true,
					preConfirm         : (): Promise<void> => this.installAddon( addon, key, true ),
				} );

				break;

			case 'extend':
				Swal.fire( {
					icon               : 'info',
					title              : this.settings.get( 'trialExpired' ),
					html               : message,
					showCancelButton   : true,
					showCloseButton    : true,
					confirmButtonText  : this.settings.get( 'extend' ),
					cancelButtonText   : this.settings.get( 'cancel' ),
					reverseButtons     : true,
					showLoaderOnConfirm: true,
					preConfirm         : (): Promise<void> => this.extendTrial( addon, key, true ),
				} );

				break;

		}

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