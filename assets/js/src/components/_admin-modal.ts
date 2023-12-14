/* =======================================
   ADMIN MODAL
   ======================================= */

import { COLORS } from '../config/_constants';
import Settings from '../config/_settings';
import Swal, { SweetAlertOptions } from 'sweetalert2';

export default class AdminModal {

	defaultSwalOptions: SweetAlertOptions = {
		icon              : 'info',
		confirmButtonColor: COLORS.primary,
		focusConfirm      : false,
		showCloseButton   : true,
	};

	swalConfigs: any = {};
	
	constructor(
		private settings: Settings
	) {

		this.swalConfigs = this.settings.get( 'swal_configs' );

		// Add this component to the global scope so can be accessed by other add-ons.
		if ( ! window.hasOwnProperty( 'atum' ) ) {
			window[ 'atum' ] = {};
		}

		window[ 'atum' ][ 'AdminModal' ] = this;

		this.showModal();
		
	}

	/**
	 * Show the modal
	 */
	async showModal() {

		let swalOpts: SweetAlertOptions = { ...this.defaultSwalOptions };
		const steps: number = Object.keys( this.swalConfigs ).length;

		if ( steps > 1 ) {
			let stepNumbers: string[] = []
			for ( let step = 1; step <= steps; step++) {
				stepNumbers.push( step.toString() );
			}

			swalOpts.progressSteps = stepNumbers;
			swalOpts.showClass = { backdrop: 'swal2-noanimation' };
			swalOpts.hideClass = { backdrop: 'swal2-noanimation' };
		}

		const swalMixin = Swal.mixin( this.defaultSwalOptions );
		let counter: number = 1;

		// By using await, we can queue multiple Swals on the same page.
		for ( const key in this.swalConfigs ) {
			await swalMixin.fire( {
				...{ currentProgressStep: counter }, ...( <SweetAlertOptions>this.swalConfigs[ key ] )
			} );

			this.hideModal( key );
			counter++;
		}

	}

	/**
	 * Hide the modal and save the closed state for the current user
	 *
	 * @param {string} key
	 */
	hideModal( key: string ) {

		$.ajax( {
			url     : window[ 'ajaxurl' ],
			dataType: 'json',
			method  : 'post',
			data    : {
				action  : 'atum_hide_atum_admin_modal',
				security: this.settings.get( 'nonce' ),
				key     : key,
			},
		} );

	}
	
}