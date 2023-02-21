import Settings from '../../config/_settings';
import Tooltip from '../_tooltip';
import HelpGuide from '../_help-guide';
import Globals from '../list-table/_globals';
import WPHooks from '../../interfaces/wp.hooks';

export class StockCentralKnowledgeBase {

	$akbButtonsWrapper: JQuery;
	knowledgeEnabled: boolean = false;
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.

	constructor(
		private globals: Globals,
		private settings: Settings,
		private toolTip: Tooltip,
		private helpGuide: HelpGuide
	) {

		this.$akbButtonsWrapper = $( document ).find( '.akb-buttons-wrapper' );

		// Bind events.
		this.bindEvents();

		// Add hooks.
		this.addHooks();

		// Add help guides.
		this.addHelpGuides();

		// Hide AKB tooltips.
		this.checkKnowledgeVisibility();
	}

	bindEvents() {
		this.$akbButtonsWrapper
			.on( 'click', '.display-akb-button', () => {
				this.knowledgeEnabled = ! this.knowledgeEnabled;
				this.checkKnowledgeVisibility();
			} )
	}

	addHooks() {
		// Add the lost helpguides after refreshing table.
		this.wpHooks.addAction( 'atum_listTable_tableUpdated', 'atum', () => this.addRefreshedHelpGuides() );
	}

	/**
	 * Add static help-guides buttons to the page elements.
	 */
	addHelpGuides() {
		//$( '#atum-stock-central-lists-button' ).data( 'step', 1 ).after( this.addButton( 1 ) );

		this.addWrapper( $( '#atum-stock-central-lists-button' ), 1 );
		this.addWrapper( this.globals.$atumList.find( '.list-table-header .search-box .input-group' ), 5 );
		this.addWrapper( this.globals.$atumList.find( '.list-table-header .sticky-columns-button' ), 6 );
		this.addWrapper( this.globals.$atumList.find( '.list-table-header .sticky-header-button' ), 7 );

		this.addWrapper( $( '#screen-options-link-wrap #show-settings-link' ), 20 );
		this.addWrapper( $( '#contextual-help-link-wrap #contextual-help-link' ), 21 );
		this.addWrapper( $( '#atum-export-link-wrap #show-export-settings-link' ), 22 );

		// Elements that refresh when table is updated.
		this.addRefreshedHelpGuides();
	}

	/**
	 * Add refreshed help-guides buttons to the page elements.
	 */
	addRefreshedHelpGuides() {
		this.addWrapper( this.globals.$atumList.find( '.subsubsub .all_stock a' ), 2 );
		this.addWrapper( this.globals.$atumList.find( '#restock_status' ), 3 );
		this.addWrapper( this.globals.$atumList.find( '.subsubsub .unmanaged a' ), 4 );
		this.addWrapper( this.globals.$atumList.find( '.top #filters_container .bulkactions' ), 8 );
		this.addWrapper( this.globals.$atumList.find( '#product_cat + .select2' ), 9 );
		this.addWrapper( this.globals.$atumList.find( '.dropdown_product_type + .select2' ), 10 );
		this.addWrapper( this.globals.$atumList.find( 'select#supplier + .select2' ), 11 );
		this.addWrapper( this.globals.$atumList.find( 'select[name="extra_filter"] + .select2' ), 12 );

		this.addWrapper( this.globals.$atumList.find( 'thead th#thumb .col-product-details' ), 13 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#ID .col-product-details' ), 14 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#title .col-product-details' ), 15 );
		this.addWrapper( this.globals.$atumList.find( 'thead th#calc_type .col-product-details' ), 14 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_sku .col-product-details' ), 17 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_supplier .col-product-details' ), 18 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_supplier_sku .col-product-details' ), 19 );
		this.addWrapper( this.globals.$atumList.find( 'thead th#calc_location .col-product-details' ), 15 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_regular_price .col-product-details' ), 21 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_sale_price .col-product-details' ), 22 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_purchase_price .col-product-details' ), 23 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#calc_gross_profit .col-product-details' ), 24 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_weight .col-product-details' ), 25 );

		//this.addWrapper( this.globals.$atumList.find( 'thead th#_stock .col-stock-counters' ), 26 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_out_stock_threshold .col-stock-counters' ), 27 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_inbound_stock .col-stock-counters' ), 28 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_stock_on_hold .col-stock-counters' ), 29 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_reserved_stock .col-stock-counters' ), 30 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#calc_back_orders .col-stock-counters' ), 31 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_sold_today .col-stock-counters' ), 32 );

		//this.addWrapper( this.globals.$atumList.find( 'thead th#_customer_returns .col-stock-negatives' ), 33 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_warehouse_damage .col-stock-negatives' ), 34 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_lost_in_post .col-stock-negatives' ), 35 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_other_logs .col-stock-negatives' ), 36 );

		//this.addWrapper( this.globals.$atumList.find( 'thead th#_sales_last_days .col-stock-selling-manager' ), 37 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#calc_will_last .col-stock-selling-manager' ), 38 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_out_stock_days .col-stock-selling-manager' ), 39 );
		//this.addWrapper( this.globals.$atumList.find( 'thead th#_lost_sales .col-stock-selling-manager' ), 40 );
		this.addWrapper( this.globals.$atumList.find( 'thead th#calc_stock_indicator .col-stock-selling-manager' ), 16 );

		this.addWrapper( this.globals.$atumList.find( '.atum-list-table td .set-meta' ).first(), 17 );
		this.addWrapper( this.globals.$atumList.find( '.atum-list-table td .compounded' ).first(), 18 );

		this.addWrapper( this.globals.$atumList.find( 'tr.totals td.column-cb span' ), 19 );
	}

	/**
	 * Add a wrapper for the element.
	 *
	 * @param {JQuery} $elem
	 * @param {number} step
	 */
	addWrapper( $elem: JQuery, step: number ) {
		if ( ! $elem.length ) {
			return;
		}

		let $wrapper: JQuery = $('<span class="akb-wrapper">');

		$elem.data( 'step', step );
		$elem.before( $wrapper );
		$elem.appendTo( $wrapper );
		$elem.after( this.addButton( step ) );
	}

	/**
	 * Add the icon for the help-guide
	 *
	 * @param {number} step
	 * @returns {string}
	 */
	addButton( step: number = 0 ) {
		return this.helpGuide.getHelpGuideButton( 'stock-central', '', 'question atum-kb', step );
	}

	/**
	 * Toggle Atum Knowledge tooltips visibility.
	 */
	checkKnowledgeVisibility() {
		const $akb: JQuery = $( '.atum-kb' );

		if ( this.knowledgeEnabled ) {
			$akb.show();
		}
		else {
			$akb.hide();
		}
	}
}