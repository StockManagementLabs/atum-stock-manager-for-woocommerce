/* ===================
 INTRO HELP GUIDES
 =================== */

import introJs from 'intro.js/minified/intro.min';
import Settings from '../config/_settings';
import Utils from '../utils/_utils';
import WPHooks from '../interfaces/wp.hooks';

interface IIntroOptions {
	steps: IGuideStep[];
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

interface IGuideStep {
	title?: string;
	intro: string;
	element?: Element;
	elementSelector?: string;
	load: 'init'|'lazy';
	first?: boolean;
	position?: 'auto|top|left|right|bottom|bottom-middle-aligned|bottom-right-aligned';
	markerPosition?: 'top-right|bottom-right|bottom-left|top-left';
}

export default class HelpGuide {

	IntroJs: any = introJs();
	introOptions: IIntroOptions;
	guideSteps: IGuideStep[] = [];
	cachedGuides: { string?: IGuideStep[] } = {};

	step: number = null;
	isAuto: boolean = false;
	guide: string = null;
	markersEnabled: boolean = false;
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.

	constructor(
		private settings: Settings
	) {

		// Check if there is any auto-guide within the passed settings.
		const autoGuide: IGuideStep[] = <IGuideStep[]>this.settings.get( 'hgAutoGuide' );
		if ( autoGuide && Array.isArray( autoGuide ) && autoGuide.length ) {

			this.guideSteps = autoGuide;
			this.isAuto = true; // Prepare it to be dismissed permanently.

			if ( this.settings.get( 'hgMainGuide' ) ) {
				this.guide = this.settings.get( 'hgMainGuide' );
				this.maybeCacheGuide();
			}

		}

		// Get the intro.js options from the localized var.
		this.introOptions = <IIntroOptions> ( this.settings.get( 'hgIntroJsOptions' ) || {} );

		// If the options are coming at instantiation, run the guide automatically.
		if ( this.prepareIntroOptions() ) {
			this.runGuide();
		}

		this.bindEvents();

	}

	/**
	 * Prepare the options' JSON for intro.js
	 */
	prepareIntroOptions(): boolean {

		if ( Array.isArray( this.guideSteps ) && this.guideSteps.length ) {

			this.maybeCacheGuide();

			// There are selectors that introJS doesn't understand, so let's use jQuery here.
			this.guideSteps.forEach( ( step: IGuideStep ) => {
				if ( step.element ) {
					step.element = $( step.element ).get( 0 );
				}
			} );

			this.introOptions.steps = this.guideSteps;

			return true;

		}

		return false;

	}

	/**
	 * Bind events
	 */
	bindEvents() {

		const $body: JQuery = $( 'body' );

		$body

			// Start any intro guide by clicking the button.
			.on( 'click', '.show-intro-guide', ( evt: JQueryEventObject ) => {

				const $button: JQuery = $( evt.currentTarget );
				this.guide = $button.parent().data( 'guide' );

				this.getGuide( () => {

					if ( this.prepareIntroOptions() ) {
						this.runGuide();
					}

				}, $button );

			} )

			// Show the help markers when clicking the button.
			.on( 'click', '.show-help-markers', ( evt: JQueryEventObject ) => {

				const $button: JQuery             = $( evt.currentTarget ),
				      $otherActiveButtons: JQuery = $( '.show-help-markers.active' ).not( $button );

				if ( $otherActiveButtons.length ) {
					$otherActiveButtons.removeClass( 'active' );
					this.markersEnabled = false;
					this.toggleHelpMarkers();
				}

				this.markersEnabled = ! this.markersEnabled;
				this.guide = $button.parent().data( 'guide' );
				this.getGuide( () => this.toggleHelpMarkers(), $button );
				$button.toggleClass( 'active', this.markersEnabled );

			} )

			// Open a specific step when clicking a help marker.
			.on( 'click', '.atum-help-marker', ( evt: JQueryEventObject ) => {

				if ( ! $body.hasClass( 'atum-show-help-markers' ) ) {
					return;
				}

				const $elem: JQuery = $( evt.currentTarget );

				// Only open the guide step if the user clicked on the help marker icon (the pseudo-element).
				if ( ! Utils.pseudoClick( evt, $elem, 'before' ) ) {
					return;
				}

				evt.stopImmediatePropagation();
				evt.stopPropagation();

				this.step = parseInt( $elem.data( 'step' ) || '0' );

				if ( this.step ) {

					this.isAuto = false;
					this.getGuide( () => {

						if ( this.prepareIntroOptions() ) {
							this.runGuide();
							this.step = null;
						}

					} );

				}

			} );

	}

