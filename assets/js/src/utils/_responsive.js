/* ====================
   RESPONSIVE
   ==================== */


let Responsive = {
	
	_SIZE: null,
	_XS  : 575.98,
	_SM  : 991.98,
	_MD  : 1024.98,
	_LG  : 1199.98,
	
	init() {
		
		// Set device size.
		this.setup();
		
		this.events();
	},
	
	events() {
		
		let self = this;
		
		// Set device size again if the viewport size changes.
		$(window).on('resize.Responsive', () => {
			self.setup();
		});
		
	},
	
	setup() {
		
		let viewportWidth = $(window).width();
		
		if (viewportWidth <= this._XS) {
			this._SIZE = 'xs';
		}
		else if (viewportWidth <= this._SM) {
			this._SIZE = 'sm';
		}
		else if (viewportWidth <= this._MD) {
			this._SIZE = 'md';
		}
		else if (viewportWidth <= this._LG) {
			this._SIZE = 'lg';
		}
		else {
			this._SIZE = 'xl';
		}
		
	},
}

module.exports = Responsive;
