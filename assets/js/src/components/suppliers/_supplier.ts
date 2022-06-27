/* =======================================
   SUPPLIER COMPONENT
   ======================================= */

export default class Supplier {

	constructor() {

		this.bindEvents();
	
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// Set default checkboxes.
		$( '.default-checkbox' ).change( ( evt: JQueryEventObject ) => {

			const $checkbox: JQuery     = $( evt.currentTarget ),
			      $relatedInput: JQuery = $checkbox.closest( '.form-field' ).children( ':input' ).not( $checkbox );

			if ( $checkbox.is( ':checked' ) ) {
				$relatedInput.hide();
			}
			else {
				$relatedInput.show();
			}

		} );


	}

}