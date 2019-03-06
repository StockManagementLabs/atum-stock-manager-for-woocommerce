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
	 * @param jQuery $selector
	 * @param Object options
	 */
	doSelect2($selector: any, options?: any) {
		
		options = options || {
			minimumResultsForSearch: 10,
		};
	
		$selector.select2(options);
		
		$selector.siblings('.select2-container').addClass('atum-select2');
	
	}
	
	/**
	 * Add the ATUM classes to all the enhanced selects to avoid conflicts with other selects
	 */
	addAtumClasses() {
		
		$('select').filter('.atum-select2, .atum-enhanced-select').each( (index: number, elem: any) => {
			
			const $select: JQuery           = $(elem),
			      $select2Container: JQuery = $select.siblings('.select2-container').not('.atum-select2, .atum-enhanced-select')
			
			if ($select2Container.length) {
				$select2Container.addClass( $select.hasClass('atum-select2') ? 'atum-select2' : 'atum-enhanced-select' );
			}
			
		});
		
	}
	
}
