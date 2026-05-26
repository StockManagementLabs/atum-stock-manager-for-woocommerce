/* =======================================
   DATE TIME PICKER
   ======================================= */

/**
 * Third party plugins
 */
import { DateTime, Namespace, TempusDominus } from '@eonasdan/tempus-dominus';
import type Options from '@eonasdan/tempus-dominus/types/utilities/options';

import Settings from '../config/_settings';

type LegacyDateInput = Date | DateTime | string | boolean | null | undefined;

type LegacyDateTimePickerOptions = {
	format?: string;
	dateFormat?: string;
	useCurrent?: boolean;
	showClose?: boolean;
	showClear?: boolean;
	showTodayButton?: boolean;
	keepInvalid?: boolean;
	minDate?: LegacyDateInput;
	maxDate?: LegacyDateInput;
	stepping?: number;
	sideBySide?: boolean;
	widgetPositioning?: {
		vertical?: 'top' | 'bottom' | 'auto';
		horizontal?: 'left' | 'right' | 'auto';
	};
	icons?: Record<string, string>;
	tooltips?: Record<string, string>;
	locale?: string;
};

type DateTimePickerEventDetail = {
	date?: DateTime;
	oldDate?: DateTime;
	isClear?: boolean;
};

class TempusDominusAdapter {
	private pickedDate?: DateTime;

	constructor(
		private element: HTMLElement,
		private picker: TempusDominus,
		private optionsRef: Options,
		private legacyFormat: string,
		private tempusFormat: string,
	) {}

	hide() {
		this.picker.hide();
	}

	destroy() {
		this.picker.dispose();
	}

	options() {
		return {
			...this.optionsRef,
			format: this.legacyFormat,
		};
	}

	date( newDate?: LegacyDateInput ) {
		if ( arguments.length ) {
			const normalizedDate = DateTimePicker.normalizeDateValue( newDate, this.tempusFormat );

			this.setNativeDate( normalizedDate );
		}

		return this.pickedDate || DateTimePicker.normalizeDateValue( this.getInput()?.value, this.tempusFormat ) || this.picker.dates.lastPicked || false;
	}

	minDate( newDate?: LegacyDateInput ) {
		if ( arguments.length ) {
			this.optionsRef.restrictions = {
				...( this.optionsRef.restrictions || {} ),
				minDate: DateTimePicker.normalizeDateValue( newDate, this.tempusFormat ),
			};

			this.picker.updateOptions( { restrictions: this.optionsRef.restrictions } );
		}

		return this.optionsRef.restrictions?.minDate || false;
	}

	maxDate( newDate?: LegacyDateInput ) {
		if ( arguments.length ) {
			this.optionsRef.restrictions = {
				...( this.optionsRef.restrictions || {} ),
				maxDate: DateTimePicker.normalizeDateValue( newDate, this.tempusFormat ),
			};

			this.picker.updateOptions( { restrictions: this.optionsRef.restrictions } );
		}

		return this.optionsRef.restrictions?.maxDate || false;
	}

	syncPickedDate( date?: DateTime ) {
		if ( date ) {
			this.pickedDate = date;
		}
		else {
			this.pickedDate = undefined;
		}
	}

	syncVisibleSelection( updateViewDate: boolean = true ) {
		const selectedDate = this.date() || undefined;

		this.syncPickedDate( selectedDate );

		if ( DateTimePicker.isUsableDateTime( selectedDate ) && updateViewDate ) {
			const viewDate = selectedDate.clone;

			viewDate.setLocalization( this.optionsRef.localization );
			this.picker.viewDate = viewDate;
		}
	}

	configureInputBridge() {
		this.picker.dates.parseInput = ( value: LegacyDateInput ): DateTime => {
			const parsedDate = DateTimePicker.normalizeDateValue( value, this.tempusFormat );

			return parsedDate as DateTime;
		};

		this.picker.dates.formatInput = ( date?: DateTime ): string => date ? date.format( this.tempusFormat ) : '';
	}

