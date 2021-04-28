/* =======================================
   FILE UPLOADER
   ======================================= */

import WPHooks from '../interfaces/wp.hooks';

export interface WPMediaModalOptions {
	frame?: 'select' | 'post' | 'image' | 'audio' | 'video'; // The modal frame type
	title?: string; // Modal title.
	multiple?: boolean; // Enable/disable multiple select.

	library?: {
		order?: 'DESC' | 'ASC';
		orderby?: 'name' | 'author' | 'date' | 'title' | 'modified' | 'uploadedTo' | 'id' | 'post__in' | 'menuOrder';
		type?: string; // Mime type. e.g. 'image', 'image/jpeg'.
		search?: string; // Searches the attachment title.
		uploadedTo?: number; // Includes media only uploaded to the specified post (ID). wp.media.view.settings.post.id (for current post ID)
	}

	button?: {
		text?: string; // The modal button text.
	}
}

// It uses the WP media component.
export default class FileUploader {

	defaultOptions: WPMediaModalOptions = {
		frame: 'select',
		multiple: false,
	};

	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.

	constructor(
		private $buttons: JQuery,
		private options?: WPMediaModalOptions,
		private preview: boolean = false
	) {

		this.doFileUploaders();

	}

	/**
	 * Link the WP Media modals to the ATUM uploader buttons
	 */
	doFileUploaders() {

		// The WP media must be enqueued.
		if ( window['wp'].hasOwnProperty( 'media' ) ) {

			this.$buttons.click( ( evt: JQueryEventObject ) => {

				const $button: JQuery = $( evt.currentTarget );

				const modalOptions: WPMediaModalOptions = { ...this.defaultOptions, ...this.options };

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

						const selection: any = uploader.state().get( 'selection' ),
						      attachment: any = modalOptions.multiple ? selection.toJSON() : selection.first().toJSON(),
						      $input: JQuery  = $button.siblings( 'input:hidden' );

						if ( modalOptions.multiple ) {

							let attachmentIds: number[] = [];
							attachment.forEach( ( att: any ) => {
								attachmentIds.push( att.id );
							} );

							$input.val( JSON.stringify( this.wpHooks.applyFilters( 'atum_fileUploader_inputVal', attachmentIds, $input ) ) );

						}
						else {
							$input.val( this.wpHooks.applyFilters( 'atum_fileUploader_inputVal', attachment.id, $input ) );
						}

						// Show the preview for images only.
						if ( this.preview && ( ! modalOptions.library.type || modalOptions.library.type.indexOf( 'image' ) > -1 ) ) {

							$button.siblings( 'img' ).remove();

							if ( modalOptions.multiple ) {

								attachment.forEach( ( att: any ) => {
									$button.after( `<img class="atum-file-uploader__preview" src="${ att.url }">` );
								} );

							}
							else {
								$button.after( `<img class="atum-file-uploader__preview" src="${ attachment.url }">` );
							}

						}

						this.wpHooks.doAction( 'atum_fileUploader_selected', uploader, $button );

					} )
					.open();

			} );

		}
		
	}
	
}