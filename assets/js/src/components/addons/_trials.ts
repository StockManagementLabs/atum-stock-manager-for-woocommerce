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
			this.extendTrial( $button.data( 'key' ) );
		} );

	}

	/**
	 * Extend a trial license
	 *
	 * @param {string} key
	 */
	extendTrial( key: string ) {

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

				return new Promise( ( resolve: Function ) => {

					$.ajax( {
						url     : window[ 'ajaxurl' ],
						method  : 'post',
						dataType: 'json',
						data    : {
							action  : 'atum_extend_trial',
							security: this.settings.get( 'nonce' ),
							key     : key,
						},
						success : ( response: any ) => {

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

								} )

							}

							resolve();

						},
					} );

				} );

			},
		} );

	}
}