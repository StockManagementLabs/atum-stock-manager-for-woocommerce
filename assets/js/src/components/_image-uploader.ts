/* =======================================
   IMAGE UPLOADER
   ======================================= */

// It uses the WP media component.

const ImageUploader = {
	
	doImageUploaders() {

		// The WP media must be enqueued.
		if ( window['wp'].hasOwnProperty( 'media' ) ) {

			$( 'body' ).on( 'click', '.atum-image-uploader', ( evt: JQueryEventObject ) => {

				const $button: JQuery = $( evt.currentTarget );

				const modalOptions: any = {
					library : {
						type: 'image',
					},
					multiple: false
				};

				if ( $button.data( 'modal-title' ) ) {
					modalOptions.title = $button.data( 'modal-title' );
				}

				if ( $button.data( 'modal-button' ) ) {
					modalOptions.button = {
						text: $button.data( 'modal-button' )
					}
				}

				const uploader: any = window[ 'wp' ].media( modalOptions )
					.on( 'select', function () {
						const attachment: any = uploader.state().get( 'selection' ).first().toJSON();
						$button.siblings( 'input:hidden' ).val( attachment.id );

						if ( $button.siblings( 'img' ).length ) {
							$button.siblings( 'img' ).attr( 'src', attachment.url );
						}
						else {
							$button.after( `<img class="atum-image-uploader__preview" src="${ attachment.url }">` );
						}

					} )
					.open();

			} );

		}
		
	},
	
};

export default ImageUploader;