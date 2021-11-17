/* =======================================
 ADD SUPPLIER MODAL
 ======================================= */

import Settings from '../../config/_settings';
import Swal, { SweetAlertOptions } from 'sweetalert2';
import Globals from './_globals';
import ListTable from './_list-table';

export default class AddSupplierModal {

	$modal: JQuery;
	productId : number;

	readonly buttonColor: string = '#69c61d';

	constructor(
		private settings: Settings,
		private globals: Globals,
		private listTable: ListTable,
		private $setMeta: JQuery,
	) {

		this.productId = $setMeta.closest( 'tr' ).data( 'id' );
		this.showModal();
	}

	/**
	 * Show the modal
	 */
	showModal() {

		Swal.fire( {
			title              : `${this.settings.get( 'newSupplier' )}<small>${this.settings.get( 'createNewSupplier' )}</small>`,
			html               : $( '#create-supplier-modal' ).html(),
			customClass        : {
				container: 'atum-modal',
				popup    : 'add-suppliers-modal'
			},
			showCloseButton    : true,
			confirmButtonText : this.settings.get( 'confirmNewSupplier' ),
			confirmButtonColor: this.buttonColor,
			didOpen            : ( modal: HTMLElement ) => {
				this.$modal = $( modal );
			},
			preConfirm         : (): Promise<void> => this.createSupplier(),

		} );
	}

	/**
	 * Create a new Supplier via Ajax
	 */
	createSupplier(): Promise<void> {

		return new Promise( ( resolve: Function ) => {

			const $supplierName: JQuery = this.$modal.find( '#supplier-name' );

			// Validate the fields before submitting the request.
			if ( ! $supplierName.val() ) {

				Swal.showValidationMessage( this.settings.get( 'supplierNameRequired' ) );
				$supplierName.focus().select();
				resolve();
				return;
			}

			$.ajax( {
				url     : window[ 'ajaxurl' ],
				data    : {
					action        : 'atum_create_supplier',
					security      : this.settings.get( 'createSupplierNonce' ),
					supplier_data : this.$modal.find( 'form' ).serialize(),
				},
				method  : 'POST',
				dataType: 'json',
				success : ( response: any ) => {

					if ( response.success === false ) {
						Swal.showValidationMessage( response.data );
					}
					else {

						const successSwalOptions: SweetAlertOptions = {
							      icon              : 'success',
							      title             : response.data.message,
							      html              : `<a target="_blank" class="atum-link" style="font-size: 10px;" href="${ response.data.supplier_link }">${ response.data.text_link }</a>`,
							      confirmButtonText : this.settings.get( 'ok' ),
							      confirmButtonColor: this.buttonColor,
							      showCloseButton   : true,
						      },
						      meta: string                          = this.$setMeta.data( 'meta' ),
						      selectOptions: any                    = this.$setMeta.data( 'selectOptions' );

						let editedCols: any = this.globals.$editInput.val();

						selectOptions[ response.data.supplier_id ] = response.data.supplier_name;

						this.$setMeta.data( 'realValue', response.data.supplier_id );
						this.$setMeta.data( 'selectedValue', response.data.supplier_id );
						this.$setMeta.data( 'selectOptions', selectOptions );

						this.listTable.setCellValue( this.$setMeta, response.data.supplier_name );

						// Initialize the JSON object.
						if ( editedCols ) {
							editedCols = JSON.parse( editedCols );
						}

						editedCols = editedCols || {};

						if ( ! editedCols.hasOwnProperty( this.productId ) ) {
							editedCols[ this.productId ] = {};
						}

						if ( ! editedCols[ this.productId ].hasOwnProperty( meta ) ) {
							editedCols[ this.productId ][ meta ] = {};
						}

						editedCols[ this.productId ][ meta ] = response.data.supplier_id;

						this.globals.$editInput.val( JSON.stringify( editedCols ) );

						Swal.fire( successSwalOptions );

						this.listTable.maybeAddSaveButton();

					}

					resolve();

				},
			} );

		} );

	}


}
