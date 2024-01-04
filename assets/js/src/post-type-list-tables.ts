/**
 * Atum Post Type List Tables
 *
 * @copyright Stock Management Labs ©2024
 *
 * @since 1.5.0
 */


/**
 * Third Party Plugins
 */


import '../vendor/select2';      // A fixed version compatible with webpack

/**
 * Components
 */


import DateTimePicker from "./components/_date-time-picker";
import DragScroll from "./components/list-table/_drag-scroll";
import EnhancedSelect from './components/_enhanced-select';
import Globals from './components/list-table/_globals';
import HelpGuide from './components/_help-guide';
import TableCellPopovers from './components/_table-cell-popovers';
import PostTypeList from './components/list-table/_post-type-list';
import ScrollBar from './components/list-table/_scroll-bar';
import Settings from './config/_settings';
import Tooltip from './components/_tooltip';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {

	// Get the settings from localized var.
	const settings = new Settings( 'atumPostTypeListVars' );
	const globals = new Globals( $( '#posts-filter, .atum-list-wrapper' ), settings, {
		$atumTable: $( '.wp-list-table' ),
		filterData: {},
	} );
	const enhancedSelect = new EnhancedSelect( $( '#wpbody-content select' ) );
	const tooltip = new Tooltip();
	const dateTimePicker = new DateTimePicker( settings );
	const popover = new TableCellPopovers( settings, dateTimePicker );

	new PostTypeList( settings, globals, enhancedSelect );
	new DragScroll( globals, tooltip, popover );
	new ScrollBar( globals );

	// Add the list table help guide (if available).
	if ( settings.get( 'hgMainGuide' ) ) {
		const helpGuide = new HelpGuide( settings );
		const $tableTitle: JQuery = $( 'h1.wp-heading-inline' );
		$tableTitle.append( helpGuide.getHelpGuideButtons( settings.get( 'hgMainGuide' ) ) );
		tooltip.addTooltips( $tableTitle );
	}

} );