/* ===================
 INTRO HELP GUIDES
 =================== */

import introJs from 'intro.js/minified/intro.min';
import Settings from '../config/_settings';

interface IIntroOptions {
	steps: IIntroStep[];
	nextLabel?: string;
	prevLabel?: string;
	skipLabel?: string;
	doneLabel?: string;
	hidePrev?: boolean;
	hideNext?: boolean;
	nextToDone?: boolean;
	tooltipPosition?: string;
	tooltipClass?: string;
	highlightClass?: string;
	exitOnEsc?: boolean;
	exitOnOverlayClick?: boolean;
	showStepNumbers?: boolean;
	keyboardNavigation?: boolean;
	showButtons?: boolean;
	showBullets?: boolean;
	showProgress?: boolean;
	scrollToElement?: boolean;
	scrollTo?: 'element'|'tooltip';
	scrollPadding?: number;
	overlayOpacity?: number;
	disableInteraction?: boolean;
}

interface IIntroStep {
	title?: string;
	intro: string;
	element?: Element;
	elementSelector?: string;
}

export default class HelpGuide {

	IntroJs: any = introJs();
	introOptions: IIntroOptions;
	introSteps: IIntroStep[] = [];

	isAuto: boolean = false;

	constructor(
		private settings: Settings
	) {

		// Check if there is any auto-guide in the passed settings.
		const autoGuide: IIntroStep[] = <IIntroStep[]>this.settings.get( 'autoHelpGuide' );
		if ( autoGuide && Array.isArray( autoGuide ) && autoGuide.length ) {
			this.introSteps = autoGuide;
			this.isAuto = true; // Prepare it to be dismissed permanently.
		}

		// Get the intro.js options from the localized var.
		this.introOptions = <IIntroOptions>(this.settings.get( 'introJsOptions' ) || {});

		// If the options are coming at instantiation, run the guide automatically.
		this.prepareOptions();

		this.bindEvents();

	}

	/**
	 * Prepare the options' JSON for intro.js
	 */
	prepareOptions() {

		if ( this.introSteps && Array.isArray( this.introSteps) && this.introSteps.length ) {

			// There are selectors that introJS doesn't understand, so let's use jQuery here.
			this.introSteps.forEach( ( step: IIntroStep ) => {
				if ( step.element ) {
					step.element = $( step.element ).get( 0 );
				}
			} );

			this.introOptions.steps = this.introSteps;
			this.runGuide();
		}

	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// Start any intro guide by clicking the button.
		$( 'body' ).on( 'click', '.show-intro-guide', ( evt: JQueryEventObject ) => {

			const $button: JQuery = $( evt.currentTarget ),
			      guide: string   = $button.data( 'guide' );

			if ( ! guide ) {
				return;
			}

			// First try to find out in the settings option (for quick or one-time guides).
			if ( this.settings.get( guide ) && Array.isArray( this.settings.get( guide ) ) ) {
				this.introSteps = this.settings.get( guide );
				this.isAuto = false;
				this.prepareOptions();
			}
			// Get the guide steps via Ajax.
			else {

				let data: any = {
					action  : 'atum_get_help_guide_steps',
					security: this.settings.get( 'helpGuideNonce' ),
					guide,
				}

				if ( $button.data( 'guide-path' ) ) {
					data.path = $button.data( 'guide-path' );
				}

				if ( this.settings.get( 'screenId' ) ) {
					data.screen = this.settings.get( 'screenId' );
				}

				$.ajax( {
					url       : window[ 'ajaxurl' ],
					method    : 'post',
					dataType  : 'json',
					data,
					beforeSend: () => $button.addClass( 'loading-guide' ),
					success   : ( response: any ) => {

						$button.removeClass( 'loading-guide' );

						if ( response.success ) {
							this.introSteps	= response.data;
							this.isAuto = false;
							this.prepareOptions();
						}

					},
				} );

			}

		} );

	}

	/**
	 * Run the tour guide
	 */
	runGuide() {

		$( 'body' ).addClass( 'running-atum-help-guide' );
		this.IntroJs.setOptions( this.introOptions ).start();

		// @ts-ignore
		this.IntroJs.onexit( () => {

			$( 'body' ).removeClass( 'running-atum-help-guide' );

			if ( this.isAuto && this.settings.get( 'screenId' ) ) {

				$.ajax( {
					url   : window[ 'ajaxurl' ],
					method: 'post',
					data  : {
						action  : 'atum_save_closed_auto_guide',
						security: this.settings.get( 'helpGuideNonce' ),
						screen  : this.settings.get( 'screenId' ),
					},
				} );

			}

		} );

	}

	/**
	 * Build a help guide button
	 *
	 * @param {string} guide
	 * @param {string} path
	 *
	 * @return {string}
	 */
	getHelpGuideButton( guide: string, path: string = '' ) {

		let dataAtts: string = `data-guide="${ guide }"`;

		if ( path ) {
			dataAtts += ` data-path="${ path }"`;
		}

		return `<i class="atum-icon atmi-indent-increase show-intro-guide atum-tooltip" ${ dataAtts } title="${ this.settings.get( 'showHelpGuide' ) }"></i>`;

	}

	/**
	 * Setter for the introSteps prop
	 *
	 * @param {IIntroStep[]} introSteps
	 */
	setIntroSteps( introSteps: IIntroStep[] ) {
		this.introSteps = introSteps;
	}

}