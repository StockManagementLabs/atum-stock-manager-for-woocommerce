/* =======================================
   ADMIN MODAL
   ======================================= */

import Settings from '../config/_settings';
import Swal, { SweetAlertOptions } from 'sweetalert2';


export default class AdminModal {

	swalConfig: SweetAlertOptions = {};
	
	constructor(
		private settings: Settings
	) {

		this.swalConfig = this.settings.get( 'swal_config' );
		this.showModal();
		
	}

	/**
	 * Show the modal
	 */
	showModal() {

		Swal.fire( this.swalConfig ).then( () => this.hideModal() );
		
	}

	/**
	 * Hide the modal and save the closed state for the current user
	 */
	hideModal() {

		$.ajax( {
			url     : window[ 'ajaxurl' ],
			dataType: 'json',
			method  : 'post',
			data    : {
				action      : 'atum_hide_atum_modal',
				security    : this.settings.get( 'nonce' ),
				transientKey: this.settings.get( 'key' ),
			},
		} );

	}
	
}