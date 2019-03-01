/* =======================================
   LIGHT BOX
   ======================================= */

export default class LightBox {
	
	constructor() {
		
		$('.thumb').each( (index:number, elem:any) => {
			
			const containerId   = `thumb-${index}`,
			    $thumbContainer = $(elem).attr('id', containerId),
			    $image          = $thumbContainer.find('img');
			
			if ($image.length) {
				
				window['lightGallery']($thumbContainer.get(0), {
					download: false,
					counter : false,
					controls: false,
				});
				
			}
			
		});
		
	}
	
}
