/* =======================================
   BUTTON GROUPS
   ======================================= */


const ButtonGroup = {

	/**
	 * Bind the button groups
	 *
	 * @param {JQuery} $container
	 */
	doButtonGroups( $container: JQuery ) {

		$container.on( 'click', '.btn-group .btn', ( evt: JQueryEventObject ) => {

			const $button: JQuery = $( evt.currentTarget );

			// Checkboxes.
			if ( $button.find( ':checkbox' ).length ) {
				$button.toggleClass( 'active' );
			}
			// Radio buttons.
			else {
				$button.siblings( '.active' ).removeClass( 'active' );
				$button.addClass( 'active' );
			}

			this.updateChecked( $button.closest( '.btn-group' ) );
			$button.find( 'input' ).change();

			return false; // This avoids from running this twice.

		} );

	},

	/**
	 * Update the checked statuses
	 *
	 * @param {JQuery} $buttonGroup
	 */
	updateChecked( $buttonGroup: JQuery ) {

		$buttonGroup.find( '.btn' ).each( ( index: number, elem: Element ) => {

			const $button: JQuery = $( elem );
			$button.find( 'input' ).prop( 'checked', $button.hasClass( 'active' ) );

		} );

	}

}

export default ButtonGroup;