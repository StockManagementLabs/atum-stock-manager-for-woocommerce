/**
 * ATUM Trials Expiration Modal
 *
 * @copyright Stock Management Labs ©2024
 *
 * @since 1.9.27
 */

/**
 * Components
 */

import Settings from './config/_settings';
import Trials from './components/addons/_trials';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {

	// Get the options from the localized var.
	const settings = new Settings( 'atumTrialsModal' );
	new Trials( settings );
	
});