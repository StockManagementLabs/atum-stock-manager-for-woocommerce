/* ====================
   UTILS
   ==================== */


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
		currency: {
			symbol : '$',
			format : '%s%v',	// controls output: %s = symbol, %v = value (can be object).
			decimal : '.',		// decimal point separator.
			thousand : ',',		// thousands separator.
			precision : 2,		// decimal places.
			grouping : 3		// digit grouping (not implemented yet).
		},
	},
	
	/**
	 * Apply a delay
	 *
	 * @return {Function}
	 */
	delay( callback: Function, ms: number ) {
		
		clearTimeout( this.settings.delayTimer );
		this.settings.delayTimer = setTimeout( callback, ms );
		
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
	filterQuery(query: string, variable: string): string|boolean {
		
		const vars = query.split('&');
		
		for (let i = 0; i < vars.length; i++) {
			
			const pair = vars[i].split('=');
			
			if (pair[0] === variable) {
				return pair[1];
			}
			
		}
		
		return false;
		
	},
	
	filterByData($elem: JQuery, prop: string, val: any): JQuery {
		
		if (typeof val === 'undefined') {
			return $elem.filter( (index: number, elem: Element) => {
				return typeof $(elem).data(prop) !== 'undefined'
			});
		};
		
		return $elem.filter( (index: number, elem: Element) => {
			return $(elem).data(prop) == val
		});
		
	},
	
	/**
	 * Add a notice on top identical to the WordPress' admin notices
	 *
	 * @param {string} type The notice type. Can be "updated" or "error".
	 * @param {string} msg  The message.
	 */
	addNotice(type: string, msg: string) {
		
		let $notice: JQuery        = $('<div class="' + type + ' notice is-dismissible"><p><strong>' + msg + '</strong></p></div>').hide(),
		    $dismissButton: JQuery = $('<button />', {type: 'button', class: 'notice-dismiss'}),
		    $headerEnd: JQuery     = $('.wp-header-end');
		
		$headerEnd.siblings('.notice').remove();
		$headerEnd.before($notice.append($dismissButton));
		$notice.slideDown(100);
		
		$dismissButton.on('click.wp-dismiss-notice', (evt: any) => {
			
			evt.preventDefault();
			
			$notice.fadeTo(100, 0, () => {
				$notice.slideUp(100, () => {
					$notice.remove();
				});
			});
			
		});
		
	},
	
	/**
	 * Defer the execution until all the images have been loaded
	 *
	 * @param {JQuery} $wrapper
	 *
	 * @return {JQueryPromise<any>}
	 */
	imagesLoaded($wrapper: JQuery): JQueryPromise<any> {
		
		// Get all the images (excluding those with no src attribute).
		let $imgs: JQuery = $wrapper.find('img[src!=""]');
		
		// If there's no images, just return an already resolved promise.
		if (!$imgs.length) {
			return $.Deferred().resolve().promise();
		}
		
		// For each image, add a deferred object to the array which resolves when the image is loaded (or if loading fails)
		let dfds = [];
		$imgs.each(function() {
			
			let dfd: any = $.Deferred(),
			    img: any = new Image();
			
			dfds.push(dfd);
			
			img.onload = function() {
				dfd.resolve();
			}
			
			img.onerror = function() {
				dfd.resolve();
			}
			
			img.src = this.src;
			
		});
		
		// Return a master promise object which will resolve when all the deferred objects have resolved
		// IE - when all the images are loaded
		return $.when.apply($, dfds);
		
	},
	
	/**
	 * Helper to get parameters from the URL
	 *
	 * @param {string} name
	 *
	 * @return {string}
	 */
	getUrlParameter(name: string) {
		
		if (typeof URLSearchParams !== 'undefined') {
			
			const urlParams = new URLSearchParams(window.location.search);
			
			return urlParams.get(name);
			
		}
		// Deprecated: Only for old browsers non supporting URLSearchParams.
		else {
			
			name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
			const regex: RegExp     = new RegExp('[\\?&]' + name + '=([^&#]*)'),
			      results: string[] = regex.exec(window.location.search);
			
			return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
		}
		
	},
	
	/**
	 * Get a sanitized HTML code and returns valid HTML code
	 *
	 * @param {string} input
	 *
	 * @return {string}
	 */
	htmlDecode(input: string) {
		
		const e: HTMLElement = document.createElement('div');
		e.innerHTML = input;
		
		return e.childNodes[0].nodeValue;
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
	areEquivalent(a: any, b: any, strict: boolean = false) {
		
		// Create arrays of property names.
		const aProps: string[] = Object.getOwnPropertyNames(a),
			  bProps: string[] = Object.getOwnPropertyNames(b);
		
		// If number of properties is different, objects are not equivalent.
		if (aProps.length != bProps.length) {
			return false;
		}
		
		for (let i = 0; i < aProps.length; i++) {
			const propName: string = aProps[i];
			
			// If values of same property are not equal, objects are not equivalent.
			if ( (strict && a[propName] !== b[propName]) || (!strict && a[propName] != b[propName]) ) {
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
	toggleNodes(nodes: any[], openOrClose: string){
		
		for (let i: number = 0; i < nodes.length; i++) {
			
			nodes[i].isExpanded = openOrClose == 'open'; // Either expand node or don't
			
			// If has children open/close those as well.
			if (nodes[i].children && nodes[i].children.length > 0) {
				this.toggleNodes(nodes[i].children, openOrClose);
			}
			
		}
		
	},
	
	/**
	 * Format a number, with comma-separated thousands and custom precision/decimal places.
	 * Based on accounting.js.
	 *
	 * Localise by overriding the precision and thousand / decimal separators.
	 *
	 * @param {number} number
	 * @param {number} precision
	 * @param {string} thousand
	 * @param {string} decimal
	 *
	 * @return {string[] | string}
	 */
	formatNumber( number: number[] | number, precision?: number, thousand?: string, decimal?: string ): string[] | string {
		
		// Resursively format arrays.
		if ( Array.isArray( number ) ) {
			return $.map( number, val => this.formatNumber( val, precision, thousand, decimal ) );
		}
		
		// Clean up number.
		number = this.unformat( number );
		
		const defaults: any = { ...this.settings.number },
		      // Prevent undefined decimals.
		      paramOpts: any = typeof decimal === 'undefined' ? { precision: precision, thousand: thousand } : { precision: precision, thousand: thousand, decimal: decimal },
		      opts: any     = { ...defaults, ...paramOpts },
		      // Clean up precision.
		      usePrecision  = this.checkPrecision( opts.precision ),
		      // Do some calc.
		      negative      = number < 0 ? '-' : '',
		      base          = parseInt( this.toFixed( Math.abs( <number>number || 0 ), usePrecision ), 10 ) + '',
		      mod           = base.length > 3 ? base.length % 3 : 0;
		
		
		// Format the number.
		return negative + ( mod ? base.substr( 0, mod ) + opts.thousand : '' ) + base.substr( mod ).replace( /(\d{3})(?=\d)/g, '$1' + opts.thousand ) + ( usePrecision ? opts.decimal + this.toFixed( Math.abs( <number>number ), usePrecision ).split( '.' )[ 1 ] : '' );
		
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
	formatMoney( number: number[] | number, symbol?: string, precision?: number, thousand?: string, decimal?: string, format?: string ) : string[] | string {
		
		// Resursively format arrays.
		if ( Array.isArray( number ) ) {
			return $.map( number, val => this.formatMoney( val, symbol, precision, thousand, decimal, format ) );
		}
		
		// Clean up number.
		number = this.unformat(number);
		
		const defaults: any = { ...this.settings.currency },
		      opts: any     = {
			      defaults,
			      ...{
				      symbol: symbol,
				      precision: precision,
				      thousand: thousand,
				      decimal: decimal,
				      format: format,
			      },
		      },
		      // Check format (returns object with pos, neg and zero).
		      formats       = this.checkCurrencyFormat( opts.format ),
		      // Choose which format to use for this value.
		      useFormat     = number > 0 ? formats.pos : number < 0 ? formats.neg : formats.zero;
		
		// Return with currency symbol added.
		return useFormat.replace( '%s', opts.symbol ).replace( '%v', this.formatNumber( Math.abs( <number>number ), this.checkPrecision( opts.precision ), opts.thousand, opts.decimal ) );
		
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
	unformat( value: number | string, decimal?: string ): number[] | number {
		
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
		
		// Default decimal point comes from settings, but could be set to eg. "," in opts.
		decimal = decimal || this.settings.number.decimal;
		
		// Build regex to strip out everything except digits, decimal point and minus sign.
		const regex       = new RegExp( `[^0-9-${ decimal }]`, 'g' ),
		      unformatted = parseFloat(
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
		return Array.from( new Set([ ...arr1, ...arr2 ]) );
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
		const value: number = parseFloat( qty || '0' ),
		      min: number   = parseFloat( $input.attr( 'min' ) || '0' ),
		      max: number   = parseFloat( $input.attr( 'max' ) || '0' );

		if ( value < min ) {
			$input.val( min ); // Change to min.
		}
		else if ( value > max ) {
			$input.val( max ); // Change to max.
		}
		else if ( qty === '' ) {
			$input.val( 0 ); // Set to 0.
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
				break;

		}

	}
	
}

export default Utils;
