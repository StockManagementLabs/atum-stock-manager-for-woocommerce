/**
 * Check order prices from the WC Orders list
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.8.6
 */

/**
 * Components
 */

import CheckOrderPrices from './components/_check-order-prices';
import Settings from './config/_settings';
import Tooltip from './components/_tooltip';

// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $) => {

	window['$'] = $; // Avoid conflicts.

	// Get the settings from localized var.
	const settings = new Settings('atumCheckOrders');
	const tooltip  = new Tooltip( false );

	new CheckOrderPrices( settings, tooltip );

});