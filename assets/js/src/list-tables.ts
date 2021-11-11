/**
 * Atum List Tables
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 0.0.1
 */

/**
 * Third Party Plugins
 */

import '../vendor/jquery.address.min';               // This is not downloading the sources
import '../vendor/jquery.jscrollpane';               // A fixed version compatible with webpack
import '../vendor/select2';                          // A fixed version compatible with webpack


/**
 * Components
 */

import BulkActions from './components/list-table/_bulk-actions';
import DateTimePicker from './components/_date-time-picker';
import DragScroll from './components/list-table/_drag-scroll';
import ColumnGroups from './components/list-table/_column-groups';
import EditableCell from './components/list-table/_editable-cell';
import EnhancedSelect from './components/_enhanced-select';
import Filters from './components/list-table/_filters';
import Globals from './components/list-table/_globals';
import LightBox from './components/_light-box';
import ListTable from './components/list-table/_list-table';
import LocationsTree from './components/list-table/_locations-tree';
import TableCellPopovers from './components/_table-cell-popovers';
import Router from './components/list-table/_router';
import SalesLastDays from './components/list-table/_sales-last-days';
import ScrollBar from './components/list-table/_scroll-bar';
import SearchInColumn from './components/list-table/_search-in-column';
import Settings from './config/_settings';
import StickyColumns from './components/list-table/_sticky-columns';
import StickyHeader from './components/list-table/_sticky-header';
import TableButtons from './components/list-table/_table-buttons';
import Tooltip from './components/_tooltip';
import RowActions from './components/list-table/_row-actions';
import Utils from './utils/_utils';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {
	
	// Get the settings from localized var.
	const settings = new Settings( 'atumListVars', {
		ajaxFilter: 'yes',
		view      : 'all_stock',
		order     : 'desc',
		orderby   : 'date',
		paged     : 1,
	} );
	
	// Set globals.
	const globals = new Globals( settings );
	
	// Initialize components with dependency injection.
	const enhancedSelect = new EnhancedSelect();
	const tooltip = new Tooltip();
	const stickyCols = new StickyColumns( settings, globals );
	const listTable = new ListTable( settings, globals, tooltip, enhancedSelect, stickyCols );
	const router = new Router( settings, globals, listTable );
	const stickyHeader = new StickyHeader( settings, globals, stickyCols, tooltip );
	const dateTimePicker = new DateTimePicker( settings );
	const popover = new TableCellPopovers( settings, dateTimePicker, enhancedSelect );

	if ( ! Utils.checkRTL( 'isRTL' ) ) {
		new ScrollBar( globals );
	}

	new DragScroll( globals, tooltip, popover );
	new SearchInColumn( settings, globals );
	new ColumnGroups( globals, stickyHeader );
	new Filters( settings, globals, listTable, router, tooltip, dateTimePicker );
	new EditableCell( settings, globals, popover, listTable );
	new LightBox();
	new TableButtons( globals, tooltip, stickyCols, stickyHeader );
	new SalesLastDays( globals, router, enhancedSelect );
	new BulkActions( settings, globals, listTable );
	new LocationsTree( settings, globals, tooltip );
	new RowActions( settings, globals );
	
});