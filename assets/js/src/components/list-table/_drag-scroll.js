/* =======================================
   DRAG-SCROLL FOR LIST TABLES
   ======================================= */

let DragScroll = {
	
	init: function() {
	
		ListTable.$atumList.on('atum-scroll-bar-loaded', this.loadHammer)
	
	},
	
	loadHammer: function() {
		
		// Drag and drop scrolling on desktops
		const hammertime = new Hammer(ScrollBar.$scrollPane.get(0), {});
		
		hammertime
			// Horizontal drag scroll (JScrollPane)
			.on('panright panleft', function(evt) {
				
				const velocityModifier = 10,
				    displacement       = self.jScrollApi.getContentPositionX() - (evt.distance * (evt.velocityX / velocityModifier));
				
				self.jScrollApi.scrollToX(displacement, false);
				
			})
			// Vertical drag scroll (browser scroll bar)
			.on('panup pandown', function(evt) {
				
				const velocityModifier = 10,
				    displacement       = $(window).scrollTop() - (evt.distance * (evt.velocityY / velocityModifier));
				
				$(window).scrollTop(displacement)
				
			});
		
	}
	
}

module.exports = DragScroll;




