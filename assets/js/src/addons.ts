/**
 * ATUM Addons
 *
 * @copyright Stock Management Labs ©2023
 *
 * @since 1.2.0
 */

/**
 * Components
 */

import AddonsPage from './components/addons/_addons-page';
import Settings from './config/_settings';
import Tooltip from './components/_tooltip';
import Trials from './components/addons/_trials';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {

	// Get the options from the localized var.
	const settings = new Settings( 'atumAddons' ),
	      tooltip  = new Tooltip(),
	      trials   = new Trials( settings, () => location.reload() );

	new AddonsPage( settings, tooltip, trials );
	
});