	private setNativeDate( date: DateTime | undefined ) {
		this.picker.dates.setValue( date, date ? this.picker.dates.lastPickedIndex : undefined );
		this.syncPickedDate( date );
	}

	private getInput(): HTMLInputElement | undefined {
		if ( this.element instanceof HTMLInputElement ) {
			return this.element;
		}

		return this.element.querySelector( 'input' ) as HTMLInputElement;
	}

}

export default class DateTimePicker {
	
	defaults: LegacyDateTimePickerOptions = {};

	constructor(
		private settings: Settings,
	) {

		const langs: string[] = [ 'haz', 'as', 'ar', 'as', 'azb', 'bo', 'dz', 'fa', 'gu', 'he', 'hi', 'hy', 'ka', 'kk', 'km', 'kn', 'ko', 'ku', 'lo', 'ml', 'mr', 'my', 'ne', 'pa', 'ps', 'sd', 'si', 'skr', 'ta', 'ur' ];
		const mylang: string  = langs.includes( this.settings.get( 'calendarLocale' ) ) ? 'en' : this.settings.get( 'calendarLocale' );

		const today = new Date();

		today.setHours( 0, 0, 0, 0 );

		this.defaults = {
			format           : this.settings.get( 'dateFormat' ),
			useCurrent       : false,
			showClose        : true,
			icons            : {
				time    : 'atum-icon atmi-clock',
				date    : 'atum-icon atmi-calendar-full',
				up      : 'atum-icon atmi-chevron-up',
				down    : 'atum-icon atmi-chevron-down',
				previous: 'atum-icon atmi-chevron-left',
				next    : 'atum-icon atmi-chevron-right',
				today   : 'atum-icon atmi-frame-expand',
				clear   : 'atum-icon atmi-trash',
				close   : 'atum-icon atmi-ok',
			},
			minDate          : today, // By default, we are not allowing to select dates before today.
			showClear        : true,
			showTodayButton  : true,
			widgetPositioning: {
				horizontal: 'right',
				vertical  : 'bottom',
			},
			tooltips         : {
				today          : this.settings.get( 'goToToday' ),
				clear          : this.settings.get( 'clearSelection' ),
				close          : this.settings.get( 'closePicker' ),
				selectMonth    : this.settings.get( 'selectMonth' ),
				prevMonth      : this.settings.get( 'prevMonth' ),
				nextMonth      : this.settings.get( 'nextMonth' ),
				selectYear     : this.settings.get( 'selectYear' ),
				prevYear       : this.settings.get( 'prevYear' ),
				nextYear       : this.settings.get( 'nextYear' ),
				selectDecade   : this.settings.get( 'selectDecade' ),
				prevDecade     : this.settings.get( 'prevDecade' ),
				nextDecade     : this.settings.get( 'nextDecade' ),
				prevCentury    : this.settings.get( 'prevCentury' ),
				nextCentury    : this.settings.get( 'nextCentury' ),
				incrementHour  : this.settings.get( 'incrementHour' ),
				pickHour       : this.settings.get( 'pickHour' ),
				decrementHour  : this.settings.get( 'decrementHour' ),
				incrementMinute: this.settings.get( 'incrementMinute' ),
				pickMinute     : this.settings.get( 'pickMinute' ),
				decrementMinute: this.settings.get( 'decrementMinute' ),
				incrementSecond: this.settings.get( 'incrementSecond' ),
				pickSecond     : this.settings.get( 'pickSecond' ),
				decrementSecond: this.settings.get( 'decrementSecond' ),
			},
			locale           : mylang || 'en',
		};

	}
	
