/**
 * Atum Settings
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 0.0.2
 */

/**
 * Third Party Plugins
 */

import '../vendor/select2';             // A fixed version compatible with webpack


/**
 * Components
 */

import EnhancedSelect from './components/_enhanced-select';
import Settings from './config/_settings';
import SettingsPage from './components/settings-page/_settings-page';
import Tooltip from './components/_tooltip';
import DateTimePicker from './components/_date-time-picker';

// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {
	
	// Get the options from the localized var.
	const settings = new Settings('atumSettingsVars');
	const enhancedSelect = new EnhancedSelect();
	const tooltip = new Tooltip();
	const dateTimePicker = new DateTimePicker( settings );
	new SettingsPage( settings, enhancedSelect, tooltip, dateTimePicker );

});
