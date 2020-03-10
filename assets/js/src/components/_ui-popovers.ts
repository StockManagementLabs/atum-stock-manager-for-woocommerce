/* ===================
   POs POPOVERS
   =================== */

import Settings from '../../../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';

export default class POPopovers {
	
	constructor(
		private settings: Settings
	) {
		
		//
		// Popover events.
		//----------------
		$('body')
		
			// Set the field data.
			.on('click', '.popover .set', (evt: JQueryEventObject) => {
				
				let $setButton: JQuery    = $(evt.currentTarget),
				    popoverId: string     = $setButton.closest('.popover').attr('id'),
				    $fieldWrapper: JQuery = $(`[data-popover="${popoverId}"]`).parent(),
				    $popoverInput: any    = $setButton.closest('.popover-content').find('.meta-value'),
				    newValue: any         = $popoverInput.val(),
				    newLabel: string;
				
				if ($popoverInput.is('select')) {
					
					if (!newValue) {
						newLabel = this.settings.get('none');
					}
					else if ($.isArray(newValue)) {
						
						let selectedLabels: string[] = [];
						
						$popoverInput.find('option:selected').each( (index: number, elem: Element) => {
							selectedLabels.push( $.trim( $(elem).text() ) );
						});
						
						newLabel = selectedLabels.join(', ');
						
					}
					else {
						newLabel = $.trim( $popoverInput.find('option:selected').text() );
					}
					
				}
				else {
					newLabel = newValue ? newValue : this.settings.get('none');
				}
				
				let option: JQuery = $popoverInput.find('option');
				
				option.each( (index: number, elem: Element) => {
					
					const $option = $(elem);
					
					if ( $.inArray($option.val().toString(), newValue) != -1 ) {
						$option.attr('selected', 'selected');
					}
					else {
						$option.removeAttr('selected');
					}
					
				});
				
				$(`#${ $fieldWrapper.find('.edit-col').data('content-id') }`).html($popoverInput);
				
				$fieldWrapper.find('input[type=hidden]').val(newValue);
				$fieldWrapper.find('.field-label').addClass('unsaved').text(newLabel);
				this.destroyPopover($fieldWrapper.find('.edit-col'));
				
			})
			
			.on('keyup', '.popover .meta-value', (evt: JQueryEventObject) => {
				
				const $field = $(evt.currentTarget);
				
				// Enter key.
				if (evt.keyCode === 13) {
					$field.siblings('.set').click();
				}
				// Esc key.
				else if (evt.keyCode === 27) {
					this.destroyPopover( $(`[data-popover="${ $field.closest('.popover').attr('id') }"]`) );
				}
				
			});
		
		// Hide any other opened popover before opening a new one.
		$('#wpbody-content').click( (evt: JQueryEventObject) => {
			
			let $target: JQuery     = $(evt.target),
			    // If we are clicking on a editable cell, get the other opened popovers, if not, get all them all.
			    $editButton: JQuery = $target.hasClass('edit-col') ? $('.atum-edit-field').not($target) : $('.atum-edit-field');
			
			// Get only the cells with an opened popover.
			$editButton = $editButton.filter( (index: number, elem: Element) => {
				const $btn: JQuery = $(elem);
				return typeof $btn.data('bs.popover') !== 'undefined' && ($btn.data('bs.popover').inState || false) && $btn.data('bs.popover').inState.click === true;
			});
			
			this.destroyPopover($editButton);
			
		});
		
	}
	
	/**
	 * Bind the popovers
	 *
	 * @param {JQuery} $editButtons The edit buttons where are attached the popovers.
	 */
	bindPopovers($editButtons: JQuery) {
		
		console.log('binding popovers', $editButtons);
		
		$editButtons.each( (index: number, elem: Element) => {
			
			let $editButton: JQuery = $( elem ),
			    content: string     = $( `#${ $editButton.data( 'content-id' ) }` ).html(),
			    setButton: string   = ! $( content ).hasClass( 'alert' ) ? `<button class="set btn btn-primary btn-sm">${ this.settings.get( 'setButton' ) }</button>` : '';
			
			(<any>$editButton).popover({
				content  : content + setButton,
				html     : true,
				template : `
					<div class="popover edit-field-popover" role="tooltip">
						<div class="popover-arrow"></div>
						<h3 class="popover-title"></h3>
						<div class="popover-content"></div>
					</div>`,
				placement: 'bottom',
				trigger  : 'click',
				container: 'body',
			});
			
			$editButton.on( 'shown.bs.popover', ( evt: JQueryEventObject ) => {
				
				const $activePopover: JQuery = $( '.popover.in' ),
				      currentValue: any      = $editButton.siblings( 'input[type=hidden]' ).val();
				
				$( evt.currentTarget ).attr( 'data-popover', $activePopover.attr( 'id' ) );
				
				if ( $( `#${ $activePopover.attr( 'id' ) } .select2` ).length === 0 ) {
					$( `#${ $activePopover.attr( 'id' ) } .select2` ).remove();
				}
				
				this.addSelect2( $editButton );
				
				if ( $activePopover.find( 'select[multiple]' ).length ) {
					
					if ( currentValue ) {
						$activePopover.find( 'select' ).val( currentValue.split( ',' ) ).change();
					}
					
				}
				else {
					$activePopover.find( ':input' ).val( currentValue ).change();
				}
				
				$activePopover.find( ':input:visible' ).first().focus().select();
				
			} );
			
		});
		
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
	 * Add the select2
	 *
	 * TODO: WHY ARE NOT WE USING THE EnhancedSelect COMPONENT HERE?
	 *
	 * @param {JQuery} $editButton
	 */
	addSelect2($editButton: JQuery) {
		
		$('.atum-select2').each( (index: number, elem: Element) => {
			
			const $select: JQuery = $(elem),
			      selectOptions: any = {
				      minimumResultsForSearch: 20,
				      placeholder            : {
					      id  : '-1',
					      text: $select.find('option').first().text()
				      }
			      };
			
			if ( $select.hasClass( 'atum-select-multiple' ) && $select.prop( 'multiple' ) === false ) {
				$select.prop( 'multiple', true );
				$select.val( $editButton.siblings( 'input[type=hidden]' ).val().split( ',' ) );
			}
			
			(<any>$select)
				.css('width', '200px')
				.select2( selectOptions )
				.on( 'select2:selecting', ( evt: Event ) => {
					
					let $select: JQuery = $( evt.currentTarget ),
					    value: any      = $select.val();
					
					// Avoid selecting the "None" option.
					if ( $.isArray( value ) && $.inArray( '-1', value ) > -1 ) {
						$.each( value, ( index: number, elem: any ) => {
							if ( elem === '-1' ) {
								value.splice( index, 1 );
							}
						} );
						
						$select.val( value );
					}
					
				} );
			
			$select.siblings('.select2-container').addClass('atum-select2');
			
		});
		
	}
	
}