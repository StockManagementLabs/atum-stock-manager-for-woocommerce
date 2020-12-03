/* =======================================
   STUPID TABLE
   ======================================= */

const StupidTable = {
	
	init($table: JQuery) {
		(<any>$table).stupidtable();
		$table.on( 'aftertablesort', this.addArrows );
	},
	
	addArrows( evt: Event, data: any ) {
		
		let $th: JQuery   = $(evt.currentTarget).find('th'),
		    arrow: string = data.direction === 'asc' ? '&uarr;' : '&darr;',
		    index: number = data.column;
		
		$th.find( '.atum-arrow' ).remove();
		$th.eq( index ).append( '<span class="atum-arrow">' + arrow + '</span>' );
	}
	
}

export default StupidTable;