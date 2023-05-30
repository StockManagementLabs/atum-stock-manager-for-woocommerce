/* ===================
 INTRO HELP GUIDES
 =================== */

import introJs from 'intro.js/minified/intro.min';
import Settings from '../config/_settings';
import Swal from 'sweetalert2';
import WPHooks from '../interfaces/wp.hooks';
import Utils from '../utils/_utils';

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
	markerPosition?: 'top-right|bottom-right|bottom-left|top-left';
}

export default class HelpGuide {

	IntroJs: any = introJs();
	introOptions: IIntroOptions;
	guideSteps: IGuideStep[] = [];
	helpMarkers: IGuideStep[] = [];
	cachedGuides: { string?: IGuideStep[] } = {};

	step: number = 0;
	isAuto: boolean = false;
	guide: string = null;
	markersEnabled: boolean = false;
	$markersWrapper: JQuery = $( '<div class="atum-help-markers" />' );
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.

	constructor(
		private settings: Settings
	) {

		// Add the help markers wrapper to the DOM.
		this.$markersWrapper.appendTo( $( 'body' ) );

		// Check if there is any auto-guide within the passed settings.
		const autoGuide: IGuideStep[] = <IGuideStep[]>this.settings.get( 'hgAutoGuide' );
		if ( autoGuide && Array.isArray( autoGuide ) && autoGuide.length ) {
			this.guideSteps = autoGuide;
			this.isAuto = true; // Prepare it to be dismissed permanently.

			if ( this.settings.get( 'hgMainGuide' ) ) {
				this.guide = this.settings.get( 'hgMainGuide' );
			}
		}

		// Check if there are help markers to add.
		const hMarkers: IGuideStep[] = <IGuideStep[]>this.settings.get( 'hgHelpMarkers' );
		if ( hMarkers && Array.isArray( hMarkers ) && hMarkers.length ) {
			this.helpMarkers = hMarkers;
			this.addHelpMarkers();
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

				if ( ! this.guide ) {
					return;
				}

				if ( $button.data( 'guide-step' ) ) {
					this.step = parseInt( $button.data( 'guide-step' ) );
				}

				// First try to find out in the settings option (for quick or one-time guides).
				if ( this.settings.get( this.guide ) && Array.isArray( this.settings.get( this.guide ) ) ) {

					this.guideSteps = this.settings.get( this.guide );
					this.isAuto = false;

					if ( this.prepareIntroOptions() ) {
						this.runGuide();
					}

				}
				// If already cached, load it instead of retrieving it again via Ajax.
				else if ( this.cachedGuides.hasOwnProperty( this.guide ) ) {

					if ( this.prepareIntroOptions() ) {
						this.runGuide();
					}

				}
				// Get the guide steps via Ajax.
				else {

					$button.addClass( 'loading-guide' );

					this.loadGuideSteps()
						.then( () => {

							$button.removeClass( 'loading-guide' );
							if ( this.prepareIntroOptions() ) {
								this.runGuide();
							}

						} )
						.catch( ( error: string ) => {

							$button.removeClass( 'loading-guide' );

							if ( error ) {
								Swal.fire( {
									icon             : 'error',
									title            : this.settings.get( 'hgError' ),
									text             : error,
									confirmButtonText: this.settings.get( 'hgOk' ),
									showCloseButton  : true,
								} );
							}

						} );

				}

			} )

			// Show the help markers when clicking the button.
			.on( 'click', '.show-help-markers', ( evt: JQueryEventObject ) => {
				this.markersEnabled = ! this.markersEnabled;
				$( evt.currentTarget ).toggleClass( 'active', this.markersEnabled );
				this.toggleHelpMarkers();
			} )

