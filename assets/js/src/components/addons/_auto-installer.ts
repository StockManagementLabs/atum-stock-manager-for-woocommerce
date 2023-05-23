/*
 ┌─────────────────────────┐
 │                         │
 │  ADDONS AUTO-INSTALLER  │
 │                         │
 └─────────────────────────┘
 */

import AddonsPage from './_addons-page';
import Settings from '../../config/_settings';
import Swal from 'sweetalert2';
import Tooltip from '../_tooltip';
import Utils from '../../utils/_utils';

interface tokenParams {
	key: string[];
	addon: string[];
}

export default class AutoInstaller {

	autoInstallParams: tokenParams;

	constructor(
		private settings: Settings,
		private addonsPage: AddonsPage,
		private tooltip: Tooltip,
	) {

		const autoInstallData: string = this.settings.get( 'autoInstallData' );

		if ( autoInstallData ) {
			this.autoInstallParams = Utils.getQueryParams( autoInstallData );
			this.run();
		}

	}

	/**
	 * Run the auto-installer when detected.
	 */
	run() {

		let modalContent: string = `
			<div class="atum-modal-content">
				<div class="note">${ this.settings.get( 'toBeInstalled' ) }</div>
				<hr>
				<ul class="auto-install-list">
		`;

		this.autoInstallParams.key.forEach( ( value: string, index: number ) => {
			modalContent += `
				<li data-addon="${ this.autoInstallParams.addon[ index ] }">
					<i class="atum-icon atmi-cloud-download atum-tooltip"></i>
					${ this.autoInstallParams.addon[ index ] } <code>(${ this.settings.get( 'key' ) }: ${ value })</code>
				</li>
			`;
		} );

		modalContent += '</ul></div>';

		Swal.fire( {
			title              : this.settings.get( 'autoInstaller' ),
			html               : modalContent,
			customClass        : {
				container: 'atum-modal',
				popup    : 'auto-installer-modal',
			},
			confirmButtonText  : this.settings.get( 'install' ),
			cancelButtonText   : this.settings.get( 'cancel' ),
			showCancelButton   : true,
			showCloseButton    : true,
			reverseButtons     : true,
			allowOutsideClick  : () => !Swal.isLoading(),
			allowEscapeKey     : () => !Swal.isLoading(),
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<void> => {

				return new Promise( async ( resolve: Function, reject: Function ) => {

					const promises: Promise<string>[] = [];

					for ( const index in this.autoInstallParams.key ) {
						promises.push( this.maybeInstallAddon( this.autoInstallParams.addon[ index ], this.autoInstallParams.key[ index ] ) );
					}

					Promise.all( promises )
						.then( () => {

							this.addonsPage.showSuccessAlert(
								this.settings.get(  promises.length > 1 ? 'allAddonsInstalled' : 'addonInstalled' ),
								'',
								() => { location.href = this.settings.get( 'addonsPageUrl' ) }
							);
							resolve();

						} )
						.catch( () => reject() )

				} );

			},
		} );

	}

	/**
	 * Validate license before installing an addon
	 *
	 * @param {string} addon
	 * @param {string} key
	 *
	 * @return {Promise<string>}
	 */
	maybeInstallAddon( addon: string, key: string ): Promise<string> {

		return new Promise( ( resolve: Function, reject: Function ) => {

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
				beforeSend: () => this.setAddonStatus( addon, 'installing' ),
				success: ( response: any ) => {

					if ( false !== response.success ) {

						this.addonsPage.installAddon( addon, key )
							.then( ( message: string ) => {
								this.setAddonStatus( addon, 'success' );
								resolve( message );
							} )
							.catch( ( error: string ) => {
								this.setAddonStatus( addon, 'error' );
								Swal.showValidationMessage( `<span><strong>${ addon }:</strong> ${ error }</span>` );
								reject( error );
							} );

					}
					else {
						this.setAddonStatus( addon, 'error' );
						Swal.showValidationMessage( `<span><strong>${ addon }:</strong> ${ response.data }</span>` );
						reject( response.data );
					}

				}
			} );

		} );


	}

	/**
	 * Set the status icon for an addon acording to the installation response
	 *
	 * @param {string}                         addon
	 * @param {'success'|'error'|'installing'} status
	 */
	setAddonStatus( addon: string, status: 'success'|'error'|'installing' ) {

		const $icon: JQuery = $( `.auto-install-list [data-addon="${ addon }"] i` ).removeClass( 'atmi-cloud-download atmi-cloud-sync atmi-cloud-check atmi-cloud' ),
		      $ul: JQuery   = $icon.closest( 'ul' );

		this.tooltip.destroyTooltips( $ul );

		switch ( status ) {
			case 'success':
				$icon.addClass( 'color-success atmi-cloud-check' ).attr( 'title', this.settings.get( 'addonInstalled' ) );
				break;

			case 'error':
				$icon.addClass( 'color-danger atmi-cloud' ).attr( 'title', this.settings.get( 'addonNotInstalled' ) );
				break;

			case 'installing':
				$icon.addClass( 'color-primary atmi-cloud-sync' ).attr( 'title', this.settings.get( 'installing' ) );
				break;
		}

		this.tooltip.addTooltips( $ul );

	}

}