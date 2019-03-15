/* =======================================
   COLOR PICKERS
   ======================================= */

// It needs the WP color picker script loaded in order to work.

export let ColorPicker = {
	
	doColorPickers(selectColorText: string) {
		
		$('.atum-color').each( (index: number, elem: Element) => {
			
			const $colorField: any = $(elem);
			
			$colorField.wpColorPicker({
				change: (evt: any, ui: any) => {
					
					const value = $(evt.target).val();
					$('.wp-picker-active .color-picker-preview').css('background-color', value);
					$('.wp-picker-active .wp-color-result-text').html(value);
					
				}
			});
			
		});
		
		$('.wp-color-result').prepend('<span class="color-picker-preview"></span>');
		
		$('.wp-picker-container').each( (index: number, elem: Element) => {
			
			const $colorPicker: JQuery = $(elem),
			      value: string        = $colorPicker.find('.atum-color').val();
			
			if ( value ) {
				$colorPicker.find('.color-picker-preview').css('background-color', value);
				$colorPicker.find('.wp-color-result-text').html(value);
			}
			else {
				$colorPicker.find('.color-picker-preview').css('background-color', value);
				$colorPicker.find('.wp-color-result-text').html(selectColorText);
			}
			
		});
		
	},
	
}
