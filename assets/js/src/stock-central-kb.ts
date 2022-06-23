/**
 * Atum List Tables
 *
 * @copyright Stock Management Labs ©2022
 *
 * @since 0.0.1
 */

/**
 * Third Party Plugins
 */

import '../vendor/jquery.address.min';               // This is not downloading the sources
import '../vendor/jquery.jscrollpane';               // A fixed version compatible with webpack
import '../vendor/select2';                          // A fixed version compatible with webpack


/**
 * Components
 */

import Globals from './components/list-table/_globals';
import Settings from './config/_settings';
import Tooltip from './components/_tooltip';
import HelpGuide from './components/_help-guide';
import { StockCentralKnowledgeBase } from './components/knowledge-base/_stock-central';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {
	
	// Get the settings from localized var.
	const settings = new Settings( 'atumStockCentralKB' );
	
	// Set globals.
	const globals = new Globals( settings );

	// Initialize components with dependency injection.
	const tooltip = new Tooltip();

	const helpGuide = new HelpGuide( settings );

	new StockCentralKnowledgeBase( globals, settings, tooltip, helpGuide );

});