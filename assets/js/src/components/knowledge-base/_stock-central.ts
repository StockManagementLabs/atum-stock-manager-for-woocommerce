import Settings from '../../config/_settings';
import Tooltip from '../_tooltip';
import HelpGuide from '../_help-guide';
import Globals from '../list-table/_globals';
import WPHooks from '../../interfaces/wp.hooks';

interface GuideItem {
	title: string;
	element: string;
	intro: string;
	load: 'init'|'lazzy';
	first?: boolean;
	absolute?: boolean;
}

export class StockCentralKnowledgeBase {

	$akbButtonsWrapper: JQuery;
	knowledgeEnabled: boolean = false;
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	guides: GuideItem[];

	constructor(
		private globals: Globals,
		private settings: Settings,
		private toolTip: Tooltip,
		private helpGuide: HelpGuide
	) {

		this.guides = this.settings.get( 'AKBGuides' );

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
			.on( 'click', '.display-akb-button', ( e ) => {
				const $btn: JQuery = $( e.currentTarget );
				this.knowledgeEnabled = ! this.knowledgeEnabled;
				$btn.toggleClass( 'btn-success', this.knowledgeEnabled );
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

		this.guides.forEach( ( guide: GuideItem, index ) => {
			if ( 'init' === guide.load ) {
				let $elem: JQuery = guide.absolute ? $( guide.element ) : this.globals.$atumList.find( guide.element );
				if ( guide.first ) {
					$elem = $elem.first();
				}
				this.addWrapper( $elem, index + 1 );
			}
		} );

		// Elements that refresh when table is updated.
		this.addRefreshedHelpGuides();
	}

	/**
	 * Add refreshed help-guides buttons to the page elements.
	 */
	addRefreshedHelpGuides() {

		this.guides.forEach( ( guide: GuideItem, index ) => {
			if ( 'lazzy' === guide.load ) {
				let $elem: JQuery = guide.absolute ? $( guide.element ) : this.globals.$atumList.find( guide.element );
				if ( guide.first ) {
					$elem = $elem.first();
				}
				this.addWrapper( $elem, index + 1 );
			}
		} );

		this.checkKnowledgeVisibility();
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