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

		// Don't add pagiation nor search when there aren't enough rows on the table.
		const perPage: number            = options.perPage || 10,
		      paginationAllowed: boolean = $table.find( 'tbody tr' ).length > perPage;

		const defaults: FTOptions = {
			pagination     : paginationAllowed,
			searchable     : paginationAllowed,
			paginationClass: 'btn',
			globalSearch   : true,
		};

		( <any> $table ).fancyTable( { ...defaults, ...options } );
	}

}

export default FancyTable;