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

import '../vendor/select2';      // A fixed version compatible with webpack


/**
 * Components
 */

import Settings from './config/_settings';
import PostTypeList from './components/list-table/_post-type-list';
import ScrollBar from './components/list-table/_scroll-bar';
import EnhancedSelect from './components/_enhanced-select';
import DragScroll from "./components/list-table/_drag-scroll";
import Popover from './components/_popover';
import Tooltip from './components/_tooltip';
import DateTimePicker from "./components/_date-time-picker";


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	// Get the settings from localized var.
	let settings = new Settings('atumPostTypeListVars');
	let globals = new Globals(settings, {
		$atumTable: $('.wp-list-table'),
		$atumList : $('#posts-filter, .atum-list-wrapper'),
		filterData: {},
	});
	let enhancedSelect = new EnhancedSelect();
    let tooltip = new Tooltip();
    let dateTimePicker = new DateTimePicker(settings);
    let popover = new Popover(settings, dateTimePicker);

	new PostTypeList(settings, globals, enhancedSelect);
    new DragScroll(globals, tooltip, popover);
    new ScrollBar(globals);
	
});