/* =======================================
   NOTES FOR ATUM ORDERS
   ======================================= */

import Blocker from '../_blocker';
import Settings from '../../config/_settings';
import Swal, { SweetAlertResult } from 'sweetalert2';

export default class OrderNotes {
	
	$container: JQuery;
	$textarea: JQuery;
	
	constructor(
		private settings: Settings
	) {
		
		this.$container = $('#atum_order_notes');
		this.$textarea = $('textarea#add_atum_order_note');
		
		this.$container
			
			.on('click', 'button.add_note', (evt: JQueryEventObject) => this.addNote(evt) )
			.on('click', 'a.delete_note', (evt: JQueryEventObject) => this.deleteNote(evt) );
		
	}
	
	addNote( evt: JQueryEventObject ) {

		evt.preventDefault();

		const note: string = this.$textarea.val();

		if ( ! note ) {
			return;
		}
		
		Blocker.block(this.$container);

		const data: any = {
			action  : 'atum_order_add_note',
			post_id : $( '#post_ID' ).val(),
			note    : note,
			security: this.settings.get( 'addNoteNonce' ),
		};

		$.post( window[ 'ajaxurl' ], data, ( response: any ) => {

			$( 'ul.atum_order_notes' ).prepend( response );
			Blocker.unblock( this.$container );
			this.$textarea.val( '' );
			this.$container.trigger( 'atum_added_note' );

		} );
		
	}
	
	deleteNote( evt: JQueryEventObject ) {
		
		evt.preventDefault();

		const $note: JQuery = $( evt.currentTarget ).closest( 'li.note' );

		Swal.fire( {
			text             : this.settings.get( 'deleteNote' ),
			icon             : 'warning',
			showCancelButton : true,
			confirmButtonText: this.settings.get( 'continue' ),
			cancelButtonText : this.settings.get( 'cancel' ),
			reverseButtons   : true,
			allowOutsideClick: false,
			preConfirm       : (): Promise<any> => {

				return new Promise( ( resolve: Function, reject: Function ) => {

					Blocker.block( $note );

					const data: any = {
						action  : 'atum_order_delete_note',
						note_id : $note.attr( 'rel' ),
						security: this.settings.get( 'deleteNoteNonce' ),
					};

					$.post( window[ 'ajaxurl' ], data, () => resolve() );

				} );

			},
		} )
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {
				$note.remove();
				this.$container.trigger('atum_removed_note');
			}

		} );
		
	}
	
}