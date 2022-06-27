/**
 * Atum Suppliers
 *
 * @copyright Stock Management Labs ©2022
 *
 * @since 1.9.19
 */


/**
 * Components
 */

import Supplier from './components/suppliers/_supplier';

// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {
	
	// Get the options from the localized var.
	new Supplier();

});
