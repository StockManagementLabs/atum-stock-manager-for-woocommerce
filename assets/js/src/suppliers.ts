/**
 * Atum Suppliers
 *
 * @copyright Stock Management Labs ©2026
 *
 * @since 1.9.19
 */

/**
 * Components
 *
 * Select2 is enqueued as the `atum-select2` WP handle from `Suppliers.php` deps.
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

} );
