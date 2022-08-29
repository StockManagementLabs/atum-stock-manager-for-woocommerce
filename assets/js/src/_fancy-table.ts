/* =============================================================
   FANCY TABLE (https://github.com/myspace-nu/jquery.fancyTable)
   ============================================================= */

import 'jquery.fancytable/dist/fancyTable.min';   // From node_modules

interface FTOptions {
	exactMatch?: boolean;
	globalSearch?: boolean;
	globalSearchExcludeColumns?: number[];
	inputPlaceholder?: string;
	inputStyle?: string;
	matchCase?: boolean;
	onInit?: Function;
	onUpdate?: Function;
	pagination?: boolean;
	paginationClass?: string;
	paginationClassActive?: string;
	paginationElement?: string;
	pagClosest?: number;
	perPage?: number;
	searchable?: boolean;
	sortable?: boolean;
	sortColumn?: number;
	sortFunction?: Function;
	sortOrder?: string;
}

const FancyTable = {

	init( $table: JQuery, options: FTOptions = {} ) {

		const defaults: FTOptions = {
			pagination     : true,
			paginationClass: 'btn',
			globalSearch   : true,
			onInit         : ( elem: JQuery ) => {

				// Hide the pagination if there is just one page.
				const $paginationWrapper: JQuery = $( elem ).find( 'tfoot .pag' );

				if ( $paginationWrapper.find( 'a' ).length <= 1 ) {
					$paginationWrapper.hide();
				}

			}
		};

		( <any> $table ).fancyTable( { ...defaults, ...options } );
	}
	
}

export default FancyTable;