	/**
	 * Add the date time pickers.
	 *
	 * @param {JQuery} $selector
	 * @param {any}    opts
	 */
	addDateTimePickers( $selector: JQuery, opts: LegacyDateTimePickerOptions = {} ) {

		$selector.each( ( index: number, elem: Element ) => {

			const $dateTimePicker: any = $( elem );
			const data: LegacyDateTimePickerOptions = $dateTimePicker.data() || {};

			// If the current element has a DateTimePicker attached, destroy it first.
			if ( $dateTimePicker.data( 'DateTimePicker' ) ) {
				this.destroyDateTimePickers( $dateTimePicker );
			}

			const legacyOptions = {
				...this.defaults,
				...data,
				...opts,
			};
			const legacyFormat = legacyOptions.format || legacyOptions.dateFormat || this.settings.get( 'dateFormat' );
			const tempusFormat = DateTimePicker.toTempusFormat( legacyFormat );
			const tempusOptions = this.toTempusDominusOptions( legacyOptions, tempusFormat );
			const $popoverContainer = $dateTimePicker.closest( '.popover' );

			if ( $popoverContainer.length ) {
				tempusOptions.container = $popoverContainer.get( 0 ) as HTMLElement;
			}

			const input = elem instanceof HTMLInputElement ? elem : elem.querySelector( 'input' );
			const initialValue = input?.value;
			const initialDate = DateTimePicker.normalizeDateValue( initialValue, tempusFormat );

			if ( initialDate ) {
				tempusOptions.viewDate = initialDate;
			}

			// Tempus parses the input during construction before we can patch
			// parseInput/formatInput. Avoid that first native parse and restore it
			// through ATUM's stricter bridge after the instance exists.
			if ( input && initialValue ) {
				input.value = '';
			}

			const picker = new TempusDominus( elem as HTMLElement, tempusOptions );
			const adapter = new TempusDominusAdapter( elem as HTMLElement, picker, tempusOptions, legacyFormat, tempusFormat );

			adapter.configureInputBridge();

			if ( input && initialValue ) {
				input.value = initialValue;

				if ( initialDate ) {
					adapter.date( initialDate );
				}
			}

			$dateTimePicker.data( 'DateTimePicker', adapter );

			picker.subscribe( Namespace.events.change, ( evt: DateTimePickerEventDetail ) => {
				adapter.syncPickedDate( evt.date );
				this.onDateTimePickerChange( evt, $dateTimePicker, tempusFormat );
			} );

			picker.subscribe( Namespace.events.show, () => {
				adapter.syncVisibleSelection();
				this.onDateTimePickerShow( $selector, $dateTimePicker );
			} );

		} );
		
	}

	/**
	 * Destroy the datepickers.
	 *
	 * @param {JQuery} $selector
	 */
	destroyDateTimePickers( $selector: JQuery ) {

		$selector.each( ( index: number, elem: Element ) => {

			const $dateTimePicker: JQuery = $( elem );
			const dateTimePicker: any = $dateTimePicker.data( 'DateTimePicker' );

			if ( typeof dateTimePicker !== 'undefined' ) {
				dateTimePicker.destroy();
				$dateTimePicker.removeData( 'DateTimePicker' );
			}

		} );
		
	}

	/**
	 * Check the min and max dates when it's a range field.
	 *
	 * @param {JQuery} $dpInput
	 */
	checkRange( $dpInput: JQuery ) {

		const dp: any = $dpInput.data( 'DateTimePicker' );

		// If the range min field has been changed.
		if ( $dpInput.data( 'range-max' ) ) {

			const $rangeMaxField: JQuery = $( $dpInput.data( 'range-max' ) );

			if ( $rangeMaxField.length ) {
				const rangeMaxDp = $rangeMaxField.data( 'DateTimePicker' );

				dp.maxDate( rangeMaxDp.date() || false );
			}

		}
		// If the range max field has been changed.
		else if ( $dpInput.data( 'range-min' ) ) {

			const $rangeMinField: JQuery = $( $dpInput.data( 'range-min' ) );

			if ( $rangeMinField.length ) {
				const rangeMinDp = $rangeMinField.data( 'DateTimePicker' );

				dp.minDate( rangeMinDp.date() || false );
			}

		}

	}