	/**
	 * Load the guide set to the "guide" prop.
	 *
	 * @param {Function} success
	 * @param {JQuery}   $button
	 */
	getGuide( success: Function, $button: JQuery = null ) {

		if ( ! this.guide ) {
			return;
		}

		// If already cached, load it instead of retrieving it again via Ajax.
		if ( this.cachedGuides.hasOwnProperty( this.guide ) ) {
			this.setGuideSteps( this.cachedGuides[ this.guide ] );
			success();
		}
		// Get the guide steps via Ajax.
		else {

			$button && $button.addClass( 'loading-guide' );

			this.loadGuideSteps()
				.then( () => {

					$button && $button.removeClass( 'loading-guide' );
					success();

					this.wpHooks.doAction( 'atum_helpGuide_loaded', this.guide );

				} )
				.catch( ( error: string ) => {

					$button && $button.removeClass( 'loading-guide' );

					if ( error ) {
						Utils.addNotice( 'error', error, true );
					}

				} );

		}

	}

	/**
	 * Load the guide steps through ajax
	 *
	 * @return {Promise<void>}
	 */
	loadGuideSteps(): Promise<void> {

		return new Promise( ( resolve: Function, reject: Function ) => {

			$.ajax( {
				url       : window[ 'ajaxurl' ],
				method    : 'post',
				dataType  : 'json',
				data      : {
					action  : 'atum_get_help_guide_steps',
					security: this.settings.get( 'hgNonce' ),
					guide   : this.guide,
				},
				success   : ( response: any ) => {

					if ( response.success ) {
						this.setGuideSteps( response.data );
						this.isAuto = false;
						resolve();
					}
					else {
						reject( response.data );
					}

				},
				error: () => reject()
			} );

		} );

	}

	/**
	 * Run the tour guide
	 */
	runGuide() {

		$( 'body' ).addClass( 'running-atum-help-guide' );
		this.IntroJs.setOptions( this.introOptions );

		if ( this.step ) {
			this.IntroJs._currentStepNumber = this.step;
		}

		// @ts-ignore
		this.IntroJs

			.onexit( () => {

				$( 'body' ).removeClass( 'running-atum-help-guide' );

				// Save the closed auto-guide as user meta to not load again.
				if ( this.isAuto && this.guide ) {
					this.isAuto = false;
					this.saveClosedAutoGuide();

					// After closing an auto-guide, tell the user how to access it later.
					const $helpGuideButtons: JQuery = $( '.help-guide-buttons' ).filter( ':visible' );

					if ( $helpGuideButtons.length ) {

						setTimeout( () => {

							this.IntroJs.setOptions( {
								steps: [
									{
										element : $helpGuideButtons.get(0),
										title   : this.settings.get( 'hgGuideButtonsTitle' ),
										intro   : this.settings.get( 'hgGuideButtonsNotice' ),
										position: 'left',
									},
								],
								doneLabel: this.settings.get( 'hgGotIt' )
							} ).start();

						}, 500 );

					}

				}

				this.wpHooks.doAction( 'atum_helpGuide_onExit', this.guide );

			} )
			// Run a hook when the guide step changes, so we can adjust the UI externally if needed.
			.onchange( ( targetElem: HTMLElement ) => this.wpHooks.doAction( 'atum_helpGuide_onChange', this.guide, targetElem ) )
			.start();

	}

	/**
	 * Build the help guide buttons
	 *
	 * @param {string} guide
	 *
	 * @return {string}
	 */
	getHelpGuideButtons( guide: string ) {

		if ( ! guide ) {
			return '';
		}

		return `
			<span class="spacer"></span>
			<span class="help-guide-buttons" data-guide="${ guide }">
				<i class="show-help-markers atum-icon atmi-flag atum-tooltip" title="${ this.settings.get( 'hgShowHelpMarkers' ) }"></i>
				<i class="show-intro-guide atum-icon atmi-indent-increase atum-tooltip" title="${ this.settings.get( 'hgShowHelpGuide' ) }"></i>					
			</span>
		`;

	}

