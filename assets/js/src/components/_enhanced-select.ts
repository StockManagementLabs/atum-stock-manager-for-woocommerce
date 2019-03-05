/* =======================================
   ENHANCED SELECT
   ======================================= */

export let EnhancedSelect = {
	
	/**
	 * Restore the enhanced select filters (if any)
	 */
	maybeRestoreEnhancedSelect() {
		
		$('.select2-container--open').remove();
		$('body').trigger('wc-enhanced-select-init');
		
	},
	
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
	
	}
	
}
