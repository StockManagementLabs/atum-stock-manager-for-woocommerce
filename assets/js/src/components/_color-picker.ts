/* =======================================
   COLOR PICKERS
   ======================================= */

// It needs the WP color picker script loaded in order to work.

const ColorPicker = {
	
	doColorPickers(selectColorText: string) {
		
		$('.atum-color').each( (index: number, elem: Element) => {
			
			const $colorField: any = $(elem);
			
			$colorField.wpColorPicker({
				change: (evt: any, ui: any) => {
					
					const value = ui.color.toString(),
					$container: JQuery = $(evt.target).closest('.wp-picker-container');
				
					$container.find('.color-picker-preview').css('background-color', value);
					$container.find('.wp-color-result-text').html(value);
				},
				clear: ( evt: any ) => {
					const $container: JQuery = $(evt.target).closest('.wp-picker-container');
					
					$container.find('.color-picker-preview').css('background-color', '');
					$container.find('.wp-color-result-text').html('');
					
				},
			});
			
		});
		
		$('.wp-color-result').prepend('<span class="color-picker-preview"></span>');
		
		$('.wp-picker-container').addClass('atum-color-picker');
		
		this.updatePreviewValues();
		
	},

    updateColorPicker(element: any, selectColorText: string) {

	    element.wpColorPicker('color', selectColorText);
	    this.updatePreviewValues();

    },
    
    updatePreviewValues() {
	
	    $('.wp-picker-container').each( (index: number, elem: Element) => {
		
		    const $colorPicker: JQuery = $(elem),
		          value: string        = $colorPicker.find('.atum-color').val();
		
		    if ( value ) {
			    $colorPicker.find('.color-picker-preview').css('background-color', value);
		    }
		    else {
			    $colorPicker.find('.color-picker-preview').css('background-color', value);
		    }
		
	    });
    }
	
};

export default ColorPicker;