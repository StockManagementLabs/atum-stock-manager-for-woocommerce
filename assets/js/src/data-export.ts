/**
 * Atum Data Export
 *
 * @copyright Stock Management Labs ©2022
 *
 * @since 1.2.5
 */


/**
 * Components
 */

import Settings from './config/_settings';
import DataExport from './components/export/_export';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {
	
	// Get the options from the localized var.
	const settings = new Settings( 'atumExport' );
	new DataExport( settings );
	
});