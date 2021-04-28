/* =============================
 ATUM ATTACHMENTS META BOX
 =============================== */

import FileUploader, { WPMediaModalOptions } from '../_file-uploader';
import Settings from '../../config/_settings';
import WPHooks from '../../interfaces/wp.hooks';

interface Attachment {
	id: number,
	email: string;
}

export default class FileAttachments {

	$attachmentsList: JQuery;
	$input: JQuery;
	$emailSelector: JQuery = $( '<select>', { class: 'attach-to-email' } );
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.

	constructor(
		private settings: Settings
	) {

		this.$attachmentsList = $( '.atum-attachments-list' );
		this.$input = $( '#atum-attachments' );

		// Add the values to the email notifications dropdown.
		$.each( this.settings.get( 'emailNotifications' ), ( key: string, title: string ) => {
			this.$emailSelector.append( `
				<option value="${ key }">${ title }</option>
			` );
		} );

		this.addHooks();
		this.bindEvents();

		// Use the FileUploader component to show the WP Media modal.
		const uploaderOptions: WPMediaModalOptions = {
			multiple: true,
		};
		new FileUploader( $( '#atum_files' ).find( '.atum-file-uploader' ), uploaderOptions );

	}

	/**
	 * Add hooks
	 */
	addHooks() {

		// Add the selected files to the meta box.
		this.wpHooks.addAction( 'atum_fileUploader_selected', 'atum', ( uploader: any ) => {

			const attachments: any[] = uploader.state().get( 'selection' ).toJSON();

			attachments.forEach( ( attachment: any ) => {

				const $listItem: JQuery = $( '<li>' ).data( 'id', attachment.id ),
				      url: string       = attachment.hasOwnProperty( 'url' ) ? attachment.url : attachment.sizes.full.url;

				$listItem
					.append( `<label>${ this.settings.get( 'attachToEmail' ) }</label>` )
					.append( this.$emailSelector.clone() );

				$listItem.append( `
					<a href="${ url }" target="_blank" title="${ attachment.title }">
						<img src="${ attachment.sizes.medium.url }" alt="${ attachment.title }">
					</a>
				` );

				this.$attachmentsList.append( $listItem );

			} );

			this.updateInput();

		} );

	}

	/**
	 * Bind Events
	 */
	bindEvents() {

		this.$attachmentsList

			// Update the input value after changing any email notifications selector
			.on( 'change', '.attach-to-email', () => this.updateInput() )

			// Delete an attachment.
			.on( 'click', '.delete-attachment', ( evt: JQueryEventObject ) => {

				const $button: JQuery   = $( evt.currentTarget ),
				      tooltipId: string = $button.attr( 'aria-describedby' );

				$button.closest( 'li' ).remove();
				$(`#${ tooltipId }`).remove();
				this.updateInput();

			} );

	}

	/**
	 * Get all the values from the added items and update the input
	 */
	updateInput() {

		const value: Attachment[] = [];

		this.$attachmentsList.find( 'li' ).each( ( index: number, elem: Element ) => {

			const $elem: JQuery = $( elem );

			value.push( {
				id   : $elem.data( 'id' ),
				email: $elem.find( '.attach-to-email' ).val(),
			} );

		} );

		this.$input.val( JSON.stringify( value ) );

	}

}

