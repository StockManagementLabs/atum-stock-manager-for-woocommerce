/* =======================================
   LIGHT BOX
   ======================================= */

let LightBox = {
	
	init() {
		
		$('.thumb').each( (index, elem) => {
			
			const $image         = $(elem).find('img'),
			      $containerId   = 'thumb' + index,
			      $adminMenuMain = $('#adminmenumain'),
			      $wpAdminBar    = $('#wpadminbar');
			
			$(this).attr('id', $containerId);
			
			if ( $image.length > 0 ) {
				lightGallery(document.getElementById($containerId), {
					download: false,
					counter : false,
				});
			}
			
			$('#' + $containerId).on('onBeforeOpen.lg', () => {
				$adminMenuMain.hide();
				$wpAdminBar.hide();
			});
			
			$('#' + $containerId).on('onBeforeClose.lg', () => {
				$adminMenuMain.show();
				$wpAdminBar.show();
			});
			
		});
		
	},
	
}

module.exports = LightBox;