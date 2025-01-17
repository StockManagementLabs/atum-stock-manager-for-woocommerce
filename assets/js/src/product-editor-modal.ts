/**
 * Product Editor Modal
 * A temporary modal to warn the user about ATUM's incompatibility
 *
 * @copyright Stock Management Labs ©2025
 *
 * @since 1.9.38
 */

import { COLORS } from './config/_constants';
import Settings from './config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2-neutral';

class ProductEditorModal {
	
    constructor(
        private settings: Settings,
    ) {

        this.bindEvents();
		
    }

    /**
     * Bind events
     */
    bindEvents() {

        $( 'body' ).on( 'change', '#woocommerce_feature_product_block_editor_enabled', ( evt: JQueryEventObject ) => {

            const $checkbox: JQuery = $( evt.currentTarget );

            // Only show the modal when enabling the WC product editor.
            if ( $checkbox.is( ':checked' ) ) {
                this.showModal();
            }

        } );

    }

    /**
     * Show the modal
     */
    showModal() {

        Swal.fire( {
            icon              : 'warning',
            title             : this.settings.get( 'title' ),
            html              : this.settings.get( 'text' ),
            confirmButtonText : this.settings.get( 'confirm' ),
            showCancelButton  : true,
            cancelButtonText  : this.settings.get( 'cancel' ),
            confirmButtonColor: COLORS.warning,
            cancelButtonColor : COLORS.primary,
            focusConfirm      : false,
            allowEscapeKey    : false,
            allowOutsideClick : false,
            allowEnterKey     : false,
        } )
            .then( ( result: SweetAlertResult ) => {
                if ( result.isDismissed ) {
                    $( '#woocommerce_feature_product_block_editor_enabled' ).prop( 'checked', false );
                }
            } );

    }
	
}

// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {

    // Get the options from the localized var.
    const settings = new Settings( 'atumProductEditorModalVars' );

    new ProductEditorModal( settings );

} );