			// Open a specific step when clicking a help marker.
			.on( 'click', '.atum-help-marker', ( evt: JQueryEventObject ) => {

				if ( ! $body.hasClass( 'atum-show-help-markers' ) ) {
					return;
				}

				const $elem: JQuery     = $( evt.currentTarget ),
				      elem: HTMLElement = <HTMLElement> $elem.get( 0 );

				// Only open the guide step if the user clicked on the help marker icon (the pseudo-element).
				if ( ! Utils.pseudoClick( evt, elem, 'before' ) ) {
					return;
				}

				this.step = parseInt( $elem.data( 'step' ) || '0' );

				if ( this.step ) {
					this.guideSteps = this.settings.get( 'hgHelpMarkers' );
					this.isAuto = false;

					if ( this.prepareIntroOptions() ) {
						this.runGuide();
					}
				}

			} );

	}

	/**
	 * Load the guide steps through ajax
	 *
	 * @return {Promise<void>}
	 */
	loadGuideSteps(): Promise<void> {

		return new Promise( ( resolve: Function, reject: Function ) => {

			const data: any = {
				action  : 'atum_get_help_guide_steps',
				security: this.settings.get( 'hgNonce' ),
				guide   : this.guide,
			}

			if ( this.settings.get( 'hgScreenId' ) ) {
				data.screen = this.settings.get( 'hgScreenId' );
			}

			$.ajax( {
				url       : window[ 'ajaxurl' ],
				method    : 'post',
				dataType  : 'json',
				data,
				success   : ( response: any ) => {

					if ( response.success ) {
						this.guideSteps	= response.data;
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

		this.IntroJs.start();

		// @ts-ignore
		this.IntroJs.onexit( () => {

			$( 'body' ).removeClass( 'running-atum-help-guide' );

			if ( this.isAuto && this.settings.get( 'hgScreenId' ) ) {
				this.saveClosedAutoGuide( this.settings.get( 'hgScreenId' ) );
			}

			this.wpHooks.doAction( 'atum_helpGuide_onExit', this.guide );

		} );

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

		const helpMarkersButton: string = ( Array.isArray( this.helpMarkers ) && this.helpMarkers.length > 0 ) ? `<i class="show-help-markers atum-icon atmi-flag atum-tooltip" title="${ this.settings.get( 'hgShowHelpMarkers' ) }"></i>` : '';

		return `
			<span class="help-guide-buttons" data-guide="${ guide }">
				${ helpMarkersButton }
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
	}

	/**
	 * Save the closed auto-guide status
	 *
	 * @param {string} screen
	 */
	saveClosedAutoGuide( screen: string ) {

		$.ajax( {
			url   : window[ 'ajaxurl' ],
			method: 'post',
			data  : {
				action  : 'atum_save_closed_auto_guide',
				security: this.settings.get( 'hgNonce' ),
				screen
			},
		} );

	}

	/**
	 * Add static help markers to the page elements.
	 */
	addHelpMarkers() {

		if ( Array.isArray( this.helpMarkers ) && this.helpMarkers.length ) {

			// Get all the steps that must be initialized.
			this.helpMarkers.forEach( ( step: IGuideStep, index: number ) => {
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

		if ( Array.isArray( this.helpMarkers ) && this.helpMarkers.length ) {

			this.helpMarkers.forEach( ( step: IGuideStep, index: number ) => {
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

		let $elem: JQuery = $( step.element || step.elementSelector );

		if ( ! $elem.length ) {
			console.warn( 'Guide element not found', $elem );
			return;
		}

		if ( step.first ) {
			$elem = $elem.first();
		}

		// Using a "custom" HTML element to avoid CSS issues.
		// The step number must be 1 or greater.
		const $helpMarker: JQuery = $( `
			<atum-help-marker class="atum-help-marker" 
				data-step="${ index + 1  }"
				data-marker-position="${ step.markerPosition || 'top-right' }"			  
			/>
		` );

		if ( $elem.is( 'td,th,tr' ) ) {
			$elem.wrapInner( $helpMarker );
		}
		else {

			if ( $elem.parent().hasClass( 'atum-tooltip' ) ) {
				$elem = $elem.parent();
			}

			$elem.wrap( $helpMarker );

		}

	}

	/**
	 * Toggle help markers visibility.
	 */
	toggleHelpMarkers() {
		$( 'body' ).toggleClass( 'atum-show-help-markers', this.markersEnabled );
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