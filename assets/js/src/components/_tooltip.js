/* =======================================
   TOOLTIP
   ======================================= */

let Tooltip = {
	
	init() {
		
		this.addTooltips();
		
	},
	
	/**
	 * Enable tooltips
	 *
	 * @param jQuery $wrapper   Optional. The wrapper where the elements with tooltips are contained,
	 */
	addTooltips($wrapper = null) {
		
		if (!$wrapper) {
			$wrapper = $('body');
		}
		
		$wrapper.find('.tips, .atum-tooltip').each( (index, elem) => {
			
			const $tipEl = $(elem);
			
			$tipEl.tooltip({
				html     : true,
				title    : $tipEl.data('tip'),
				container: 'body',
			});
			
		});
		
		$wrapper.find('.select2-selection__rendered').each( (index, elem) => {
			
			const $tipEl = $(elem);
			
			$tipEl.tooltip({
				html     : true,
				title    : $tipEl.attr('title'),
				container: 'body',
			});
			
		});
		
	},
	
	/**
	 * Destroy all the tooltips
	 *
	 * @param jQuery $wrapper   Optional. The wrapper where the elements with tooltips are contained
	 */
	destroyTooltips($wrapper = null) {
		
		if (!$wrapper) {
			$wrapper = $('body');
		}
		
		$wrapper.find('.tips, .atum-tooltip, .select2-selection__rendered').tooltip('destroy');
		
	},
	
}

module.exports = Tooltip;
