/* =======================================
   POPOVER
   ======================================= */

import DateTimePicker from './_date-time-picker';
import EnhancedSelect from './_enhanced-select';
import PopoverBase from '../abstracts/_popover-base';
import Settings from '../config/_settings';
import Utils from '../utils/_utils';
import WPHooks from '../interfaces/wp.hooks';

export default class TableCellPopovers extends PopoverBase{

	popoverClassName: string = 'atum-popover';
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private dateTimePicker?: DateTimePicker,
		private enhancedSelect?: EnhancedSelect,
	) {

		super();
		
		// Init popovers.
		this.bindPopovers();
		
		// Hide any other opened popover before opening a new one.
		$( 'body' ).click( ( evt: JQueryEventObject ) => {

			if ( ! $( '.popover' ).length ) {
				return;
			}

			const $target: JQuery = $( evt.target ),
				  $select2: JQuery = $target.closest( '.select2-container');

			// Do not hide any popover if the click is being performed within one.
			if ( ! $target.length || $target.is( '.popover' ) || $target.closest( '.popover.show' ).length
				|| ( $select2.length && $select2.find( '.select2-search' ) ) ) {
				return;
			}

			// If we are clicking on a editable cell, get the other opened popovers, if not, get all them all.
			const $metaCell: JQuery = $target.hasClass( 'set-meta' ) ? $( '.set-meta[aria-describedby]' ).not( $target ) : $( '.set-meta[aria-describedby]' );
			
			// Hide all the opened popovers.
			this.hidePopover( $metaCell );

		} );
		
	}
	
	/**
	 * Enable "Set Field" popovers
	 */
	bindPopovers( $metaCells?: JQuery ) {

		if ( ! $metaCells ) {
			$metaCells = $( '.set-meta' );
		}
		
		// Set meta value for listed products.
		$metaCells.each( ( index: number, elem: Element ) => {
			this.doPopovers( $( elem ) );
		} );
		
		$metaCells

			// Focus on the input field and set a reference to the popover to the editable column.
			.on( 'shown.bs.popover', ( evt: JQueryEventObject ) => {

				const $metaCell: JQuery      = $( evt.currentTarget ),
				      $activePopover: JQuery = $( '.popover.show' ),
				      $metaInput: JQuery     = $activePopover.find( '.meta-value' );

				if ( this.dateTimePicker ) {

					const $dateInputs: JQuery = $activePopover.find( '.atum-datepicker' );

					if ( $dateInputs.length ) {
						this.dateTimePicker.addDateTimePickers( $dateInputs );
					}

				}

				// Do not focus over datepicker fields because the calendar is not showing.
				if ( ! $metaInput.hasClass( 'atum-datepicker' ) ) {
					$activePopover.find( '.meta-value' ).focus().select();
				}

				// Click the "Set" button when hitting enter on an input field.
				$activePopover.find( 'input' ).on( 'keyup', ( evt: JQueryEventObject ) => {

					// Enter key.
					if ( 13 === evt.which ) {
						$activePopover.find( '.set' ).click();
					}
					// ESC key.
					else if ( 27 === evt.which ) {
						this.hidePopover( $metaCell );
					}

				} );

				if ( $metaInput.hasClass( 'wc-product-search' ) && this.enhancedSelect ) {
					$( 'body' ).trigger( 'wc-enhanced-select-init' );
				}

			} );
		
	}
	
	/**
	 * Bind the editable cell's popovers
	 *
	 * @param {JQuery} $metaCell The cell where the popover will be attached.
	 */
	doPopovers( $metaCell: JQuery ) {

		const symbol: string    = $metaCell.data( 'symbol' ) || '',
		      cellName: string  = $metaCell.data( 'cell-name' ) || '',
		      inputType: string = $metaCell.data( 'input-type' ) || 'number',
		      isSelect: boolean = inputType === 'select',
		      realValue: string = $metaCell.data( 'realvalue' ) || '',
		      value: string     = $metaCell.text().trim(),
		      inputAtts: any    = {
			      type : inputType || 'number',
			      value: value,
			      class: $metaCell.data( 'extraClass' ) ? `meta-value ${ $metaCell.data( 'extraClass' ) }` : 'meta-value',
		      };

		if ( inputType === 'selective_number' ) {
			this.wpHooks.doAction( 'atum_tableCellPopovers_selectiveNumberType', $metaCell, this );
			return;
		}
		else if ( inputType === 'number' || symbol ) {

			let numericValue: number;
			const numericRealValue: number = parseFloat( realValue );

			// For currency numbers.
			if ( symbol ) {
				numericValue = Math.abs( <number> Utils.unformat( value.replace('>', '').trim(), this.settings.get( 'currencyFormatDecimalSeparator' ) ) );
			}
			// For regular numbers.
			else {
				numericValue = parseFloat( value );
			}

			if ( ! isNaN( numericRealValue) && numericValue !== numericRealValue ) {

				numericValue = numericRealValue;
			}

			inputAtts.value = isNaN( numericValue ) ? 0 : numericValue;

		}
		else if ( isSelect ) {
			const atts: string[] = [ 'allow_clear', 'action', 'placeholder', 'multiple', 'minimum_input_length', 'container-css', 'selected' ];

			atts.forEach( ( attr: string ) => {
				if ( typeof $metaCell.data( attr ) !== 'undefined' ) {
					inputAtts[ `data-${attr}` ] = $metaCell.data( attr );
				}
			} );

		}
		else if ( value === '-' ) {
			inputAtts.value = '';
		}
		
		if ( inputType === 'number' ) {
			inputAtts.min = symbol ? '0' : ''; // The minimum value for currency fields is 0.
			inputAtts.step = symbol ? '0.1' : '1'; // Allow decimals only for the currency fields for now.
		}

		const $input: JQuery     = isSelect ? $( '<select />', inputAtts ) : $( '<input />', inputAtts ),
		      $setButton: JQuery = $( '<button />', {
			      type : 'button',
			      class: 'set btn btn-primary button-small',
			      text : this.settings.get( 'setButton' ),
		      } ),
		      extraMeta: any     = $metaCell.data( 'extra-meta' );

		// Add the datepicker to the input field if needed.
		if ( $metaCell.data( 'has-datepicker' ) === 'yes' ) {
			$input.addClass( 'atum-datepicker' );

			if ( typeof $metaCell.data( 'value-in-placeholder' ) !== 'undefined' ) {
				$input.attr( 'placeholder', value );
			}

			if ( typeof $metaCell.data( 'date-format' ) !== 'undefined' ) {
				$input.data( 'date-format', $metaCell.data( 'date-format' ) );
			}

			if ( typeof $metaCell.data( 'min-date' ) !== 'undefined' ) {
				$input.data( 'min-date', $metaCell.data( 'min-date' ) );
			}

			if ( typeof $metaCell.data( 'max-date' ) !== 'undefined' ) {
				$input.data( 'max-date', $metaCell.data( 'max-date' ) );
			}
		}

		if ( isSelect ) {

			const selectedValue: string = $metaCell.data( 'selected-value' ),
			      selectOptions: any    = $metaCell.data( 'select-options' );

			if ( typeof selectOptions === 'object' && Object.keys( selectOptions ).length ) {

				$.each( selectOptions, ( index: string, value: any ) => {

					const selected: string = selectedValue.toString() === index ? ' selected' : '';

					$input.append( `
						<option value="${ index }"${ selected }>
                           ${ value === this.settings.get( 'emptyCol' ) ? '' : value }
                        </option>
					` );

				} );

			}

		}

		let popoverClass: string = this.popoverClassName,
		    $extraFields: JQuery = null;

		// Check whether to add extra fields to the popover.
		if ( typeof extraMeta !== 'undefined' ) {

			popoverClass += ' with-meta';
			$extraFields  = $( '<div />' ).append( $input ).append( '<hr>' );

			$.each( extraMeta, ( index: number, metaAtts: any ) => {
				$extraFields.append( $( '<input />', metaAtts ) );
			} );

		}

		// Add class if has select.
		if ( isSelect ) {
			popoverClass += ' with-select';
		}

		let $content: JQuery = $( '<div class="edit-popover-content" />' );

		if ( $extraFields ) {
			$content.append( $extraFields ).append( $setButton );
		}
		else {
			$content.append( $input ).append( $setButton );
		}

		if ( $metaCell.data( 'footerContent' ) ) {
			$content = $( '<div class="edit-popover-wrapper" />' ).append( $content ).append( $metaCell.data( 'footerContent' ) );
		}

		const titleSetting: string = isSelect ? 'selectSetValue' : 'setValue';

		// Create the meta edit popover.
		this.addPopover( $metaCell, {
			title      : this.settings.get( titleSetting ) ? this.settings.get( titleSetting ).replace( '%%', cellName ) : cellName,
			content    : $content.get( 0 ), // It supports one element only.
			html       : true,
			customClass: popoverClass,
			placement  : 'bottom',
			trigger    : 'click',
			container  : 'body',
		} );
		
	}
	
	/**
	 * Destroy a popover attached to a specified table cell
	 *
	 * @param jQuery $metaCell Optional. The table cell where is attached the visible popover.
	 */
	destroyPopover( $metaCell?: JQuery ) {

		// If not passing the popover to destroy, try to find out the currently active.
		if ( ! $metaCell || ! $metaCell.length ) {
			$metaCell = $( '.set-meta[aria-describedby]' );
		}

		if ( $metaCell.length ) {

			super.destroyPopover( $metaCell, () => {

				// Give a small lapse to complete the 'fadeOut' animation before re-binding.
				setTimeout( () => this.bindPopovers( $metaCell ), 300 );

			} );

		}

	}
	
}
