/* =======================================
   NICE SCROLL
   ======================================= */

const NiceScroll = {
	
	getScrollBars($elem: JQuery) {
		return $elem.find('.scroll-box');
	},
	
	addScrollBars($elem: JQuery, opts?: any) {
		
		const $boxSelector: any = this.getScrollBars($elem);
		
		if ($boxSelector.length) {
			
			opts = Object.assign( {
				cursorcolor       : '#e1e1e1',
				cursoropacitymin  : 0.8,
				cursorwidth       : '4px',
				cursorborderradius: '3px',
				background        : 'rgba(225, 225, 225, 0.3)',
				bouncescroll      : false
			}, opts);
			
			$boxSelector.niceScroll(opts);
			
		}
		
	},
	
	removeScrollBars($elem: JQuery) {
		
		const $boxSelector: any = this.getScrollBars($elem);
		
		if ($boxSelector.length) {
			$boxSelector.getNiceScroll().remove();
		}
		
	},
	
	resizeScrollBars($elem: JQuery) {
		
		const $boxSelector: any = this.getScrollBars($elem);
		
		if ($boxSelector.length) {
			$boxSelector.getNiceScroll().resize();
		}
		
	},
	
}

export default NiceScroll;