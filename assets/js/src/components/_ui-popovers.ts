/* ===================
   UI POPOVERS
   =================== */

import '../../vendor/bootstrap3-custom.min'; // TODO: USE BOOTSTRAP 4 POPOVERS
import EnhancedSelect from './_enhanced-select';
import Settings from '../config/_settings';
import { Utils } from '../utils/_utils';

export default class UIPopovers {
	
	constructor(
		private settings: Settings,
		private enhancedSelect?: EnhancedSelect,
	) {
		
		//
		// Popover events.
		//----------------
		$('body')
		
			// Set the field data when clicking the "Set" button.
			.on( 'click', '.popover .set', ( evt: JQueryEventObject ) => {
				
				const $setButton: JQuery      = $( evt.currentTarget ),
				      popoverId: string       = $setButton.closest( '.popover' ).attr( 'id' ),
				      $editField: JQuery      = $( `[data-popover="${ popoverId }"]` ),
				      $fieldWrapper: JQuery   = $editField.parent(),
				      $popoverWrapper: JQuery = $setButton.closest( '.popover-content' ),
				      $setMetaInput: JQuery   = $popoverWrapper.find( '.meta-value' ),
				      newValue: any           = $setMetaInput.val();
				
				let newLabel: string;
				
				if ( $setMetaInput.is( 'select' ) ) {
					
					if ( ! newValue ) {
						newLabel = this.settings.get( 'none' );
					}
					else if ( $.isArray( newValue ) ) {
						
						let selectedLabels: string[] = [];
						
						$setMetaInput.find( 'option:selected' ).each( ( index: number, elem: Element ) => {
							selectedLabels.push( $.trim( $( elem ).text() ) );
						} );
						
						newLabel = selectedLabels.join( ', ' );
						
					}
					else {
						newLabel = $.trim( $setMetaInput.find( 'option:selected' ).text() );
					}
					
				}
				else {
					newLabel = newValue ? newValue : this.settings.get( 'none' );
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
				
				// Set the value to the related hidden input.
				const $valueInput: JQuery = $fieldWrapper.find( 'input[type=hidden]' );
				$valueInput.val( newValue );
				
				// Set the field label.
				const $fieldLabel: JQuery = $fieldWrapper.find( '.field-label' );
				
				if ( $fieldLabel.length ) {
					
					$fieldLabel.addClass( 'unsaved' );
					
					// For numeric labels, adjust the decimal separator if needed.
					if ( $.isNumeric( newLabel ) && $fieldLabel.data('decimal-separator') ) {
						newLabel = <string>Utils.formatNumber( parseFloat( newLabel ), 2, '', $fieldLabel.data('decimal-separator') );
					}
					
					// Check if a template exists for the label
					if ( $fieldLabel.data( 'template' ) ) {
						$fieldLabel.html( $fieldLabel.data( 'template' ).replace( '%value%', newLabel ) );
					}
					else {
						$fieldLabel.text( newLabel );
					}
					
				}
				
				// Once set, destroy the opened popover.
				this.destroyPopover( $fieldWrapper.find( '.atum-edit-field' ) );
				
				$editField.trigger( 'atum-ui-popover-set-value', [ $valueInput, newValue, newLabel, $popoverWrapper.find(':input').serializeArray() ] );
				
			})
			
			// Bind keys pressed on the popover.
			.on( 'keyup', '.popover', ( evt: JQueryEventObject ) => {
				
				const $popover: JQuery = $( evt.currentTarget );
				
				// Enter key.
				if ( evt.keyCode === 13 ) {
					$popover.find( '.set' ).click();
				}
				// Esc key.
				else if ( evt.keyCode === 27 ) {
					this.destroyPopover( $( `[data-popover="${ $popover.attr( 'id' ) }"]` ) );
				}
				
			} );
		
			// Hide any other opened popover before opening a new one.
			// NOTE: we are using the #wpbody-content element instead of the body tag to avoid closing when clicking within popovers.
			$( '#wpbody-content' ).click( ( evt: JQueryEventObject ) => {
				
				const $target: JQuery = $( evt.target );
				let $editButton: JQuery = $( '.atum-edit-field' );
				
				// If we are clicking on a editable cell, get the other opened popovers, if not, get them all.
				if ( $target.hasClass( 'atum-edit-field' ) ) {
					$editButton = $editButton.not( $target );
				}
				else if ( $target.closest( '.atum-edit-field' ).length ) {
					$editButton = $editButton.not( $target.closest( '.atum-edit-field' ) );
				}
				
				// Get only the cells with an opened popover.
				$editButton = $editButton.filter( ( index: number, elem: Element ) => {
					const $btn: JQuery = $( elem );
					return typeof $btn.data( 'bs.popover' ) !== 'undefined' && ( $btn.data( 'bs.popover' ).inState || false ) && $btn.data( 'bs.popover' ).inState.click === true;
				} );
				
				this.destroyPopover( $editButton );
				
			} );
		
	}
	
	/**
	 * Bind the popovers
	 *
	 * @param {JQuery} $editButtons The edit buttons where are attached the popovers.
	 */
	bindPopovers( $editButtons: JQuery ) {
		
		$editButtons.each( ( index: number, elem: Element ) => {
			
			const $editButton: JQuery = $( elem ),
			      content: string     = $( `#${ $editButton.data( 'content-id' ) }` ).html(),
			      setButton: string   = ! $( content ).hasClass( 'alert' ) ? `<button class="set btn btn-primary btn-sm">${ this.settings.get( 'setButton' ) }</button>` : '';
			
			( <any>$editButton ).popover( {
				content  : content + setButton,
				html     : true,
				template : `
					<div class="popover edit-field-popover" role="tooltip">
						<div class="popover-arrow"></div>
						<h3 class="popover-title"></h3>
						<div class="popover-content"></div>
					</div>`,
				placement: $editButton.data('placement') || 'bottom',
				trigger  : 'click',
				container: 'body',
			} );
			
			// Prepare the popover's fields when shown.
			$editButton.on( 'shown.bs.popover', ( evt: JQueryEventObject ) => {
				
				const $activePopover: JQuery = $( '.popover.in' ),
				      currentValue: string   = $editButton.siblings( 'input[type=hidden]' ).val();
				
				$( evt.currentTarget ).attr( 'data-popover', $activePopover.attr( 'id' ) );
				
				if ( $( `#${ $activePopover.attr( 'id' ) } .select2` ).length === 0 ) {
					$( `#${ $activePopover.attr( 'id' ) } .select2` ).remove();
				}
				
				this.prepareSelect( $editButton );
				
				if ( $activePopover.find( 'select[multiple]' ).length ) {
					
					if ( currentValue ) {
						$activePopover.find( 'select' ).val( currentValue.split( ',' ) ).change();
					}
					
				}
				else {
					$activePopover.find( '.meta-value' ).val( currentValue ).change();
				}
				
				$activePopover.find( '.meta-value' ).focus().select();
				
			} );
			
		} );
		
	}
	
	/**
	 * Destroy the popovers
	 *
	 * @param {JQuery} $editButton The edit button that holds the popover to destroy.
	 */
	destroyPopover( $editButton: JQuery ) {
		
		if ( $editButton.length ) {
			
			( <any>$editButton ).popover( 'destroy' );
			$editButton.removeAttr( 'data-popover' );
			
			// Give a small lapse to complete the 'fadeOut' animation before re-binding.
			setTimeout( () => this.bindPopovers( $editButton ), 300 );
			
		}
		
	}
	
	/**
	 * Add the enhancedSelect components to the popover
	 *
	 * @param {JQuery} $editButton
	 */
	prepareSelect( $editButton: JQuery ) {
		
		if ( this.enhancedSelect) {
			
			$( '.popover .atum-select2' ).each( ( index: number, elem: Element ) => {
				
				const $select: JQuery = $(elem),
				      selectOptions: any = {
					      minimumResultsForSearch: 20,
					      placeholder            : {
						      id  : '-1',
						      text: $select.find('option').first().text()
					      }
				      };
				
				if ( $select.hasClass( 'atum-select-multiple' ) && $select.prop( 'multiple' ) === false ) {
					$select.val( $editButton.siblings( 'input[type=hidden]' ).val().split( ',' ) );
				}
				
				$select.css('width', '200px');
				this.enhancedSelect.doSelect2( $select, selectOptions, true );
				
			});
		
		}
		
	}
	
}
