/* =======================================
 SUPPLIER COMPONENT
 ======================================= */

import Settings from '../../config/_settings';
import EnhancedSelect from '../_enhanced-select';

export default class Supplier {

	constructor(
		private settings: Settings,
		private enhancedSelect: EnhancedSelect
	) {

		this.bindEvents();

		if ( undefined !== this.settings.get( 'wpmlActive' ) && '1' === this.settings.get( 'wpmlActive' ) ) {
			this.initWpml();
		}

	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// Set default checkboxes.
		$( '.default-checkbox' ).change( ( evt: JQueryEventObject ) => {

			const $checkbox: JQuery     = $( evt.currentTarget ),
			      $relatedInput: JQuery = $checkbox.closest( '.form-field' ).children( ':input' ).not( $checkbox );

			$relatedInput.toggle( ! $checkbox.is( ':checked' ) );

		} );


	}

	/**
	 * Add WPML behaviour
	 */
	initWpml() {

		/**
		 * Adds the flag image if set.
		 *
		 * @param state
		 * @param {JQuery} $element
		 *
		 * @returns {string}
		 */
		const $select: JQuery = $( '#wpml_lang' );
		const addFlag: Function = ( state: any, $element: JQuery ) => {

			if ( ! state.id ) {
				return state.text;
			}

			return this.genLangOptionsContent( $( state.element ) );

		};

		this.enhancedSelect.doSelect2( $select, {
			templateResult: addFlag,
		} );

		$select.on( 'select2:select', ( evt: Event)=>{

			$( '#select2-wpml_lang-container' ).html( this.genLangOptionsContent( $select.find(':selected') ).html() );

		});

		$( '#select2-wpml_lang-container' ).html( this.genLangOptionsContent( $select.find(':selected') ).html() );

	}

	/**
	 * Generate options and selection content for the WPML lang select2
	 * @param {JQuery} $option
	 * @returns {JQuery}
	 */
	genLangOptionsContent( $option:JQuery ) {

		const flag : any = $option.data('flag'),
		      $state       = $( `<span class="${ flag.code }"><img src="${ flag.flag_url } " alt="${ flag.flag_alt }" class="${ $option.data('flagClasses') }"/> <span>${ $option.text() }</span></span>` ),
		      $img: JQuery = $state.find( 'img' );

		if ( flag.flag_width ) {
			$img.width( flag.flag_width );
		}
		if ( flag.flag_height ) {
			$img.height( flag.flag_height );
		}

		return $state;
	}

}