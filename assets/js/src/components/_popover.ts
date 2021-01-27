/* =======================================
   POPOVER
   ======================================= */

import BsPopover from 'bootstrap/js/dist/popover'; // Bootstrap 5 popover

import DateTimePicker from './_date-time-picker';
import PopoverBase from '../abstracts/_popover-base';
import Settings from '../config/_settings';
import Utils from '../utils/_utils';

export default class Popover extends PopoverBase{

	popoverClassName: string = 'atum-popover';
	
	constructor(
		private settings: Settings,
		private dateTimePicker?: DateTimePicker
	) {

		super();
		
		// Init popovers.
		this.bindPopovers();
		
		// Hide any other opened popover before opening a new one.
		$( 'body' ).click( ( evt: JQueryEventObject ) => {

			const $target: JQuery = $( evt.target );

			// Do not hide any popover if the click is being performed within one.
			if ( $target.is( '.popover' ) || $target.closest( '.popover.show' ).length ) {
				return;
			}

			// If we are clicking on a editable cell, get the other opened popovers, if not, get all them all.
			const $metaCell: JQuery = $target.hasClass( 'set-meta' ) ? $( '.set-meta' ).not( $target ) : $( '.set-meta' );
			
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
			this.addPopover( $( elem ) );
		} );
		
		// Focus on the input field and set a reference to the popover to the editable column.
		$metaCells.on( 'inserted.bs.popover', ( evt: JQueryEventObject ) => {

			const $metaCell: JQuery      = $( evt.currentTarget ),
			      $activePopover: JQuery = $( '.popover.show' );
			
			$activePopover.find('.meta-value').focus().select();

			if ( this.dateTimePicker ) {

				const $dateInputs: JQuery = $activePopover.find( '.bs-datepicker' );

				if ( $dateInputs.length ) {
					this.dateTimePicker.addDateTimePickers( $dateInputs );
				}

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
			
		});
		
	}
	
	/**
	 * Bind the editable cell's popovers
	 *
	 * @param jQuery $metaCell The cell where the popover will be attached.
	 */
	addPopover( $metaCell: JQuery ) {

		const symbol: string    = $metaCell.data( 'symbol' ) || '',
		      cellName: string  = $metaCell.data( 'cell-name' ) || '',
		      inputType: string = $metaCell.data( 'input-type' ) || 'number',
		      value: string     = $metaCell.text(),
		      inputAtts: any    = {
			      type : inputType || 'number',
			      value: value,
			      class: 'meta-value',
			      min  : '',
			      step : '',
		      };

		if ( inputType === 'number' || value === '-' ) {

			let numericValue: number;

			// For currency numbers.
			if ( symbol ) {
				numericValue= Math.abs( <number> Utils.unformat( value, this.settings.get( 'currencyFormatDecimalSeparator' ) ) );
			}
			// For regular numbers.
			else {
				numericValue = parseFloat( value );
			}

			inputAtts.value = isNaN( numericValue ) ? 0 : numericValue;

		}
		
		if ( inputType === 'number' ) {
			inputAtts.min = symbol ? '0' : ''; // The minimum value for currency fields is 0.
			inputAtts.step = symbol ? '0.1' : '1'; // Allow decimals only for the currency fields for now.
		}

		const $input: JQuery     = $( '<input />', inputAtts ),
		      $setButton: JQuery = $( '<button />', {
			      type : 'button',
			      class: 'set btn btn-primary button-small',
			      text : this.settings.get( 'setButton' ),
		      } ),
		      extraMeta: any     = $metaCell.data( 'extra-meta' );

		let popoverClass: string = this.popoverClassName,
		    $extraFields: JQuery = null;

		// Check whether to add extra fields to the popover.
		if ( typeof extraMeta !== 'undefined' ) {

			popoverClass = ' with-meta';
			$extraFields = $( '<hr>' );

			$.each( extraMeta, ( index: number, metaAtts: any ) => {
				$extraFields = $extraFields.add( $( '<input />', metaAtts ) );
			} );

		}

		const $content: JQuery = $extraFields ? $input.add( $extraFields ).add( $setButton ) : $input.add( $setButton );
		
		// Create the meta edit popover.
		new BsPopover( $metaCell.get( 0 ), {
			title      : this.settings.get( 'setValue' ) ? this.settings.get( 'setValue' ).replace( '%%', cellName ) : cellName,
			content    : $( '<div class="edit-popover-content" />' ).append( $content ).get( 0 ), // It supports one element only.
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
