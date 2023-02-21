/*
 ┌─────────────────────────┐
 │                         │
 │      TRIAL ADDONS       │
 │                         │
 └─────────────────────────┘
 */

import Settings from '../../config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';

export default class Trials {

	constructor(
		private settings: Settings,
		private successCallback ?: Function
	) {

		this.bindEvents();

	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// Extend trial.
		$( 'body' ).on( 'click', '.extend-atum-trial', ( evt: JQueryEventObject ) => {
			evt.preventDefault();
			evt.stopImmediatePropagation();

			const $button: JQuery = $( evt.currentTarget );
			this.extendTrialConfirmation( $button.closest( '.atum-addon' ).data( 'addon' ), $button.data( 'key' ) );
		} );

	}

	/**
	 * Extend a trial license
	 *
	 * @param {string} addon
	 * @param {string} key
	 */
	extendTrialConfirmation( addon: string, key: string ) {

		Swal.fire( {
			title              : this.settings.get( 'trialExtension' ),
			text               : this.settings.get( 'trialWillExtend' ),
			icon               : 'info',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get( 'extend' ),
			cancelButtonText   : this.settings.get( 'cancel' ),
			showCloseButton    : true,
			allowEnterKey      : false,
			reverseButtons     : true,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<void> => {

				return this.extendTrial( addon, key, true, ( response: any ) => {

					if ( ! response.success ) {
						Swal.showValidationMessage( response.data );
					}
					else {

						Swal.fire( {
							title            : this.settings.get( 'success' ),
							html             : response.data,
							icon             : 'success',
							confirmButtonText: this.settings.get( 'ok' ),
						} )
						.then( ( result: SweetAlertResult ) => {

							if ( this.successCallback && result.isConfirmed ) {
								this.successCallback();
							}

						} );

					}

				} );

			},
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
	extendTrial( addon: string, key: string, isSwal: boolean = false, callback: Function = null ): Promise<void> {

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

					if ( callback ) {
						callback( response );
					}
					resolve();

				},
			} );

		} );

	}
}