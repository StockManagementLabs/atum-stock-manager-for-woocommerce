/* =======================================
   DATE TIME PICKER
   ======================================= */

/**
 * Third party plugins
 */
import '../../vendor/bootstrap-datetimepicker';     // A fixed version compatible with webpack

import Settings from '../config/_settings';

export default class DateTimePicker {
	
	defaults: any = {};

	constructor(
		private settings: Settings,
	) {

		const langs: string[] = [ 'haz', 'as', 'ar', 'as', 'azb', 'bo', 'dz', 'fa', 'gu', 'he', 'hi', 'hy', 'ka', 'kk', 'km', 'kn', 'ko', 'ku', 'lo', 'ml', 'mr', 'my', 'ne', 'pa', 'ps', 'sd', 'si', 'skr', 'ta', 'ur' ];
		const mylang: string  = langs.includes( this.settings.get( 'calendarLocale' ) ) ? 'en' : this.settings.get( 'calendarLocale' );

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
			minDate          : new Date(), // By default, we are not allowing to select dates before today
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
	 * Add the date time pickers
	 *
	 * @param {JQuery} $selector
	 * @param {any}    opts
	 */
	addDateTimePickers( $selector: JQuery, opts: any = {} ) {

		$selector.each( ( index: number, elem: Element ) => {

			let $dateTimePicker: any = $( elem );
			const data: any = $dateTimePicker.data() || {};

			// If the current element has a DateTimePicker attached, destroy it first.
			if ( data.hasOwnProperty( 'DateTimePicker' ) ) {
				this.destroyDateTimePickers( $dateTimePicker );
			}

			// Use the spread operator to create a new options object in order to not conflict with other DateTimePicker options.
			$dateTimePicker.bsDatetimepicker( {
				...this.defaults,
				...data,
				...opts,
			} );

		} )
		.on( 'dp.change dp.clear', ( evt: any ) => {

			evt.stopImmediatePropagation();

			const $dpField: JQuery    = $( evt.currentTarget ),
			      $fieldLabel: JQuery = $dpField.siblings( '.field-label' );

			if ( $fieldLabel.length ) {

				const currentLabel: string = $fieldLabel.text().trim(),
				      dateTimePicker: any  = $dpField.data( 'DateTimePicker' ),
				      newLabel: string     = typeof evt.date === 'object' ? evt.date.format( dateTimePicker?.options()?.format || this.settings.get( 'dateFormat' ) ) : this.settings.get( 'none' );

				// Only update it if changed.
				if ( newLabel !== currentLabel ) {
					$fieldLabel.addClass( 'unsaved' ).text( newLabel );
				}
				else {
					$fieldLabel.removeClass( 'unsaved' );
				}

			}

			$dpField.trigger( 'atum-dp-change' );

		} )
		.on( 'dp.show', ( evt: any ) => {

			const $input: JQuery = $( evt.currentTarget );

			// Hide others opened.
			$selector.not( $input ).filter( ( index: number, elem: Element ) => {

				if ( $( elem ).children( '.bootstrap-datetimepicker-widget' ).length ) {
					return true;
				}

				return false;

			} ).each( ( index: number, elem: Element ) => {
				$( elem ).data( 'DateTimePicker' ).hide();
			} );

			// Check the min and max dates when it's a range field.
			if ( $input.data( 'range-max' ) || $input.data( 'range-min' ) ) {
				this.checkRange( $input );
			}

		} );
		
	}

	/**
	 * Destroy the datepickers
	 *
	 * @param {JQuery} $selector
	 */
	destroyDateTimePickers( $selector: JQuery ) {

		$selector.each( ( index: number, elem: Element ) => {

			const dateTimePicker: any = $( elem ).data( 'DateTimePicker' );

			if ( typeof dateTimePicker !== 'undefined' ) {
				dateTimePicker.destroy();
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
	
}