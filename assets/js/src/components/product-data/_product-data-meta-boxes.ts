/* =======================================
   PRODUCT DATA META BOXES
   ======================================= */

import ButtonGroup from '../_button-group';
import EnhancedSelect from '../_enhanced-select';
import Settings from '../../config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';

export default class ProductDataMetaBoxes {
	
	$productDataMetaBox: JQuery;
	
	constructor(
		private settings: Settings
	) {

		this.$productDataMetaBox = $( '#woocommerce-product-data' );

		// Customize enhanced selects.
		new EnhancedSelect();

		// Enable button groups.
		ButtonGroup.doButtonGroups( this.$productDataMetaBox );

		// Do button groups to variations once are loaded by WC.
		this.$productDataMetaBox.on( 'woocommerce_variations_loaded woocommerce_variations_added', () => {
			ButtonGroup.doButtonGroups( this.$productDataMetaBox.find( '.woocommerce_variations' ) );
			this.maybeBlockFields();
		} );

		// Toggle the "Out of Stock Threshold" field visibility.
		$( '#_manage_stock' ).change( ( evt: JQueryEventObject ) => $( '#_out_stock_threshold' ).closest( '.options_group' ).css( 'display', $( evt.currentTarget ).is( ':checked' ) ? 'block' : 'none' ) ).change();
		
		// Run scripts for all the variations at once.
		$( '.product-tab-runner' ).find( '.run-script' ).click( ( evt: JQueryEventObject ) => {

			const $button: JQuery = $( evt.currentTarget ),
			      value: string   = $button.siblings( 'select' ).val();

			Swal.fire( {
				title              : this.settings.get( 'areYouSure' ),
				text               : $button.data( 'confirm' ).replace( '%s', `"${ value }"` ),
				icon               : 'warning',
				showCancelButton   : true,
				confirmButtonText  : this.settings.get( 'continue' ),
				cancelButtonText   : this.settings.get( 'cancel' ),
				reverseButtons     : true,
				showLoaderOnConfirm: true,
				preConfirm         : (): Promise<any> => {

					return new Promise( ( resolve: Function, reject: Function ) => {

						$.ajax( {
							url     : window[ 'ajaxurl' ],
							data    : {
								action   : $button.data( 'action' ),
								security : this.settings.get( 'nonce' ),
								parent_id: $( '#post_ID' ).val(),
								value    : value,
							},
							method  : 'POST',
							dataType: 'json',
							success : ( response: any ) => {

								if ( typeof response !== 'object' || response.success !== true ) {
									Swal.showValidationMessage( response.data );
								}

								resolve( response.data );

							},
						} );

					} );

				},
				allowOutsideClick  : (): boolean => ! Swal.isLoading(),
			} )
			.then( ( result: SweetAlertResult ) => {

				if ( result.isConfirmed ) {

					Swal.fire( {
							icon : 'success',
							title: this.settings.get( 'success' ),
							text : result.value,
						} )
						.then( () => location.reload() );

				}

			} );
			
		});
		
		// Activate the focus for ATUM fields.
		this.$productDataMetaBox
			.on( 'focus select2:opening', '.atum-field :input', ( evt: JQueryEventObject ) => $( evt.target ).siblings( '.input-group-prepend' ).addClass( 'focus' ) )
			.on( 'blur select2:close', '.atum-field :input', ( evt: JQueryEventObject ) => $( evt.target ).siblings( '.input-group-prepend' ).removeClass( 'focus' ) );

		this.maybeBlockFields();
	}

	/**
	 * Block ATUM fields if current product is a translation.
	 */
	maybeBlockFields() {

		if ( typeof this.settings.get( 'lockFields') !== 'undefined' && 'yes' === this.settings.get( 'lockFields') ) {

			$( '.atum-field input' ).each( ( index: number, elem: Element ) => {

				$( elem ).prop( 'readonly', true ).next().after( $( '.wcml_lock_img' ).clone().removeClass( 'wcml_lock_img' ).show() );
			} );
			$( '.atum-field select' ).each( ( index: number, elem: Element ) => {

				$( elem ).prop( 'disabled', true ).next().next().after( $( '.wcml_lock_img' ).clone().removeClass( 'wcml_lock_img' ).show() );
			} );

		}

	}
	
}