	/**
	 * Normalize old ATUM datepicker options to Tempus Dominus v6 options.
	 *
	 * @param {LegacyDateTimePickerOptions} legacyOptions
	 * @param {string}                      format
	 *
	 * @return {Options}
	 */
	private toTempusDominusOptions( legacyOptions: LegacyDateTimePickerOptions, format: string ): Options {
		const locale = legacyOptions.locale || this.settings.get( 'calendarLocale' ) || 'en';
		const minDate = DateTimePicker.normalizeDateValue( legacyOptions.minDate, format );
		const maxDate = DateTimePicker.normalizeDateValue( legacyOptions.maxDate, format );
		const hasTime = /[HhmsaA]/.test( format );
		const hasDate = /[DMY]/.test( format );

		return DateTimePicker.omitUndefinedOptions( {
			allowInputToggle: false,
			keepInvalid     : legacyOptions.keepInvalid,
			useCurrent      : legacyOptions.useCurrent,
			// Tempus uses this value in arithmetic. If undefined overrides its
			// default, selected dates become Invalid Date.
			stepping        : legacyOptions.stepping || 1,
			display         : {
				sideBySide: legacyOptions.sideBySide,
				placement : legacyOptions.widgetPositioning?.vertical === 'top' ? 'top' : 'bottom',
				theme     : 'light',
				buttons   : {
					today: legacyOptions.showTodayButton,
					clear: legacyOptions.showClear,
					close: legacyOptions.showClose,
				},
				components: {
					calendar: hasDate,
					date    : hasDate,
					month   : hasDate,
					year    : hasDate,
					decades : hasDate,
					clock   : hasTime,
					hours   : hasTime,
					minutes : hasTime,
					seconds : /s/.test( format ),
				},
				icons: {
					...legacyOptions.icons,
					type: 'icons',
				},
			},
			localization: {
				locale,
				format,
				dayViewHeaderFormat: { month: 'long', year: 'numeric' },
				clear            : legacyOptions.tooltips?.clear,
				close            : legacyOptions.tooltips?.close,
				today            : legacyOptions.tooltips?.today,
				selectMonth      : legacyOptions.tooltips?.selectMonth,
				previousMonth    : legacyOptions.tooltips?.prevMonth,
				nextMonth        : legacyOptions.tooltips?.nextMonth,
				selectYear       : legacyOptions.tooltips?.selectYear,
				previousYear     : legacyOptions.tooltips?.prevYear,
				nextYear         : legacyOptions.tooltips?.nextYear,
				selectDecade     : legacyOptions.tooltips?.selectDecade,
				previousDecade   : legacyOptions.tooltips?.prevDecade,
				nextDecade       : legacyOptions.tooltips?.nextDecade,
				previousCentury  : legacyOptions.tooltips?.prevCentury,
				nextCentury      : legacyOptions.tooltips?.nextCentury,
				incrementHour    : legacyOptions.tooltips?.incrementHour,
				pickHour         : legacyOptions.tooltips?.pickHour,
				decrementHour    : legacyOptions.tooltips?.decrementHour,
				incrementMinute  : legacyOptions.tooltips?.incrementMinute,
				pickMinute       : legacyOptions.tooltips?.pickMinute,
				decrementMinute  : legacyOptions.tooltips?.decrementMinute,
				incrementSecond  : legacyOptions.tooltips?.incrementSecond,
				pickSecond       : legacyOptions.tooltips?.pickSecond,
				decrementSecond  : legacyOptions.tooltips?.decrementSecond,
			},
			restrictions: {
				minDate,
				maxDate,
			},
		} ) as Options;
	}

	/**
	 * Run ATUM's legacy change side-effects and event bridge.
	 *
	 * @param {DateTimePickerEventDetail} evt
	 * @param {JQuery}                    $dpField
	 * @param {string}                    format
	 */
	private onDateTimePickerChange( evt: DateTimePickerEventDetail, $dpField: JQuery, format: string ) {
		const $fieldLabel: JQuery = $dpField.siblings( '.field-label' );

		if ( $fieldLabel.length ) {

			const currentLabel: string = $fieldLabel.text().trim(),
			      newLabel: string     = evt.date ? evt.date.format( format ) : this.settings.get( 'none' );

			// Only update it if changed.
			if ( newLabel !== currentLabel ) {
				$fieldLabel.addClass( 'unsaved' ).text( newLabel );
			}
			else {
				$fieldLabel.removeClass( 'unsaved' );
			}

		}

		const legacyEvent: any = $.Event( evt.isClear ? 'dp.clear' : 'dp.change' );

		legacyEvent.date = evt.date;
		legacyEvent.oldDate = evt.oldDate;

		$dpField.trigger( legacyEvent );
		$dpField.trigger( 'atum-dp-change' );
	}

