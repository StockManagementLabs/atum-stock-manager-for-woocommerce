/* =======================================
   ACTIVE ROW LIST TABLES
   ======================================= */

export const ActiveRow = {
	
	/**
	 * Add/remove row active class when checkbox is clicked.
	 *
	 * @param jQuery $listTable
	 */
	addActiveClassRow($listTable: JQuery) {
		
		$listTable.find('tbody .check-column input:checkbox').change( (evt: any) => {
			
			let $checkbox: JQuery    = $(evt.currentTarget),
			    id: number           = $checkbox.val(),
			    $checkboxRow: JQuery = $listTable.find(`[data-id="${ id }"], #post-${ id }`);
			
			if ($checkbox.is(':checked')) {
				$checkboxRow.addClass('active-row');
			}
			else {
				$checkboxRow.removeClass('active-row');
			}
			
		});
		
		// Selet all rows checkbox.
		$('#cb-select-all-1').change( () => {
			
			$listTable.find('tbody tr').each( (index: number, elem: any) => {
				
				let $elem: JQuery = $(elem);
				
				if ($elem.find('.check-column input[type=checkbox]').is(':checked')) {
					$elem.addClass('active-row');
				}
				else {
					$elem.removeClass('active-row');
				}
				
			});
			
		});
		
	}
	
}