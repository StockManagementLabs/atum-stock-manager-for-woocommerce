import Settings from '../../config/_settings';
import Tooltip from '../_tooltip';
import HelpGuide from '../_help-guide';
import Globals from '../list-table/_globals';
import WPHooks from '../../interfaces/wp.hooks';

export class StockCentralKnowledgeBase {

	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.

	constructor(
		private globals: Globals,
		private settings: Settings,
		private toolTip: Tooltip,
		private helpGuide: HelpGuide
	) {

		// Add hooks.
		this.addHooks();

		// Add help guides.
		this.addHelpGuides();

		//
	}

	addHooks() {
		// Add the lost helpguides after refreshing table.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => this.addRefreshedHelpGuides() );
	}

	/**
	 * Add static help-guides buttons to the page elements.
	 */
	addHelpGuides() {
		$( '#atum-stock-central-lists-button' ).data( 'step', 1 ).after( this.addButton( 1 ) );
		this.globals.$atumList.find( '.list-table-header .search-box .input-group' ).data( 'step', 3 ).after( this.addButton( 3 ) );
		this.globals.$atumList.find( '.list-table-header .sticky-columns-button' ).data( 'step', 4 ).after( this.addButton( 4 ) );
		this.globals.$atumList.find( '.list-table-header .sticky-header-button' ).data( 'step', 5 ).after( this.addButton( 5 ) );

		// Elements that refresh when table is updated.
		this.addRefreshedHelpGuides();
	}

	/**
	 * Add refreshed help-guides buttons to the page elements.
	 */
	addRefreshedHelpGuides() {
		this.globals.$atumList.find( '#restock_status' ).data( 'step', 2 ).after( this.addButton( 2 ) );
		this.globals.$atumList.find( '.top #filters_container .bulkactions' ).data( 'step', 6 ).after( this.addButton( 6 ) );
	}

	addButton( step: number = 0 ) {
		return this.helpGuide.getHelpGuideButton( 'stock-central', '', 'question-circle atum-kb', step );
	}
}