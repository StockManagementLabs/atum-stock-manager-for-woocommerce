/**
 * Atum Product Data
 *
 * @copyright Stock Management Labs ©2019
 *
 * @since 1.4.1
 */

window['$'] = window['jQuery'];


/**
 * Components
 */

import Settings from './config/_settings';
import ProductDataMetaBoxes from './components/product-data/_product-data-meta-boxes';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	// Get the settings from localized var.
	let settings = new Settings('atumProductData');
	new ProductDataMetaBoxes(settings);
	
});