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
jQuery( ( $: JQueryStatic ) => {
	
	// Get the settings from localized var.
	const settings = new Settings( 'atumDashVars', {
		chartColors: {
			red       : '#FF4848',
			orange    : '#EFAF00',
			green     : '#69C61D',
			greenTrans: 'rgba(106, 200, 30, 0.79)',
			greenLight: '#D5F5BA',
			greenBlue : 'rgba(30, 200, 149, 0.79)',
			blue      : '#00B8DB',
			blueTrans : 'rgba(0, 183, 219, 0.79)',
		},
	} );

	const tooltip = new Tooltip();
	new Dashboard( settings, tooltip );
	
});