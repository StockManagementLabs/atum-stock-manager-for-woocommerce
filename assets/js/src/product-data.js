/**
 * Atum Product Data
 *
 * @copyright Stock Management Labs ©2019
 *
 * @since 1.4.1
 */

// Only load Babel Polyfill if is not being included by another library
if (!global._babelPolyfill) {
	require('babel-polyfill');
}

window.$ = window.jQuery;

/**
 * Third Party Plugins
 */

import 'bootstrap/js/dist/button';     // From node_modules
import 'switchery-npm/index'           // From node_modules


/**
 * Components
 */

import Settings from './config/_settings';



// Modules that need to execute when the DOM is ready should go here.
$( () => {
	
	// Get the settings from localized var.
	Settings.init('atumProductData');
	
	
});