/**
 * Atum List Tables
 *
 * @copyright Stock Management Labs ©2019
 *
 * @since 0.0.1
 */

// Only load Babel Polyfill if is not being included by another library
if (!global._babelPolyfill) {
	require('babel-polyfill');
}

window.$ = window.jQuery;

/**
 * Third Party Plugins
 */

import '../vendor/jquery.address.min';               // This is not downloading the sources
import '../vendor/jquery.jscrollpane';               // A fixed version compatible with webpack
import '../vendor/jquery.floatThead';                // A fixed version compatible with webpack
import 'lightgallery.js/dist/js/lightgallery.min';   // From node_modules
import 'dragscroll/dragscroll';                      // From node_modules
import 'hammerjs/hammer.min';                        // From node_modules
import '../vendor/select2';                          // A fixed version compatible with webpack
//import '../vendor/sweetalert2'                     // Is not working within our webpack configuration
import 'lodash/lodash.min';                          // From node_modules
import 'moment/min/moment.min';                      // From node_modules
import '../vendor/bootstrap-datetimepicker';         // A fixed version compatible with webpack
import '../vendor/jquery.easytree.min';              // This has no package available for npm
import '../vendor/bootstrap3-custom.min';            // TODO: USE BOOTSTRAP 4


/**
 * Utils
 */

//require('./utils/_responsive').init();
require('./utils/_utils');
require('./utils/_plugins');


/**
 * Components
 */

import Settings from './config/_settings';
import Globals from './components/list-table/_globals';
import ListTable from './components/list-table/_list-table';
import Router from './components/list-table/_router';
import ScrollBar from './components/list-table/_scroll-bar';
import DragScroll from './components/list-table/_drag-scroll';
import SearchByColumn from './components/list-table/_search-by-column';
import ColumnGroups from './components/list-table/_column-groups';
import StickyColumns from './components/list-table/_sticky-columns';
import StickyHeader from './components/list-table/_sticky-header';
import Filters from './components/list-table/_filters';
import EditableCell from './components/list-table/_editable-cell';
import LightBox from './components/_light-box';
import Tooltip from './components/_tooltip';
import TableButtons from './components/list-table/_table-buttons';
import SalesLastDays from './components/list-table/_sales-last-days';
import BulkActions from './components/list-table/_bulk-actions';
import LocationsTree from './components/list-table/_locations-tree';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	// Get the settings from localized var.
	Settings.init('atumListVars', {
		ajaxFilter    : 'yes',
		view          : 'all_stock',
		order         : 'desc',
		orderby       : 'date',
		paged         : 1,
		searchDropdown: 'no',
	});
	
	// Initialize components.
	Globals.init();
	ListTable.init();
	Router.init();
	ScrollBar.init();
	DragScroll.init();
	SearchByColumn.init();
	ColumnGroups.init();
	StickyColumns.init();
	StickyHeader.init();
	Filters.init();
	EditableCell.init();
	LightBox.init();
	Tooltip.init();
	TableButtons.init();
	SalesLastDays.init();
	BulkActions.init();
	LocationsTree.init();
	
});