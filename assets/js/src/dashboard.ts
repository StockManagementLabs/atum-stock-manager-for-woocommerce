/**
 * Atum Dashboard
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.4.0
 */

/**
 * Third Party Plugins
 */

import 'jquery-nice-select/js/jquery.nice-select.min';      // From node_modules
import 'chart.js/dist/Chart.bundle.min';                    // From node_modules
import 'owl.carousel/dist/owl.carousel.min';                // From node_modules


/**
 * Components
 */

import Dashboard from './components/dashboard/_dashboard';
import Settings from './config/_settings';
import Tooltip from './components/_tooltip';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	window['$'] = $; // Avoid conflicts.
	
	// Get the settings from localized var.
	let settings = new Settings('atumDashVars', {
		chartColors: {
            red       : '#ff4848',
			orange    : '#efaf00',
			green     : '#69c61d',
			greenTrans: 'rgba(106, 200, 30, 0.79)',
			greenLight: '#d5f5ba',
			greenBlue : 'rgba(30, 200, 149, 0.79)',
			blue      : '#00b8db',
			blueTrans : 'rgba(0, 183, 219, 0.79)'
		}
	});
	
	//greenLight: 'rgba(180, 240, 0, 0.79)',
	// Initialize components with dependency injection.
	let tooltip = new Tooltip();
	new Dashboard(settings, tooltip);
	
});