	/**
	 * Setter for the guideSteps prop
	 *
	 * @param {IGuideStep[]} guideSteps
	 */
	setGuideSteps( guideSteps: IGuideStep[] ) {

		this.guideSteps = guideSteps;

		// Only add the help markers if weren't added yet.
		if ( ! this.cachedGuides.hasOwnProperty( this.guide ) ) {
			this.addHelpMarkers();
		}

		this.maybeCacheGuide();

	}

	/**
	 * Save the closed auto-guide status
	 */
	saveClosedAutoGuide() {

		$.ajax( {
			url   : window[ 'ajaxurl' ],
			method: 'post',
			data  : {
				action  : 'atum_save_closed_auto_guide',
				security: this.settings.get( 'hgNonce' ),
				guide   : this.guide
			},
		} );

	}

	/**
	 * Add static help markers to the page elements.
	 */
	addHelpMarkers() {

		if ( Array.isArray( this.guideSteps ) && this.guideSteps.length ) {

			// Get all the steps that must be initialized.
			this.guideSteps.forEach( ( step: IGuideStep, index: number ) => {
				if ( ! step.load || 'init' === step.load ) {
					this.prepareHelpMarker( step, index );
				}
			} );

			// Elements that refresh when table is updated.
			this.addLazyHelpMarkers();

		}

	}

	/**
	 * Add lazy help markers to the page elements.
	 */
	addLazyHelpMarkers() {

		if ( Array.isArray( this.guideSteps ) && this.guideSteps.length ) {

			this.guideSteps.forEach( ( step: IGuideStep, index: number ) => {
				if ( 'lazy' === step.load ) {
					this.prepareHelpMarker( step, index );
				}
			} );

			this.toggleHelpMarkers();

		}

	}

	/**
	 * Prepare a help marker
	 *
	 * @param {IGuideStep} step
	 * @param {number} index
	 */
	prepareHelpMarker( step: IGuideStep, index: number ) {

		if ( ! step.element && ! step.elementSelector ) {
			return;
		}

		let $elem: JQuery = $( step.element || step.elementSelector );

		if ( ! $elem.length ) {
			console.warn( 'Guide element not found', step );
			return;
		}

		if ( step.first ) {
			$elem = $elem.first();
		}

		if ( $elem.is( 'tr, thead, tbody' ) ) {
			console.warn( 'tr, thead and tbody elements are not allowed', step );
			return;
		}

		// Using a "custom" HTML element to avoid CSS issues.
		// The step number must be 1 or greater.
		const $helpMarker: JQuery = $( `
			<atum-help-marker class="atum-help-marker active" 
				data-guide="${ this.guide }"
				data-step="${ index + 1  }"
				data-marker-position="${ step.markerPosition || 'top-right' }"
				data-position="${ step.position || 'auto' }"	
			/>
		` );

		// Special cases to not break tables.
		if ( $elem.is( 'td,th' ) ) {

			// Do not readd the marker if for some reason was already there.
			if ( ! $elem.children( '.atum-help-marker' ).length ) {
				$elem.wrapInner( $helpMarker );
			}

		}
		else {

			// If there is a tooltip on the elem parent, get this one to avoid conflicts.
			if ( $elem.parent().hasClass( 'atum-tooltip' ) ) {
				$elem = $elem.parent();
			}

			// Do not readd the marker if for some reason was already there.
			if ( ! $elem.parent( '.atum-help-marker' ).length ) {
				$elem.wrap( $helpMarker );
			}

		}

	}

	/**
	 * Toggle help markers visibility.
	 */
	toggleHelpMarkers() {

		const $helpMarkers: JQuery = $( '.atum-help-marker' );

		$( 'body' ).toggleClass( 'atum-show-help-markers', this.markersEnabled );
		$helpMarkers.not( `[data-guide="${ this.guide }"]` ).removeClass( 'active' );
		$helpMarkers.filter( `[data-guide="${ this.guide }"]` ).toggleClass( 'active', this.markersEnabled );

		this.wpHooks.doAction( 'atum_helpGuide_toggleHelpMarkers', this.markersEnabled, this.guide );

	}

	/**
	 * Add the current guide to cache if necessary
	 */
	maybeCacheGuide() {
		if ( ! this.cachedGuides.hasOwnProperty( this.guide ) ) {
			this.cachedGuides[ this.guide ] = this.guideSteps;
		}
	}

}