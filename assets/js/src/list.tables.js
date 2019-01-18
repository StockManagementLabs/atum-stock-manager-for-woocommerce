/**
 * Atum List Tables
 *
 * @copyright Stock Management Labs ©2019
 *
 * @since 0.0.1
 */

window.$ = window.jQuery

/**
 * Third Party Plugins
 */



/**
 * Utils
 */

require('./utils/_responsive.js')._init()



/**
 * Components
 */

import ListTable from './components/list-table/_list-table'
import Router from './components/list-table/_router'
import ScrollBar from './components/list-table/_scroll-bar'
import DragScroll from './components/list-table/_drag-scroll'
import SearchByColumn from './components/list-table/_search-by-column'


// Modules that need to execute when the DOM is ready should go here
$(function() {
	
	ListTable.init()
	Router.init()
	ScrollBar.init()
	DragScroll.init()
	SearchByColumn.init()
	
})