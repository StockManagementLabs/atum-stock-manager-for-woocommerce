/* ===================
   EDIT POPOVERS
   =================== */

import ButtonGroup from './_button-group';
import EnhancedSelect from './_enhanced-select';
import PopoverBase from '../abstracts/_popover-base';
import Settings from '../config/_settings';
import Utils from '../utils/_utils';
import WPHooks from '../interfaces/wp.hooks';

export default class EditPopovers extends PopoverBase{

	popoverClassName: string = 'edit-field-popover';
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private enhancedSelect?: EnhancedSelect,
	) {

		super();
		this.bindEvents();
		
	}
	
	/**
	 * Bind the popovers
	 *
	 * @param {JQuery} $editButtons The edit buttons where are attached the popovers.
	 */
	bindPopovers( $editButtons: JQuery ) {
		
		$editButtons.each( ( index: number, elem: Element ) => {

			const $editButton: JQuery   = $( elem ),
			      $fieldWrapper: JQuery = this.getEditFieldWrapper( $editButton );

			// If this button already had a popover linked, destroy it first to not conflict with the new one.
			if ( this.getInstance( $editButton ) ) {
				this.destroyPopover( $editButton );
			}

			// NOTE: the template should be a button's sibling to avoid unexpected issues with possible duplicated IDs.
		    let content: string = $editButton.parent().find( `#${ $editButton.data( 'content-id' ) }` ).html();

			if ( ! $( content ).hasClass( 'alert' ) ) {
				content += `<button class="set btn btn-primary btn-sm">${ this.settings.get( 'setButton' ) }</button>`;
			}

			this.addPopover( $editButton, {
				content    : $( '<div class="edit-popover-content" />' ).append( content ).get( 0 ),
				html       : true,
				customClass: $editButton.data( 'bs-custom-class' ) ? `${ this.popoverClassName } ${ $editButton.data( 'bs-custom-class' ) }` : this.popoverClassName,
				placement  : $editButton.data( 'bs-placement' ) || 'bottom',
				trigger    : 'click',
				container  : $fieldWrapper,
			} )

			// Prepare the popover's fields when shown.
			$editButton.on( 'inserted.bs.popover', () => {

				const $popover: JQuery    = $( `#${ $editButton.attr( 'aria-describedby' ) }` ),
				      $valueInput: JQuery = $editButton.siblings( 'input:hidden' );

				if ( $valueInput.length === 1 ) {
					this.initPopoverInputValue( $valueInput, $popover );
				}
				// Support multiple inputs.
				else if ( $valueInput.length > 1 ) {

					$valueInput.each( ( index: number, elem: Element ) => {
						this.initPopoverInputValue( $( elem ), $popover );
					} );

				}

				// Prepare select2.
				$( `#${ $popover.attr( 'id' ) } .select2` ).remove();
				this.prepareSelect( $editButton );

				// Prepare the button groups (if any).
				if ( $popover.find( '.btn-group' ).length ) {
					ButtonGroup.doButtonGroups( $popover );
				}

				$popover.find( '.meta-value' ).first().focus().select();

				// Trigger action after inserting the popover.
				this.wpHooks.doAction( 'atum_editPopovers_inserted', $popover, $editButton );

			} );

		} );
		
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		$( 'body' )

			// Set the field data when clicking the "Set" button.
			.on( 'click', `.popover.${ this.popoverClassName } .set`, ( evt: JQueryEventObject ) => {
				evt.preventDefault();
				this.setValue( $( evt.currentTarget ) );
			})

			// Bind keys pressed on the popover.
			.on( 'keyup', '.popover', ( evt: JQueryEventObject ) => {

				const $popover: JQuery = $( evt.currentTarget );
				evt.preventDefault();

				// Enter key.
				if ( evt.keyCode === 13 ) {
					$popover.find( '.set' ).click();
				}
				// Esc key.
				else if ( evt.keyCode === 27 ) {
					const $editButton: JQuery = $( `[aria-describedby="${ $popover.attr( 'id' ) }"]` );
					this.destroyPopover( $editButton, () => setTimeout( () => this.bindPopovers( $editButton ), 300 ) ); // Give a small lapse to complete the 'fadeOut' animation before re-binding.
				}

			} )

			// Avoid weird error when pressing enter on edit popovers (it was showing a Bootstrap error in console and reloading the page).
			.on( 'keydown', '.popover', ( evt: JQueryEventObject ) => {

				if ( evt.keyCode === 13 ) {
					evt.preventDefault();
				}

			} )

			// Switch between percentage and fixed amount when clicking on the input group button.
			.on( 'click', '.edit-field-popover .input-group-append', ( evt: JQueryEventObject ) => {

				const $elem: JQuery = $( evt.currentTarget );

				$elem.children( '.input-group-text' ).toggleClass( 'active' );
				$elem.children( 'input' ).val( $elem.children( '.input-group-text.active' ).data( 'value' ) );

			} )

			// Set default value link.
			.on( 'click', '.set-default-value', ( evt: JQueryEventObject ) => {
				evt.preventDefault();
				this.setDefaultValue( $( evt.currentTarget ) );
			} );

		// Hide any other opened popover before opening a new one.
		// NOTE: we are using the #wpbody-content element instead of the body tag to avoid closing when clicking within popovers.
		$( '#wpbody-content' ).click( ( evt: JQueryEventObject ) => this.maybeHidePopovers( $( evt.target ) ) );

	}

	/**
	 * Set field value when clicking the popover's set button
	 *
	 * @param {JQuery} $setButton
	 */
	setValue( $setButton: JQuery ) {

		const popoverId: string       = $setButton.closest( '.popover' ).attr( 'id' ),
		      $editButton: JQuery     = $( `[aria-describedby="${ popoverId }"]` ),
		      $fieldWrapper: JQuery   = this.getEditFieldWrapper( $editButton ),
		      $popoverWrapper: JQuery = $setButton.closest( '.popover-body' ),
		      $setMetaInput: JQuery   = $popoverWrapper.find( '.meta-value' );

		// The popover must have at least an input field with the meta-value class.
		if ( ! $setMetaInput.length ) {
			return;
		}

		let newValue: any;

		// Support multiple meta inputs within the same popover.
		if ( $setMetaInput.length > 1 ) {

			newValue = {};

			$setMetaInput.each( ( index: number, elem: Element ) => {

				const $input: JQuery = $( elem );

				// The name of the related data input should be placed on the data-input attr.
				newValue[ $input.data( 'input' ) || $input.attr( 'name' ) ] = $input.val();

			} );

		}
		else {
			newValue = $setMetaInput.val();
		}

		let newLabel: string;

		if ( $setMetaInput.is( 'select' ) ) {

			if ( ! newValue ) {
				newLabel = null;
			}
			// Multi-select.
			else if ( Array.isArray( newValue ) ) {

				let selectedLabels: string[] = [];

				$setMetaInput.find( 'option:selected' ).each( ( index: number, elem: Element ) => {
					selectedLabels.push( $( elem ).text().trim() );
				} );

				newLabel = selectedLabels.join( ', ' );

			}
			else {
				newLabel = $setMetaInput.find( 'option:selected' ).text().trim();
			}

			$setMetaInput.find( 'option' ).each( ( index: number, elem: Element ) => {

				const $option: JQuery = $( elem );

				if ( $.inArray( $option.val().toString(), newValue ) > -1 ) {
					$option.attr( 'selected', 'selected' );
				}
				else {
					$option.removeAttr( 'selected' );
				}

			} );

		}
		else {
			newLabel = newValue ? newValue : null;
		}

		// Set the value to the related hidden input(s).
		const $valueInput: JQuery = $editButton.siblings( 'input:hidden' );
		let oldValue: any;

		// Support multiple data inputs.
		if ( $valueInput.length > 1 && typeof newValue === 'object' ) {

			oldValue = {};

			$valueInput.each( ( index: number, elem: Element ) => {

				const $input: JQuery    = $( elem ),
				      inputName: string = $input.attr( 'name' );

				oldValue[ inputName ] = $input.val();

				if ( newValue.hasOwnProperty( inputName ) ) {
					$input.val( newValue[ inputName ] ).change();
				}

			} );

		}
		else {

			if ( typeof newValue === 'object' ) {
				newValue = newValue[ Object.keys( newValue )[ 0 ] ];
			}

			oldValue = $valueInput.first().val();
			$valueInput.val( newValue ).change(); // We need to trigger the change event, so WC is aware of the change made to any variation and updated its data.
		}

		// Set the field label.
		this.setEditFieldLabel( $fieldWrapper.find( '.field-label' ), newLabel );

		// Handle the value change extenally.
		this.wpHooks.doAction( 'atum_editPopovers_setValue', $valueInput, newValue, oldValue, newLabel, $popoverWrapper.find( ':input' ).serializeArray(), $setButton );

		// Once set, destroy the opened popover.
		this.destroyPopover( $editButton, () => setTimeout( () => this.bindPopovers( $editButton ), 300 ) ); // Give a small lapse to complete the 'fadeOut' animation before re-binding.

	}

	/**
	 * Get the edit field wrapper
	 *
	 * @param {JQuery} $editField
	 *
	 * @return {JQuery}
	 */
	getEditFieldWrapper( $editField: JQuery ) {
		return  $editField.parent().hasClass( 'atum-tooltip' ) ?  $editField.parent().parent() : $editField.parent();
	}
	
	/**
	 * Add the enhancedSelect components to the popover
	 *
	 * @param {JQuery} $editButton
	 */
	prepareSelect( $editButton: JQuery ) {
		
		if ( this.enhancedSelect ) {

			$( `#${ $editButton.attr( 'aria-describedby' ) } select` ).each( ( index: number, elem: Element ) => {

				const $select: JQuery    = $( elem ),
				      selectOptions: any = {
					      minimumResultsForSearch: 20,
					      placeholder            : {
						      id  : '-1',
						      text: $select.find( 'option' ).first().text().trim(),
					      },
				      };

				if ( $select.hasClass( 'atum-select-multiple' ) && $select.prop( 'multiple' ) === false ) {
					$select.val( $editButton.siblings( 'input[type=hidden]' ).val().split( ',' ) );
				}

				$select.css( 'width', '200px' );
				this.enhancedSelect.doSelect2( $select, selectOptions, true );

			} );
		
		}
		
	}
	
	/**
	 * Set the label for an edit field
	 *
	 * @param {JQuery} $fieldLabel
	 * @param {string} label
	 */
	setEditFieldLabel( $fieldLabel: JQuery, label: string ) {

		if ( $fieldLabel.length ) {

			let decimals: number = parseInt( $fieldLabel.data( 'decimals-number') || '2' );

			$fieldLabel.addClass( 'unsaved' );

			// For numeric labels, adjust the decimal separator if needed.
			if ( Utils.isNumeric( label ) && $fieldLabel.data( 'decimal-separator' ) ) {
				// Round only 2??
				label = <string> Utils.formatNumber( parseFloat( label ), decimals, '', $fieldLabel.data( 'decimal-separator' ) );
			}

			// Check if a template exists for the label
			if ( null !== label && $fieldLabel.data( 'template' ) ) {
				$fieldLabel.html( $fieldLabel.data( 'template' ).replace( '%value%', label ) );
			}
			else if ( null === label ) {
				const noneLabel: string = $fieldLabel.data( 'none' ) ? $fieldLabel.data( 'none' ) : this.settings.get( 'none' );
				$fieldLabel.text( noneLabel );
			}
			else {
				$fieldLabel.text( label );
			}

		}
		
	}

	/**
	 * Initialize the popover input(s) value(s)
	 *
	 * @param {JQuery} $valueInput
	 * @param {JQuery} $popover
	 */
	initPopoverInputValue( $valueInput: JQuery, $popover: JQuery ) {

		const inputData: any = $valueInput.data();
		let currentValue: string = '';

		// Check whether to load the initial values from data attributes.
		// NOTE: the related value input(s) must have the data-field-value in order to initialize the popover field(s) with such value.
		if ( Object.keys( inputData ).length && inputData.fieldValue ) {

			for ( const dataKey in inputData ) {

				if ( 'fieldValue' === dataKey ) {
					currentValue = inputData[ dataKey ];
				}
				else if ( $popover.find( `[name=${ dataKey }]` ).length ) {

					let $targetInput: JQuery = $popover.find( `[name=${ dataKey }]` );

					// Radio buttons.
					if ( $targetInput.is( ':radio' ) ) {

						$targetInput = $targetInput.filter( `[value="${ inputData[ dataKey ] }"]` );

						// Button group input.
						if ( $targetInput.closest( '.btn-group' ).length ) {
							$targetInput.closest( '.btn' ).click();
						}
						// Regular radio.
						else {
							$targetInput.prop( 'checked', true );
						}

					}
					// Checkboxes.
					else if ( $targetInput.is( ':checkbox' ) ) {
						$targetInput.prop( 'checked', true );
					}
					// Input groups.
					else if ( $targetInput.closest( '.input-group-append, .input-group-prepend' ).length  ) {
						$targetInput.siblings( '.input-group-text.active' ).removeClass( 'active' );
						$targetInput.siblings( `[data-value="${ inputData[ dataKey ] }"]` ).addClass( 'active' );
						$targetInput.val( inputData[ dataKey ] );
					}
					else {
						$targetInput.val( inputData[ dataKey ] );
					}

				}

			}

		}
		else {
			currentValue = $valueInput.val();
		}

		// Select multiple.
		if ( $popover.find( 'select[multiple]' ).length ) {

			if ( currentValue ) {

				let $select: JQuery = $popover.find( `select[data-input="${ $valueInput.data( 'input' ) }"]` );

				if ( ! $select.length ) {
					$select = $popover.find( 'select[multiple]' ).first();
				}

				$select.val( currentValue.split( ',' ) ).change();

			}

		}
		else {

			let $input: JQuery = $popover.find( `.meta-value[data-input="${ $valueInput.data( 'input' ) }"]` );

			if ( ! $input.length ) {
				$input = $popover.find( '.meta-value' ).first();
			}

			$input.val( currentValue ).change();

		}

	}

	/**
	 * Set the default value by clicking the link for it
	 *
	 * @param {JQuery} $link
	 */
	setDefaultValue( $link: JQuery ) {

		const $popoverContent: JQuery = $link.closest( '.edit-popover-content' ),
		      dataAtts: any           = $link.data();

		if ( dataAtts && Object.keys( dataAtts ).length ) {

			for ( const dataAtt in dataAtts ) {

				const inputName: string = dataAtt.replace( /_/g, '-' );

				const $relatedInput: JQuery = $popoverContent.find( `:input[name="${ inputName }"]` );

				if ( $relatedInput.length ) {

					// Input group append fields.
					if ( $relatedInput.parent( '.input-group-append' ).length ) {
						if ( $relatedInput.val() !== dataAtts[ dataAtt ] ) {
							$relatedInput.parent( '.input-group-append' ).click(); // Force the change by triggering the event.
						}
					}
					// Any other input.
					else {
						$relatedInput.val( dataAtts[ dataAtt ] );
					}

				}

			}

		}

	}

	/**
	 * Hide all the other opened popovers when opening the current one
	 *
	 * @param {JQuery} $target
	 */
	maybeHidePopovers( $target: JQuery ) {

		if ( ! $( '.popover' ).length ) {
			return;
		}

		if (
			! $target.length || $target.hasClass( 'select2-selection__choice__remove' ) ||
			$target.hasClass( this.popoverClassName ) || $target.closest( `.${ this.popoverClassName }` ).length
		) {
			return;
		}

		// Hide all the opened popovers.
		$( `.popover.${ this.popoverClassName }` ).each( ( index: number, elem: Element ) => {

			const $editButton: JQuery = $( `[aria-describedby="${ $( elem ).attr( 'id' ) }"]` );

			if ( ! $editButton.is( $target ) && ! $target.closest( $editButton ).length ) {
				super.hidePopover( $editButton );
			}

		} );

	}
	
}
