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

export default class IntroGuide {

	IntroJs: any = introJs();
	introOptions: IIntroOptions;

	constructor(
		private settings: Settings,
		private introSteps: IIntroStep[] = []
	) {

		// Get the intro.js options from the localized var.
		this.introOptions = this.settings.get( 'introJsOptions' ) || {};

		// If the options are coming at instantiation, run the guide automatically.
		this.prepareOptions();

	}

	/**
	 * Prepare the options' JSON for intro.js
	 */
	prepareOptions() {

		if ( this.introSteps.length ) {
			this.introOptions.steps = this.introSteps;
			this.runGuide();
		}

	}

	/**
	 * Run the tour guide
	 */
	runGuide() {

		this.IntroJs.setOptions( this.introOptions ).start();

	}

}