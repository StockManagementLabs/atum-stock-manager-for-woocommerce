/**
 * Atum List Tables
 *
 * @copyright Stock Management Labs ©2019
 *
 * @since 0.0.1
 */

window.$ = window.jQuery;

/**
 * Third Party Plugins
 */



/**
 * Utils
 */

require('./utils/_responsive.js')._init();



/**
 * Components
 */

import ListTable from './components/_list-table';


// Modules that need to execute when the DOM is ready should go here
$(function() {
	
	ListTable.init();
	
});