	/**
	 * Preserve the old behavior that only one picker should be visible and range
	 * constraints are refreshed when the widget opens.
	 *
	 * @param {JQuery} $selector
	 * @param {JQuery} $input
	 */
	private onDateTimePickerShow( $selector: JQuery, $input: JQuery ) {
		// Hide others opened.
		$selector.not( $input ).each( ( index: number, elem: Element ) => {
			const otherPicker: any = $( elem ).data( 'DateTimePicker' );

			if ( typeof otherPicker !== 'undefined' ) {
				otherPicker.hide();
			}
		} );

		// Check the min and max dates when it's a range field.
		if ( $input.data( 'range-max' ) || $input.data( 'range-min' ) ) {
			this.checkRange( $input );
		}

		$input.trigger( $.Event( 'dp.show' ) );
	}

	/**
	 * Normalize the legacy date option values used across ATUM and addons.
	 *
	 * @param {LegacyDateInput} value
	 * @param {string}          format
	 *
	 * @return {DateTime|undefined}
	 */
	static normalizeDateValue( value: LegacyDateInput, format: string ): DateTime | undefined {
		if ( value === false || value === null || typeof value === 'undefined' || value === '' ) {
			return undefined;
		}

		if ( value instanceof DateTime ) {
			return value;
		}

		if ( value instanceof Date ) {
			return DateTime.convert( value );
		}

		if ( typeof value === 'string' ) {
			if ( value === 'moment' || value === 'now' ) {
				return DateTime.convert( new Date() );
			}

			if ( value === 'moment+1' ) {
				const date = new Date();

				date.setMinutes( date.getMinutes() + 1 );

				return DateTime.convert( date );
			}

			try {
				const date = DateTime.fromString( value, { format } );

				if ( DateTimePicker.isUsableDateTime( date ) && date.format( format ) === value ) {
					return date;
				}
			}
			catch ( err ) {
				// Fall back to native parsing below for ISO-like values.
			}

			const date = new Date( value );

			return Number.isNaN( date.getTime() ) ? undefined : DateTime.convert( date );
		}

		return undefined;
	}

	static isUsableDateTime( date?: DateTime ): date is DateTime {
		return !!date && !Number.isNaN( date.getTime() );
	}

	static omitUndefinedOptions( value: unknown ): unknown {
		if ( value instanceof Date ) {
			return value;
		}

		if ( Array.isArray( value ) ) {
			return value.map( ( item: unknown ) => DateTimePicker.omitUndefinedOptions( item ) );
		}

		if ( value && typeof value === 'object' ) {
			return Object.fromEntries(
				Object.entries( value )
					.filter( ( [ , entryValue ] ) => typeof entryValue !== 'undefined' )
					.map( ( [ key, entryValue ] ) => [ key, DateTimePicker.omitUndefinedOptions( entryValue ) ] ),
			);
		}

		return value;
	}

	/**
	 * Convert the legacy Moment.js format tokens used by ATUM's previous
	 * datepicker wrapper to Tempus Dominus' DateTime tokens.
	 *
	 * @param {string} format
	 *
	 * @return {string}
	 */
	static toTempusFormat( format: string ): string {
		const tokenMap: Record<string, string> = {
			YYYY: 'yyyy',
			YY  : 'yy',
			DD  : 'dd',
			D   : 'd',
			A   : 'T',
			a   : 't',
		};

		return format.replace( /(\[[^[\]]*])|YYYY|YY|DD|D|A|a/g, ( token: string, escaped: string ) => {
			return escaped || tokenMap[ token ] || token;
		} );
	}
	
}
