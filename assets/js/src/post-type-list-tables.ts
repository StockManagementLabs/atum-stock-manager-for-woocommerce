/**
 * Atum Post Type List Tables
 *
 * @copyright Stock Management Labs ©2019
 *
 * @since 1.5.0
 */
import Globals from './components/list-table/_globals';

window['$'] = window['jQuery'];

/**
 * Third Party Plugins
 */

import '../vendor/jquery.jscrollpane';               // A fixed version compatible with webpack
import 'hammerjs/hammer.min';                        // From node_modules
import '../vendor/select2';                          // A fixed version compatible with webpack


/**
 * Components
 */

import Settings from './config/_settings';
import PostTypeList from './components/list-table/post-type-list';
import ScrollBar from './components/list-table/_scroll-bar';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	// Get the settings from localized var.
	let settings = new Settings('atumPostTypeListVars');
	let globals = new Globals(settings, {
		$atumTable: $('.wp-list-table'),
		$atumList : $('#posts-filter'),
		filterData: {},
	});
	
	new PostTypeList(settings, globals);
	new ScrollBar(globals);
	
});