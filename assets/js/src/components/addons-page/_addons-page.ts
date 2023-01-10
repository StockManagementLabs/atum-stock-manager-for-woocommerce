/*
   ┌──────────────┐
   │              │
   │ ADD-ONS PAGE │
   │              │
   └──────────────┘
*/

import Settings from '../../config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';

export default class AddonsPage {
	
	$addonsList: JQuery;
	
	constructor(
		private settings: Settings
	) {

		this.$addonsList = $( '.atum-addons' );
		this.bindEvents();
		
	}

	/**
	 * Bind Events
	 */
	bindEvents() {

		this.$addonsList

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
	
}