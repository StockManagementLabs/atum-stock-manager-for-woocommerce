/**
 * Atum Orders
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.2.4
 */

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
	
	window['$'] = $; // Avoid conflicts.
	
	// Get the settings from localized var.
	let settings = new Settings('atumOrder');
	let tooltip = new Tooltip();
	let dateTimePicker = new DateTimePicker(settings);
	new EnhancedSelect();
	new AtumOrders(settings, tooltip, dateTimePicker);
	new OrderNotes(settings);
	
});