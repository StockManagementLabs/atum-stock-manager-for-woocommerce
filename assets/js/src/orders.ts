/**
 * Atum Orders
 *
 * @copyright Stock Management Labs ©2019
 *
 * @since 1.2.4
 */

window['$'] = window['jQuery'];

/**
 * Third Party Plugins
 */

import '../vendor/bootstrap3-custom.min';       // TODO: USE BOOTSTRAP 4
import 'lodash/lodash.min';                     // From node_modules
import 'moment/min/moment.min';                 // From node_modules
import '../vendor/bootstrap-datetimepicker';    // A fixed version compatible with webpack

/**
 * Components
 */

import AtumOrders from './components/orders/_atum-orders';
import DateTimePicker from './components/_date-time-picker';
import EnhancedSelect from './components/_enhanced-select';
import OrderNotes from './components/orders/_order-notes';
import Settings from './config/_settings';
import Tooltip from './components/_tooltip';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	// Get the settings from localized var.
	let settings = new Settings('atumOrder');
	let tooltip = new Tooltip();
	let dateTimePicker = new DateTimePicker(settings);
	new EnhancedSelect();
	new AtumOrders(settings, tooltip, dateTimePicker);
	new OrderNotes(settings);
	
});