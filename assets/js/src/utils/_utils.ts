/*
   ┌───────┐
   │       │
   │ UTILS │
   │       │
   └───────┘
*/

import bigDecimal from 'js-big-decimal';

const Utils = {
	
	/**
	 * Utils settings.
	 */
	settings: {
		delayTimer: 0,
		number    : {
			precision: 0,       // default precision on numbers is 0.
			grouping : 3,       // digit grouping (not implemented yet).
			thousand : ',',
			decimal  : '.',
		},
		currency  : {
			symbol   : '$',
			format   : '%s%v',	// controls output: %s = symbol, %v = value (can be object).
			decimal  : '.',		// decimal point separator.
			thousand : ',',		// thousands separator.
			precision: 2,		// decimal places.
			grouping : 3,		// digit grouping (not implemented yet).
		},
	},
	
	/**
	 * Apply a delay
	 *
	 * @param {Function} callback
	 * @param {number}   ms
	 *
	 * @return {Function}
	 */
	delay( callback: Function, ms: number ) {
		
		clearTimeout( this.settings.delayTimer );
		this.settings.delayTimer = setTimeout( callback, ms );

		return this.settings.delayTimer;
		
	},
	
	/**
	 * Filter the URL Query to extract variables
	 *
	 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
	 *
	 * @param {string} query    The URL query part containing the variables.
	 * @param {string} variable Name of the variable we want to get.
	 *
	 * @return {string|boolean} The variable value if available, false else.
	 */
	filterQuery( query: string, variable: string ): string | boolean {

		const vars: string[] = query.split( '&' );

		for ( let i = 0; i < vars.length; i++ ) {

			const pair = vars[ i ].split( '=' );

			if ( pair[ 0 ] === variable ) {
				return pair[ 1 ];
			}

		}

		return false;

	},

	filterByData( $elem: JQuery, prop: string, val: any ): JQuery {

		if ( typeof val === 'undefined' ) {
			return $elem.filter( ( index: number, elem: Element ) => {
				return typeof $( elem ).data( prop ) !== 'undefined';
			} );
		}
		;

		return $elem.filter( ( index: number, elem: Element ) => {
			return $( elem ).data( prop ) == val;
		} );

	},
	
	/**
	 * Add a notice on top identical to the WordPress' admin notices
	 *
	 * @param {'success'|'error'|'info'|'warning'} type           The notice type.
	 * @param {string}                             msg            The message.
	 * @param {boolean}                            autoDismiss    Optional. Dismiss the notice automatically after some seconds. False by default
	 * @param {int}                                dismissSeconds Optional. The number of seconds after the auto-dismiss is triggered. 5 by default.
	 */
	addNotice( type: 'success'|'error'|'info'|'warning', msg: string, autoDismiss: boolean = false, dismissSeconds: number = 5 ) {

		const $notice: JQuery        = $( `<div class="notice-${ type } notice is-dismissible"><p><strong>${ msg }</strong></p></div>` ).hide(),
		      $dismissButton: JQuery = $( '<button />', { type: 'button', class: 'notice-dismiss' } ),
		      $headerEnd: JQuery     = $( '.wp-header-end' );

		$headerEnd.siblings( '.notice' ).remove();
		$headerEnd.before( $notice.append( $dismissButton ) );
		$notice.slideDown( 100 );

		$dismissButton.on( 'click.wp-dismiss-notice', ( evt: any ) => {

			evt.preventDefault();

			$notice.fadeTo( 100, 0, () => {
				$notice.slideUp( 100, () => {
					$notice.remove();
				} );
			} );

		} );

		if ( autoDismiss ) {
			setTimeout( () => {
				$dismissButton.trigger( 'click.wp-dismiss-notice' );
			}, dismissSeconds * 1000 );
		}
		
	},
	
	/**
	 * Defer the execution until all the images have been loaded
	 *
	 * @param {JQuery} $wrapper
	 *
	 * @return {JQueryPromise<any>}
	 */
	imagesLoaded( $wrapper: JQuery ): JQueryPromise<any> {

		// Get all the images (excluding those with no src attribute).
		const $imgs: JQuery = $wrapper.find( 'img[src!=""]' );

		// If there's no images, just return an already resolved promise.
		if ( ! $imgs.length ) {
			return $.Deferred().resolve().promise();
		}

		// For each image, add a deferred object to the array which resolves when the image is loaded (or if loading fails)
		let dfds = [];
		$imgs.each( ( index: number, elem: Element ) => {

			let dfd: any = $.Deferred(),
			    img: any = new Image();

			dfds.push( dfd );

			img.onload = () => dfd.resolve();
			img.onerror = () => dfd.resolve();
			img.src = $( elem ).attr( 'src' );
			
		});
		
		// Return a master promise object which will resolve when all the deferred objects have resolved
		// IE - when all the images are loaded
		return $.when.apply( $, dfds );
		
	},
	
	/**
	 * Helper to get parameters from the URL
	 *
	 * @param {string} name
	 *
	 * @return {string|string[]}
	 */
	getUrlParameter( name: string ): string|string[] {

		if ( typeof URLSearchParams !== 'undefined' ) {
			const urlParams = new URLSearchParams( window.location.search );
			return urlParams.get( name );
		}
		// Deprecated: Only for old browsers non-supporting URLSearchParams.
		else {

			name = name.replace( /[\[]/, '\\[' ).replace( /[\]]/, '\\]' );
			const regex: RegExp     = new RegExp( '[\\?&]' + name + '=([^&#]*)' ),
			      results: string[] = regex.exec( window.location.search );

			return results === null ? '' : decodeURIComponent( results[ 1 ].replace( /\+/g, ' ' ) );

		}

	},

	/**
	 * Converts a query string to an object of params
	 *
	 * @param {string} query
	 *
	 * @return {any}
	 */
	getQueryParams( query: string ): any {

		let params: any = {};

		new URLSearchParams( query ).forEach( ( value: string, key: string ) => {

			let decodedKey: string = decodeURIComponent( key );
			let decodedValue: string = decodeURIComponent( value );

			// This key is part of an array
			if ( decodedKey.endsWith( '[]' ) ) {
				decodedKey = decodedKey.replace( '[]', '' );
				params[ decodedKey ] || ( params[ decodedKey ] = [] );
				params[ decodedKey ].push( decodedValue );
			}
			// Just a regular parameter
			else {
				params[ decodedKey ] = decodedValue;
			}

		} );

		return params;

	},
	
	/**
	 * Get a sanitized HTML code and returns valid HTML code
	 *
	 * @param {string} input
	 *
	 * @return {string}
	 */
	htmlDecode( input: string ): string {

		const e: HTMLElement = document.createElement( 'div' );
		e.innerHTML = input;

		return e.childNodes[ 0 ].nodeValue;

	},
	
	/**
	 * Check whether 2 distinct objects are equivalent (have the same keys and values)
	 *
	 * @param {any}     a       The first object to compare.
	 * @param {any}     b       The second object to compare.
	 * @param {boolean} strict  Optional. Whether to compare strictly.
	 *
	 * @return {boolean}
	 */
	areEquivalent( a: any, b: any, strict: boolean = false ): boolean {
		
		// Create arrays of property names.
		const aProps: string[] = Object.getOwnPropertyNames(a),
			  bProps: string[] = Object.getOwnPropertyNames(b);
		
		// If number of properties is different, objects are not equivalent.
		if ( aProps.length != bProps.length ) {
			return false;
		}

		for ( let i = 0; i < aProps.length; i++ ) {
			const propName: string = aProps[ i ];

			// If values of same property are not equal, objects are not equivalent.
			if ( ( strict && a[ propName ] !== b[ propName ] ) || ( ! strict && a[ propName ] != b[ propName ] ) ) {
				return false;
			}
		}
		
		// If we made it this far, objects are considered equivalent.
		return true;

	},
	
	/**
	 * Toggle tree nodes
	 *
	 * @param {any[]}  nodes
	 * @param {string} openOrClose
	 */
	toggleNodes( nodes: any[], openOrClose: string ) {

		for ( let i: number = 0; i < nodes.length; i++ ) {

			nodes[ i ].isExpanded = openOrClose == 'open'; // Either expand node or don't

			// If has children open/close those as well.
			if ( nodes[ i ].children && nodes[ i ].children.length > 0 ) {
				this.toggleNodes( nodes[ i ].children, openOrClose );
			}

		}

	},
	
	/**
	 * Format a number, with comma-separated thousands and custom precision/decimal places.
	 * Based on accounting.js.
	 *
	 * Localise by overriding the precision and thousand / decimal separators.
	 *
	 * @param {number}  number
	 * @param {number}  precision
	 * @param {string}  thousand
	 * @param {string}  decimal
	 * @param {boolean} stripZeros
	 *
	 * @return {string[] | string}
	 */
	formatNumber(
		number: number[] | number,
		precision: number = this.settings.number.precision,
		thousand: string  = this.settings.number.thousand,
		decimal: string   = this.settings.number.decimal,
		stripZeros: boolean = false,
	): string[] | string {
		
		// Resursively format arrays.
		if ( Array.isArray( number ) ) {
			return $.map( number, val => this.formatNumber( val, precision, thousand, decimal, stripZeros ) );
		}
		
		// Clean up number.
		number = this.unformat( number );

		const usePrecision = this.checkPrecision( precision ), // Clean up precision.
		      negative     = number < 0 ? '-' : '', // Do some calc.
		      base         = parseInt( this.toFixed( Math.abs( <number> number || 0 ), usePrecision ), 10 ) + '',
		      mod          = base.length > 3 ? base.length % 3 : 0;

		let decimalsPart: string = '';

		if ( usePrecision ) {

			decimalsPart = this.toFixed( Math.abs( <number>number ), usePrecision );

			// Check whether to strip trailing zeros from decimals.
			if ( stripZeros ) {
				decimalsPart = Number( decimalsPart ).toString();
			}

			decimalsPart = decimalsPart.includes( '.' ) ? decimal + decimalsPart.split( '.' )[1] : '';

		}

		// Format the number.
		return negative + ( mod ? base.substr( 0, mod ) + thousand : '' ) + base.substr( mod ).replace( /(\d{3})(?=\d)/g, '$1' + thousand ) + decimalsPart;
		
	},
	
	/**
	 * Format a number into currency
	 * Based on accounting.js.
	 *
	 * Usage: Utils.formatMoney( number, symbol, precision, thousandsSep, decimalSep, format )
	 * defaults: (0, '$', 2, ',', '.', '%s%v')
	 *
	 * Localise by overriding the symbol, precision, thousand / decimal separators and format
	 * Second param can be an object matching `settings.currency` which is the easiest way.
	 */
	formatMoney(
		number: number[] | number,
		symbol: string    = this.settings.currency.symbol,
		precision: number = this.settings.currency.precision,
		thousand: string  = this.settings.currency.thousand,
		decimal: string   = this.settings.currency.decimal,
		format: string    = this.settings.currency.format,
	): string[] | string {
		
		// Resursively format arrays.
		if ( Array.isArray( number ) ) {
			return $.map( number, val => this.formatMoney( val, symbol, precision, thousand, decimal, format ) );
		}
		
		// Clean up number.
		number = this.unformat( number );

		const formats   = this.checkCurrencyFormat( format ), // Check format (returns object with pos, neg and zero).
		      useFormat = number > 0 ? formats.pos : number < 0 ? formats.neg : formats.zero; // Choose which format to use for this value.

		// Return with currency symbol added.
		return useFormat.replace( '%s', symbol ).replace( '%v', this.formatNumber( Math.abs( <number>number ), this.checkPrecision( precision ), thousand, decimal ) );
		
	},
	
	/**
	 * Takes a string/array of strings, removes all formatting/cruft and returns the raw float value
	 * Based on accounting.js.
	 *
	 * Decimal must be included in the regular expression to match floats (defaults to
	 * Utils.settings.number.decimal), so if the number uses a non-standard decimal
	 * separator, provide it as the second argument.
	 *
	 * Also matches bracketed negatives (eg. "$ (1.99)" => -1.99)
	 *
	 * Doesn't throw any errors (`NaN`s become 0) but this may change in future
	 *
	 * @param {number | string} value
	 * @param {string} decimal
	 *
	 * @return {number | number[]}
	 */
	unformat( value: number | string, decimal: string = this.settings.number.decimal ): number[] | number {
		
		// Recursively unformat arrays:
		if ( Array.isArray( value ) ) {
			return $.map( value, val => this.unformat( val, decimal ) );
		}
		
		// Fails silently (need decent errors).
		value = value || 0;
		
		// Return the value as-is if it's already a number.
		if ( typeof value === 'number' ) {
			return value;
		}
		
		// Build regex to strip out everything except digits, decimal point and minus sign.
		const regex: RegExp       = new RegExp( `[^0-9-${ decimal }]`, 'g' ),
		      unformatted: number = parseFloat(
			      ( '' + value )
				      .replace( /\((.*)\)/, '-$1' ) // replace bracketed values with negatives
				      .replace( regex, '' )         // strip out any cruft
				      .replace( decimal, '.' ),     // make sure decimal point is standard
		      );
		
		// This will fail silently which may cause trouble, let's wait and see.
		return ! isNaN( unformatted ) ? unformatted : 0;
		
	},
	
	/**
	 * Check and normalise the value of precision (must be positive integer).
	 * Based on accounting.js.
	 *
	 * @param {number} val
	 * @param {number} base
	 *
	 * @return {number}
	 */
	checkPrecision( val: number, base: number = 0 ): number {
		val = Math.round( Math.abs( val ) );
		return isNaN( val ) ? base : val;
	},
	
	/**
	 * Implementation of toFixed() that treats floats more like decimals.
	 * Based on accounting.js.
	 *
	 * Fixes binary rounding issues (eg. (0.615).toFixed(2) === "0.61") that present
	 * problems for accounting and finance-related software.
	 *
	 * @param {number} value
	 * @param {number} precision
	 *
	 * @return {string}
	 */
	toFixed( value: number, precision: number ): string {
		precision = this.checkPrecision( precision, this.settings.number.precision );
		const power = Math.pow( 10, precision );
		
		// Multiply up by precision, round accurately, then divide and use native toFixed().
		return ( Math.round( this.unformat( value ) * power ) / power ).toFixed( precision );
	},
	
	/**
	 * Parses a format string or object and returns format obj for use in rendering.
	 * Based on accounting.js.
	 *
	 * `format` is either a string with the default (positive) format, or object
	 * containing `pos` (required), `neg` and `zero` values (or a function returning
	 * either a string or object)
	 *
	 * Either string or format.pos must contain '%v' (value) to be valid
	 */
	checkCurrencyFormat( format: any | string ): any | string {
		
		const defaults = this.settings.currency.format;
		
		// Allow function as format parameter (should return string or object).
		if ( typeof format === 'function' ) {
			format = format();
		}
		// Format can be a string, in which case `value` ('%v') must be present.
		else if ( typeof format === 'string' && format.match('%v') ) {
			
			// Create and return positive, negative and zero formats.
			return {
				pos : format,
				neg : format.replace( '-', '' ).replace( '%v', '-%v' ),
				zero: format,
			};
			
		}
		// If no format, or object is missing valid positive value, use defaults.
		else if ( ! format || ! format.pos || ! format.pos.match( '%v' ) ) {
			
			// If defaults is a string, casts it to an object for faster checking next time.
			return ( typeof defaults !== 'string' ) ? defaults : this.settings.currency.format = {
				pos : defaults,
				neg : defaults.replace( '%v', '-%v' ),
				zero: defaults,
			};
			
		}
		
		// Otherwise, assume format was fine.
		return format;
		
	},

	/**
	 * Check the parameter is numeric
	 * NOTE: Previously we were using the jQuery.isNumeric() function but has been deprecated
	 *
	 * @param {any} n
	 *
	 * @return {boolean}
	 */
	isNumeric( n: any ): boolean {
		return !isNaN( parseFloat( n ) ) && isFinite( n );
	},

	/**
	 * Convert an array of JQuery elements to string
	 *
	 * @param {JQuery} $elems
	 *
	 * @return {string}
	 */
	convertElemsToString( $elems: JQuery ): string {
		return $('<div />').append( $elems ).html();
	},

	/**
	 * Merge 2 arrays and return the result (this ensures the resulting arrar has no repeated values)
	 *
	 * @param {any[]} arr1
	 * @param {any[]} arr2
	 *
	 * @return {any[]}
	 */
	mergeArrays( arr1: any[], arr2: any[] ): any[] {
		return Array.from( new Set( [ ...arr1, ...arr2 ] ) );
	},

	/**
	 * Restrict an input number field's values to fit within its allowed range
	 *
	 * @param {JQuery} $input
	 */
	restrictNumberInputValues( $input: JQuery ) {

		if ( $input.attr( 'type' ) !== 'number' ) {
			return; // Only input numbers are allowed.
		}

		const qty: string = $input.val();

		// Make sure the value entered is within the allowed range.
		const value: number              = parseFloat( qty || '0' ),
		      minAtt: string | undefined = $input.attr( 'min' ),
		      maxAtt: string | undefined = $input.attr( 'max' ),
		      min: number                = parseFloat( minAtt || '0' ),
		      max: number                = parseFloat( maxAtt || '0' );

		if ( ! $.isNumeric( qty ) ) {
			$input.val( undefined !== minAtt && ! isNaN( min ) && min > 0 ? min : 0 ); // Set to 0 or min (the greater).
		}
		else if ( undefined !== minAtt && value < min ) {
			$input.val( min ); // Change to min.
		}
		else if ( undefined !== maxAtt && value > max ) {
			$input.val( max ); // Change to max.
		}

	},

	/**
	 * Check if the RTL mode is active and add some options on some cases
	 *
	 * @param {string} value
	 *
	 * @return {string | boolean}
	 */
	checkRTL( value: string ): string|boolean {

		let isRTL: boolean = false;

		if ( $( 'html[ dir="rtl" ]' ).length > 0 ) {
			isRTL = true;
		}

		switch( value ) {
			case 'isRTL':
			case 'reverse':
				return isRTL;
				break;

			case 'xSide':
				if (isRTL) {
					return 'right';
				}
				else {
					return 'left';
				}
				break;

			default:
				return false;
				break;

		}

	},

	/**
	 * Multiply a decimal number and return the right value
	 *
	 * @param {number} multiplicand
	 * @param {number} multiplier
	 *
	 * @returns {number}
	 */
	multiplyDecimal( multiplicand: number, multiplier: number ): number {
		return parseFloat( bigDecimal.multiply( multiplicand.toString(), multiplier.toString() ) );
	},

	/**
	 * Divide a decimal number and return the right value
	 *
	 * @param {number} dividend
	 * @param {number} divisor
	 * @param {number} precision
	 *
	 * @returns {number}
	 */
	divideDecimal( dividend: number, divisor: number, precision: number ): number {
		return parseFloat( bigDecimal.divide( dividend.toString(), divisor.toString(), precision ) );
	},

	/**
	 * Sum two decimal numbers and return the right value
	 *
	 * @param {number} summand1
	 * @param {number} summand2
	 *
	 * @returns {number}
	 */
	sumDecimal( summand1: number, summand2: number ): number {
		return parseFloat( bigDecimal.add( summand1.toString(), summand2.toString() ) );
	},

	/**
	 * Subtract a decimal number to another and return the right value
	 *
	 * @param {number} minuend
	 * @param {number} subtrahend
	 *
	 * @returns {number}
	 */
	subtractDecimal( minuend: number, subtrahend: number ): number {
		return parseFloat( bigDecimal.subtract( minuend.toString(), subtrahend.toString() ) );
	},

	/**
	 * Calc a base price taxes. Based on WC_Tax::calc_exclusive_tax as we have a price without applied taxes.
	 *
	 * @param {number} price
	 * @param {any[]} rates
	 *
	 * @return {number}
	 */
	calcTaxesFromBase( price: number, rates: any[] ): number {

		let taxes: number[] = [ 0 ],
		    preCompoundTaxes: number;

		$.each( rates, ( i: number, rate: any ) => {

			if ( 'yes' === rate[ 'compound' ] ) {
				return true;
			}
			taxes.push( price * rate[ 'rate' ] / 100 );

		} );

		preCompoundTaxes = taxes.reduce( ( a: number, b: number ) => a + b, 0 );

		// Compound taxes.
		$.each( rates, ( i: number, rate: any ) => {

			let currentTax: number;

			if ( 'no' === rate[ 'compound' ] ) {
				return true;
			}

			currentTax = ( price + preCompoundTaxes ) * rate[ 'rate' ] / 100;
			taxes.push( currentTax );
			preCompoundTaxes += currentTax;

		} );

		return taxes.reduce( ( a: number, b: number ) => a + b, 0 );
	},

	/**
	 * Detect clicks on pseudo-elements
	 *
	 * @param {JQueryEventObject}       evt         The click event fired on the parent element.
	 * @param {JQuery}                  $parentElem The parent element to which the pseudo-element belongs.
	 * @param {'before'|'after'|'both'} pseudoElem  Which pseudo-element to check.
	 *
	 * @return {{before: boolean, after: boolean} | boolean}
	 */
	pseudoClick( evt: JQueryEventObject, $parentElem: JQuery, pseudoElem: 'before'|'after'|'both' = 'both' ): { before: boolean, after: boolean } | boolean {

		let beforeClicked: boolean = false,
		    afterClicked: boolean  = false;

		const parentElem: HTMLElement = <HTMLElement>$parentElem.get( 0 ),
		      parentLeft: number      = parseInt( parentElem.getBoundingClientRect().left.toString(), 10 ),
		      parentTop: number       = parseInt( parentElem.getBoundingClientRect().top.toString(), 10 );

		const mouseX: number = evt.clientX,
		      mouseY: number = evt.clientY;

		if ( [ 'before', 'both' ].includes( pseudoElem ) ) {

			const before: CSSStyleDeclaration = window.getComputedStyle( parentElem, ':before' ),
			      beforeStart: number         = parentLeft + ( parseInt( before.getPropertyValue( 'left' ), 10 ) ),
			      beforeEnd: number           = beforeStart + parseInt( before.width, 10 ),
			      beforeYStart: number        = parentTop + ( parseInt( before.getPropertyValue( 'top' ), 10 ) ),
			      beforeYEnd: number          = beforeYStart + parseInt( before.height, 10 );

			beforeClicked = mouseX >= beforeStart && mouseX <= beforeEnd && mouseY >= beforeYStart && mouseY <= beforeYEnd;

		}

		if ( [ 'after', 'both' ].includes( pseudoElem ) ) {

			const after: CSSStyleDeclaration = window.getComputedStyle( parentElem, ':after' ),
			      afterStart: number         = parentLeft + ( parseInt( after.getPropertyValue( 'left' ), 10 ) ),
			      afterEnd: number           = afterStart + parseInt( after.width, 10 ),
			      afterYStart: number        = parentTop + ( parseInt( after.getPropertyValue( 'top' ), 10 ) ),
			      afterYEnd: number          = afterYStart + parseInt( after.height, 10 );

			afterClicked = mouseX >= afterStart && mouseX <= afterEnd && mouseY >= afterYStart && mouseY <= afterYEnd;

		}

		switch ( pseudoElem ) {
			case 'after':
				return afterClicked;

			case 'before':
				return beforeClicked;

			default:
				return {
					before: beforeClicked,
					after : afterClicked,
				};
		}

	},

	/**
	 * Check whether the passed element is visible in the viewport
	 *
	 * @param {HTMLElement} el
	 *
	 * @return {boolean}
	 */
	isElementInViewport( el: HTMLElement ) {

		const rect: DOMRect = el.getBoundingClientRect();

		return (
			rect.top >= 0 &&
			rect.left >= 0 &&
			rect.bottom + 80 <= window.innerHeight && // add 80 to get the text right
			rect.right <= window.innerWidth
		);

	},

};

export default Utils;
