/* =======================================
   ACTIVE ROW LIST TABLES
   ======================================= */

const ActiveRow = {
	
	/**
	 * Add/remove row active class when checkbox is clicked.
	 *
	 * @param JQuery $listTable
	 */
	addActiveClassRow( $listTable: JQuery ) {

		$listTable.find( 'tbody .check-column input:checkbox' ).change( ( evt: JQueryEventObject ) => this.switchActiveClass( $( evt.currentTarget ) ) );

		// Selet all rows checkbox.
		$( '#cb-select-all-1, #cb-select-all-2' ).change( ( evt: JQueryEventObject ) => {

			const $selectAll = $( evt.currentTarget );

			$listTable.find( 'tbody .check-column input:checkbox' ).each( ( index: number, elem: Element ) => {

				const $checkbox = $( elem );

				if ( $selectAll.is( $checkbox ) ) {
					return;
				}

				this.switchActiveClass( $checkbox );

			} );

		} );

	},

	switchActiveClass( $checkbox: JQuery ) {

		const $checkboxRow: JQuery = $checkbox.closest( 'tr' );

		if ( $checkbox.is( ':checked' ) ) {
			$checkboxRow.addClass( 'active-row' );
		}
		else {
			$checkboxRow.removeClass( 'active-row' );
		}

	},
	
}

export default ActiveRow;