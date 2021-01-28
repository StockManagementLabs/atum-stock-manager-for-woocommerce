/* =======================================
   BUTTON GROUPS
   ======================================= */


const ButtonGroup = {

	doButtonGroups( $container: JQuery ) {

		// Force change event triggering on Bootstrap radio buttons (for some reason are not being triggered anymore).
		$container.on( 'click', '.btn-group .btn', ( evt: JQueryEventObject ) => {

			const $button: JQuery = $( evt.currentTarget );

			$button.siblings( '.active' ).removeClass( 'active' );
			$button.addClass( 'active' );

			$button.find( 'input' ).change();

		} );

	},

}

export default ButtonGroup;