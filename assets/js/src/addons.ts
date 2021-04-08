/**
 * Atum Addons
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.2.0
 */

/**
 * Components
 */

import Settings from './config/_settings';
import AddonsPage from './components/addons-page/_addons-page';
import Tooltip from './components/_tooltip';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {

	window[ '$' ] = $; // Avoid conflicts.

	// Get the options from the localized var.
	let settings = new Settings( 'atumAddons' );
	new Tooltip();
	new AddonsPage( settings );
	
});