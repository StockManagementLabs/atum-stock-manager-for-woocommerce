/**
 * Atum Marketing Popup
 *
 * @copyright Stock Management Labs ©2022
 *
 * @since 1.5.2
 */

/**
 * Components
 */

import MarketingPopup from './components/_marketing-popup';
import Settings from './config/_settings';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {
	
	// Get the options from the localized var.
	const settings = new Settings( 'atumMarketingPopupVars' );
	new MarketingPopup( settings );
	
});