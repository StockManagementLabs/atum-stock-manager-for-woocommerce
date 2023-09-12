/*
   ┌──────────────┐
   │              │
   │ ADD-ONS PAGE │
   │              │
   └──────────────┘
*/

import Blocker from '../_blocker';
import dragscroll from '../../../vendor/dragscroll';
import Settings from '../../config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';
import Trials from './_trials';

export default class AddonsPage {
	
	$addonsPage: JQuery;
	$noResults: JQuery;
	
	constructor(
		private settings: Settings,
		private trials: Trials
	) {

		this.$addonsPage = $( '.atum-addons' );
		this.$noResults = this.$addonsPage.find( '.no-results' );

		this.prepareMenu();
		this.initHorizontalDragScroll();
		this.bindEvents();
		
	}

	/**
	 * Prepare the top menu items
	 */
	prepareMenu() {

		const $addonsMenu: JQuery = this.$addonsPage.find( '.nav-container-box' );

		$addonsMenu.find( 'li' ).each( ( index: number, elem: Element ) => {

			const $elem: JQuery  = $( elem ),
			      status: string = $elem.data( 'status' );

			if ( 'all' === status ) {
				return;
			}

			if ( ! this.$addonsPage.find( `.atum-addon.${ status }` ).length && ! this.$addonsPage.find( `.atum-addon .actions.${ status }` ).length ) {
				$elem.hide();
			}

		} );

		$addonsMenu.removeAttr( 'style' );

	}

	/**
	 * Bind Events
	 */
	bindEvents() {

		this.$addonsPage

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

					this.$addonsPage.find( '.atum-addon' ).each( ( index: number, elem: Element ) => {
						const $addon: JQuery = $( elem );
						$addon.toggle( 'all' === status || $addon.hasClass( status ) || $addon.find( '.actions' ).hasClass( status ) );
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

					if ( $button.hasClass( 'install-atum-addon' ) ) {
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
			.on( 'click', '.alert .refresh-status', ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				const $addon: JQuery = $( evt.currentTarget ).closest( '.atum-addon' );

				$.ajax( {
					url       : window[ 'ajaxurl' ],
					method    : 'POST',
					dataType  : 'json',
					data      : {
						action  : 'atum_refresh_license',
						security: this.settings.get( 'nonce' ),
						addon   : $addon.data( 'addon' ),
					},
					beforeSend: () => Blocker.block( $addon ),
					success   : ( response: any ) => {

						if ( response.success === true ) {
							location.reload();
						}
						else {
							Blocker.unblock( $addon );
							this.showErrorAlert( response.data );
						}

					}

				});

			} )

			// Search addons.
			.on( 'keyup paste search', '#addons-search', ( evt: JQueryEventObject ) => {

				const $input: JQuery  = $( evt.currentTarget ),
				      term: string    = $input.val().toLowerCase(),
				      $addons: JQuery = this.$addonsPage.find( '.atum-addon' );

				this.$noResults.find( '.no-results__term' ).text( term );

				if ( ! term ) {
					this.$noResults.hide();
					this.$addonsPage.find( '.nav-container-box .all' ).click();
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

					this.$noResults.toggle( numHidden >= $addons.length );

				}

			} )

			// Expand/Collapse sidebar.
			.on( 'click', '.atum-addons-sidebar__toggle', ( evt: JQueryEventObject ) => {

				evt.preventDefault();

				const $link: JQuery = $( evt.currentTarget );

				$link.closest( '.atum-addons__sidebar' ).toggleClass( 'collapsed' )
					.closest( '.atum-addons__wrap' ).toggleClass( 'with-collapsed' );

				const $linkText: JQuery = $link.find( 'span' );

				if ( $linkText.text().trim() === this.settings.get( 'show' ) ) {
					$linkText.text( this.settings.get( 'hide' ) );
				}
				else {
					$linkText.text( this.settings.get( 'show' ) );
				}

			} )

			// Toggle between list and grid views.
			.on( 'click', '.atum-addons__nav-buttons .btn', ( evt: JQueryEventObject ) => {

				const $button: JQuery     = $( evt.currentTarget ),
				      $addonsList: JQuery = $( '#atum-addons-list' );

				$button.add( $button.siblings() ).toggleClass( 'btn-primary btn-outline-primary' );

				if ( $button.hasClass( 'grid-view' ) ) {
					$addonsList.addClass( 'atum-addons__grid-view' );
				}
				else {
					$addonsList.removeClass( 'atum-addons__grid-view' );
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
			beforeSend: () => this.beforeAjax( $button ),
			success: ( response: any ) => {

				if ( true === response.success ) {

					this.installAddon( addon, key )
						.then( ( message: string ) => this.showSuccessAlert( message ) )
						.catch( ( error: string ) => this.showErrorAlert( error ) )
						.finally( () => this.afterAjax( $button ) );

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
	 *
	 * @return {Promise<string>}
	 */
	installAddon( addon: string, key: string ): Promise<string> {

		return new Promise( ( resolve: Function, reject: Function ) => {

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
						resolve( response.data );
					}
					else {
						reject( response.data );
					}

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
					preConfirm         : (): Promise<void> => {

						return this.installAddon( addon, key )
							.then( ( message: string ) => this.showSuccessAlert( message ) )
							.catch( ( error: string ) => Swal.showValidationMessage( `<span>${ error }</span>` ) );

					},
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
					preConfirm         : (): Promise<void> => {

						return this.trials.extendTrial( addon, key, true, ( response: any ) => {
							this.licenseChangeResponse( response.success, response.data, addon, key, true );
						} );

					},
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
	showSuccessAlert( message: string, title?: string, callback?: Function ) {

		if ( ! title ) {
			title = this.settings.get( 'success' );
		}

		Swal.fire( {
			title            : title,
			html             : message,
			icon             : 'success',
			confirmButtonText: this.settings.get( 'ok' ),
		} )
		.then( () => callback ? callback() : location.reload() );

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
		$button.siblings( ':input' ).prop( 'disabled', true );

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
		$button.siblings( ':input' ).prop( 'disabled', false );

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

		$navScrollContainers.css( 'visibility', 'visible' ).off( 'scroll.atum' ).on( 'scroll.atum', ( evt: JQueryEventObject ) => {
			this.addHorizontalDragScroll( $( evt.currentTarget ) );
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
	addHorizontalDragScroll( $nav: JQuery ) {

		if ( ! $nav.length ) {
			return;
		}

		const $overflowOpacityRight: JQuery = $nav.find( '.overflow-opacity-effect-right' ),
		      $overflowOpacityLeft: JQuery  = $nav.find( '.overflow-opacity-effect-left' );

		const navEl: Element = $nav.get( 0 );

		// Show/hide the right opacity element.
		$overflowOpacityRight.toggle( ! this.navIsRight( navEl ) );

		// Show/hide the left opacity element.
		$overflowOpacityLeft.toggle( ! this.navIsLeft( navEl ) );
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