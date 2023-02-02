/**
 * Atum Product Data
 *
 * @copyright Stock Management Labs ©2023
 *
 * @since 1.4.1
 */


/**
 * Components
 */

import FileAttachments from './components/product-data/_file-attachments';
import ProductDataMetaBoxes from './components/product-data/_product-data-meta-boxes';
import Settings from './config/_settings';

// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {

	// Get the settings from localized var.
	const settings = new Settings( 'atumProductData' );
	new ProductDataMetaBoxes( settings );
	new FileAttachments( settings );

} );