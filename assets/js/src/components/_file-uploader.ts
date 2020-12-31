/* =======================================
   FILE UPLOADER
   ======================================= */

interface WPModalOptions {
	frame?: string; // Accepts [ 'select', 'post', 'image', 'audio', 'video' ]
	title?: string; // Modal title.
	multiple?: boolean; // Enable/disable multiple select.

	library?: {
		order?: string; // 'DESC' or 'ASC'.
		orderby?: string; // [ 'name', 'author', 'date', 'title', 'modified', 'uploadedTo', 'id', 'post__in', 'menuOrder' ]
		type: string; // Mime type. e.g. 'image', 'image/jpeg'.
		search?: string; // Searches the attachment title.
		uploadedTo?: number; // Includes media only uploaded to the specified post (ID). wp.media.view.settings.post.id (for current post ID)
	}

	button?: {
		text: string; // The modal button text.
	}
}

// It uses the WP media component.
export default class FileUploader {

	defaultOptions: WPModalOptions = {
		library : {
			type: 'image',
		},
		multiple: false,
	};

	constructor(
		private options?: WPModalOptions,
		private preview: boolean = true
	) {

		this.doFileUploaders();

	}

	/**
	 * Link the WP Media modals to the ATUM uploader buttons
	 */
	doFileUploaders() {

		// The WP media must be enqueued.
		if ( window['wp'].hasOwnProperty( 'media' ) ) {

			$( 'body' ).on( 'click', '.atum-file-uploader', ( evt: JQueryEventObject ) => {

				const $button: JQuery = $( evt.currentTarget );

				const modalOptions: WPModalOptions = { ...this.defaultOptions, ...this.options };

				// The button's data attributes have preference over the passed options.
				if ( $button.data( 'modal-title' ) ) {
					modalOptions.title = $button.data( 'modal-title' );
				}

				if ( $button.data( 'modal-button' ) ) {
					modalOptions.button = {
						text: $button.data( 'modal-button' )
					}
				}

				const uploader: any = window[ 'wp' ].media( modalOptions )
					.on( 'select', () => {

						const attachment: any = uploader.state().get( 'selection' ).first().toJSON();
						$button.siblings( 'input:hidden' ).val( attachment.id );


						// Show the preview for images only.
						if ( this.preview && modalOptions.library.type.indexOf( 'image' ) > -1 ) {
							if ( $button.siblings( 'img' ).length ) {
								$button.siblings( 'img' ).attr( 'src', attachment.url );
							}
							else {
								$button.after( `<img class="atum-file-uploader__preview" src="${ attachment.url }">` );
							}
						}

					} )
					.open();

			} );

		}
		
	}
	
}