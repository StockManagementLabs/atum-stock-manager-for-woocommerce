/* =======================================
   ENHANCED SELECT
   ======================================= */

export default class EnhancedSelect {
	
	constructor() {
		
		this.addAtumClasses();
		
		$('body').on('wc-enhanced-select-init', () => {
			this.addAtumClasses();
		});
		
	}
	
	/**
	 * Restore the enhanced select filters (if any)
	 */
	maybeRestoreEnhancedSelect() {
		
		$('.select2-container--open').remove();
		$('body').trigger('wc-enhanced-select-init');
		
	}
	
	/**
	 * Add the select2 to the specified selectors
	 *
	 * @param jQuery  $selector
	 * @param Object  options
	 * @param Boolean avoidEmptySelections
	 */
	doSelect2($selector: JQuery, options: any = {}, avoidEmptySelections: boolean = false) {
		
		if (typeof $.fn['select2'] !== 'function') {
			return;
		}
		
		options = Object.assign( {
			minimumResultsForSearch: 10,
		}, options);
		
		$selector.each( (index: number, elem: Element) => {
			
			const $select: any = $(elem);
			
			if ($select.hasClass('atum-select-multiple') && $select.prop('multiple') === false) {
				$select.prop('multiple', true);
			}
			
			if (avoidEmptySelections) {
				
				$select.on('select2:selecting', (evt: Event) => {
					
					let $this = $(evt.currentTarget),
					    value = $this.val();
					
					// Avoid selecting the "None" option
					if ($.isArray(value) && $.inArray('', value) > -1) {
						
						$.each(value, function (index: number, elem: string) {
							if (elem === '') {
								value.splice(index, 1);
							}
						});
						
						$this.val(value);
						
					}
					
				});
				
			}
			
			$select.select2(options);
			$select.siblings('.select2-container').addClass('atum-select2');
			
		} );
	
	}
	
	/**
	 * Add the ATUM classes to all the enhanced selects to avoid conflicts with other selects
	 */
	addAtumClasses() {
		
		$('select').filter('.atum-select2, .atum-enhanced-select').each( (index: number, elem: Element) => {
			
			const $select: JQuery           = $(elem),
			      $select2Container: JQuery = $select.siblings('.select2-container').not('.atum-select2, .atum-enhanced-select')
			
			if ($select2Container.length) {
				$select2Container.addClass( $select.hasClass('atum-select2') ? 'atum-select2' : 'atum-enhanced-select' );
			}
			
		});
		
	}
	
}
