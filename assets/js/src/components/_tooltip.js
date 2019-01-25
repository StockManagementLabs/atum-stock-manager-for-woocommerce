/* =======================================
   TOOLTIP
   ======================================= */

let Tooltip = {
	
	init() {
		
		this.addTooltips();
		
	},
	
	/**
	 * Enable tooltips
	 */
	addTooltips() {
		
		$('.tips').each( (index, elem) => {
			
			const $tipEl = $(elem);
			
			$tipEl.tooltip({
				html     : true,
				title    : $tipEl.data('tip'),
				container: 'body',
			});
			
		});
		
		$('.select2-selection__rendered').each( (index, elem) => {
			
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
	 */
	destroyTooltips() {
		$('.tips, .select2-selection__rendered').tooltip('destroy');
	},
	
}

module.exports = Tooltip;
