/**
 * Atum Settings
 *
 * @copyright Stock Management Labs ©2019
 *
 * @since 0.0.2
 */

window['$'] = window['jQuery'];

/**
 * Third Party Plugins
 */

import '../vendor/jquery.address.min';  // This is not downloading the sources
import '../vendor/select2';             // A fixed version compatible with webpack


/**
 * Components
 */

import EnhancedSelect from './components/_enhanced-select';
import Settings from './config/_settings';
import SettingsPage from './components/settings-page/_settings-page';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	// Get the options from the localized var.
	let settings = new Settings('atumSettingsVars');
	let enhancedSelect = new EnhancedSelect();
	new SettingsPage(settings, enhancedSelect);
	
});