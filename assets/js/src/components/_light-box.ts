/* =======================================
   LIGHT BOX
   ======================================= */

import 'lightgallery.js/dist/js/lightgallery.min';   // From node_modules

export default class LightBox {
	
	lightGallery: Function;
	
	constructor() {
		
		if (typeof window['lightGallery'] === 'undefined') {
			return;
		}
		
		this.lightGallery = window['lightGallery'];
		
		$('.thumb').each( (index: number, elem: Element) => {
			
			const containerId     = `thumb-${index}`,
			      $thumbContainer = $(elem).attr('id', containerId),
			      $image          = $thumbContainer.find('img');
			
			if ($image.length) {
				
				this.lightGallery($thumbContainer.get(0), {
					download: false,
					counter : false,
					controls: false,
				});
				
			}
			
		});
		
	}
	
}
