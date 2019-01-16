/* ====================
   RESPONSIVE
   ==================== */


var Responsive = {
	_SIZE: null,
	_XS  : 575.98,
	_SM  : 991.98,
	_MD  : 1024.98,
	_LG  : 1199.98,
	
	
	_init: function() {
		var that = this;
		
		// Set device size
		that.setup();
		
		that._events();
	},
	
	
	_events: function() {
		var that = this;
		
		// Set device size again if the viewport size changes
		$(window).on('resize.Responsive', function() {
			that.setup();
		});
	},
	
	
	setup: function() {
		var that = this;
		var viewportWidth = $(window).width();
		
		if (viewportWidth <= that._XS) {
			that._SIZE = 'xs';
		}
		else if (viewportWidth <= that._SM) {
			that._SIZE = 'sm';
		}
		else if (viewportWidth <= that._MD) {
			that._SIZE = 'md';
		}
		else if (viewportWidth <= that._LG) {
			that._SIZE = 'lg';
		}
		else {
			that._SIZE = 'xl';
		}
	},
};


module.exports = Responsive;
