/*
 * =======================================
 * DATE TIME PICKER
 * =======================================
 */

/**
 * Third party plugins
 */
import { DateTime, Namespace, TempusDominus } from '@eonasdan/tempus-dominus';
import type Options from '@eonasdan/tempus-dominus/types/utilities/options';

import Settings from '../config/_settings';

type LegacyDateInput = Date | DateTime | string | boolean | null | undefined;

type LegacyDateTimePickerOptions = {
    format?           : string;
    dateFormat?       : string;
    useCurrent?       : boolean;
    showClose?        : boolean;
    showClear?        : boolean;
    showTodayButton?  : boolean;
    keepInvalid?      : boolean;
    minDate?          : LegacyDateInput;
    maxDate?          : LegacyDateInput;
    stepping?         : number;
    sideBySide?       : boolean;
    widgetPositioning?: {
        vertical?  : 'top' | 'bottom' | 'auto';
        horizontal?: 'left' | 'right' | 'auto';
    };
    icons?   : Record<string, string>;
    tooltips?: Record<string, string>;
    locale?  : string;
};

type DateTimePickerEventDetail = {
    date?   : DateTime;
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
            this.setNativeDate( DateTimePicker.normalizeDateValue( newDate, this.tempusFormat ) );
        }

        return this.pickedDate || DateTimePicker.normalizeDateValue( this.input?.value, this.tempusFormat ) || this.picker.dates.lastPicked || false;
    }

    minDate( newDate?: LegacyDateInput ) {
        return this.restriction( 'minDate', newDate, !!arguments.length );
    }

    maxDate( newDate?: LegacyDateInput ) {
        return this.restriction( 'maxDate', newDate, !!arguments.length );
    }

    syncPickedDate( date?: DateTime ) {
        this.pickedDate = date || undefined;
    }

    syncVisibleSelection() {
        const selectedDate = this.date() || undefined;

        this.syncPickedDate( selectedDate );

        if ( DateTimePicker.isUsableDateTime( selectedDate ) ) {
            const viewDate = selectedDate.clone;

            viewDate.setLocalization( this.optionsRef.localization );
            this.picker.viewDate = viewDate;
        }
    }

    configureInputBridge() {
        this.picker.dates.parseInput = ( value: LegacyDateInput ): DateTime => DateTimePicker.normalizeDateValue( value, this.tempusFormat ) as DateTime;
        this.picker.dates.formatInput = ( date?: DateTime ): string => date ? date.format( this.tempusFormat ) : '';
    }

    private restriction( key: 'minDate' | 'maxDate', value: LegacyDateInput, shouldSet: boolean ) {
        if ( shouldSet ) {
            this.optionsRef.restrictions = {
                ...( this.optionsRef.restrictions || {} ),
                [ key ]: DateTimePicker.normalizeDateValue( value, this.tempusFormat ),
            };

            this.picker.updateOptions( { restrictions: this.optionsRef.restrictions } );
        }

        return this.optionsRef.restrictions?.[ key ] || false;
    }

    private setNativeDate( date?: DateTime ) {
        this.picker.dates.setValue( date, date ? this.picker.dates.lastPickedIndex : undefined );
        this.syncPickedDate( date );
    }

    private get input(): HTMLInputElement | undefined {
        return this.element instanceof HTMLInputElement ? this.element : this.element.querySelector( 'input' ) as HTMLInputElement;
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
            minDate          : today, // By default, we are not allowing to select dates before today.
            showClear        : true,
            showTodayButton  : true,
            locale           : mylang || 'en',
            icons            : this.getIcons(),
            tooltips         : this.getTooltips(),
            widgetPositioning: {
                horizontal: 'right',
                vertical  : 'bottom',
            },
        };

    }

    addDateTimePickers( $selector: JQuery, opts: LegacyDateTimePickerOptions = {} ) {

        $selector.each( ( index: number, elem: Element ) => {

            const $dateTimePicker: any = $( elem );
            const legacyOptions: LegacyDateTimePickerOptions = {
                ...this.defaults,
                ...( $dateTimePicker.data() || {} ),
                ...opts,
            };
            const legacyFormat = legacyOptions.format || legacyOptions.dateFormat || this.settings.get( 'dateFormat' );
            const tempusFormat = DateTimePicker.toTempusFormat( legacyFormat );
            const tempusOptions = this.toTempusDominusOptions( legacyOptions, tempusFormat );

            if ( $dateTimePicker.data( 'DateTimePicker' ) ) {
                this.destroyDateTimePickers( $dateTimePicker );
            }

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

            // Tempus parses the input during construction before we can patch parseInput/formatInput.
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

    checkRange( $dpInput: JQuery ) {

        const dp: any = $dpInput.data( 'DateTimePicker' );
        const rangeSelector = $dpInput.data( 'range-max' ) || $dpInput.data( 'range-min' );

        if ( !rangeSelector ) {
            return;
        }

        const rangeDp = $( rangeSelector ).data( 'DateTimePicker' );

        if ( !rangeDp ) {
            return;
        }

        if ( $dpInput.data( 'range-max' ) ) {
            dp.maxDate( rangeDp.date() || false );
        }
        else {
            dp.minDate( rangeDp.date() || false );
        }

    }

    private toTempusDominusOptions( legacyOptions: LegacyDateTimePickerOptions, format: string ): Options {
        const minDate = DateTimePicker.normalizeDateValue( legacyOptions.minDate, format );
        const maxDate = DateTimePicker.normalizeDateValue( legacyOptions.maxDate, format );
        const hasTime = /[HhmsaA]/.test( format );
        const hasDate = /[DMY]/.test( format );

        return DateTimePicker.omitUndefinedOptions( {
            allowInputToggle: false,
            keepInvalid     : legacyOptions.keepInvalid,
            useCurrent      : legacyOptions.useCurrent,
            stepping        : legacyOptions.stepping || 1, // Undefined corrupts Tempus' minute arithmetic.
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
                locale             : legacyOptions.locale || this.settings.get( 'calendarLocale' ) || 'en',
                format,
                dayViewHeaderFormat: { month: 'long', year: 'numeric' },
                ...this.toTempusLocalizationTooltips( legacyOptions.tooltips || {} ),
            },
            restrictions: { minDate, maxDate },
        } ) as Options;
    }

    private onDateTimePickerChange( evt: DateTimePickerEventDetail, $dpField: JQuery, format: string ) {
        const $fieldLabel: JQuery = $dpField.siblings( '.field-label' );

        if ( $fieldLabel.length ) {

            const currentLabel: string = $fieldLabel.text().trim();
            const newLabel: string = evt.date ? evt.date.format( format ) : this.settings.get( 'none' );

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

    private onDateTimePickerShow( $selector: JQuery, $input: JQuery ) {
        $selector.not( $input ).each( ( index: number, elem: Element ) => {
            const otherPicker: any = $( elem ).data( 'DateTimePicker' );

            if ( typeof otherPicker !== 'undefined' ) {
                otherPicker.hide();
            }
        } );

        if ( $input.data( 'range-max' ) || $input.data( 'range-min' ) ) {
            this.checkRange( $input );
        }

        $input.trigger( $.Event( 'dp.show' ) );
    }

    private getIcons(): Record<string, string> {
        return {
            time    : 'atum-icon atmi-clock',
            date    : 'atum-icon atmi-calendar-full',
            up      : 'atum-icon atmi-chevron-up',
            down    : 'atum-icon atmi-chevron-down',
            previous: 'atum-icon atmi-chevron-left',
            next    : 'atum-icon atmi-chevron-right',
            today   : 'atum-icon atmi-frame-expand',
            clear   : 'atum-icon atmi-trash',
            close   : 'atum-icon atmi-ok',
        };
    }

    private getTooltips(): Record<string, string> {
        return Object.fromEntries( [
            'today', 'clear', 'close', 'selectMonth', 'selectYear', 'selectDecade',
            'prevMonth', 'nextMonth', 'prevYear', 'nextYear', 'prevDecade', 'nextDecade',
            'prevCentury', 'nextCentury', 'incrementHour', 'pickHour', 'decrementHour',
            'incrementMinute', 'pickMinute', 'decrementMinute', 'incrementSecond', 'pickSecond', 'decrementSecond',
        ].map( ( key: string ) => [ key, this.settings.get( key ) ] ) );
    }

    private toTempusLocalizationTooltips( tooltips: Record<string, string> ): Record<string, string> {
        return {
            clear          : tooltips.clear,
            close          : tooltips.close,
            today          : tooltips.today,
            selectMonth    : tooltips.selectMonth,
            previousMonth  : tooltips.prevMonth,
            nextMonth      : tooltips.nextMonth,
            selectYear     : tooltips.selectYear,
            previousYear   : tooltips.prevYear,
            nextYear       : tooltips.nextYear,
            selectDecade   : tooltips.selectDecade,
            previousDecade : tooltips.prevDecade,
            nextDecade     : tooltips.nextDecade,
            previousCentury: tooltips.prevCentury,
            nextCentury    : tooltips.nextCentury,
            incrementHour  : tooltips.incrementHour,
            pickHour       : tooltips.pickHour,
            decrementHour  : tooltips.decrementHour,
            incrementMinute: tooltips.incrementMinute,
            pickMinute     : tooltips.pickMinute,
            decrementMinute: tooltips.decrementMinute,
            incrementSecond: tooltips.incrementSecond,
            pickSecond     : tooltips.pickSecond,
            decrementSecond: tooltips.decrementSecond,
        };
    }

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

        if ( value === 'moment' || value === 'now' ) {
            return DateTime.convert( new Date() );
        }

        if ( value === 'moment+1' ) {
            const date = new Date();

            date.setMinutes( date.getMinutes() + 1 );

            return DateTime.convert( date );
        }

        if ( typeof value === 'string' ) {
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
