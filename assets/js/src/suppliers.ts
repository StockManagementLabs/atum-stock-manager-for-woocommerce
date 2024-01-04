/**
 * Atum Suppliers
 *
 * @copyright Stock Management Labs ©2024
 *
 * @since 1.9.19
 */

/**
 * Third Party Plugins
 */


import '../vendor/select2';      // A fixed version compatible with webpack


/**
 * Components
 */

import Settings from './config/_settings';
import Supplier from './components/suppliers/_supplier';
import EnhancedSelect from './components/_enhanced-select';

// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {

	// Get the options from the localized var. For Now only will be set if WPML is active.
	const settings                       = new Settings( 'atumSupplierVars' ),
	      enhancedSelect: EnhancedSelect = new EnhancedSelect();

	new Supplier( settings, enhancedSelect );

});
