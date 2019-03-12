/* =======================================
   BLOCKUI WRAPPER
   ======================================= */

export let Blocker = {
	
	block($selector: any, opts?: any) {
		
		opts = Object.assign({
			message   : null,
			overlayCSS: {
				background: '#000',
				opacity   : 0.5,
			},
		}, opts);
		
		$selector.block(opts);
		
	},
	
	unblock($selector: any) {
		$selector.unblock();
	},
	
}