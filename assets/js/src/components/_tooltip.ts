/* =======================================
   TOOLTIP
   ======================================= */

import '../../vendor/bootstrap3-custom.min';  // TODO: USE BOOTSTRAP 4

export default class Tooltip {
	
	constructor(initialize: boolean = true) {
		
		if (initialize) {
			this.addTooltips();
		}
		
	}

    /**
     * Enable tooltips
     *
     * @param $wrapper JQuery Optional. The wrapper where the elements with tooltips are contained,
     */
	addTooltips($wrapper?: JQuery) {
		
		if (!$wrapper) {
			$wrapper = $('body');
		}
		
		$wrapper.find('.tips, .atum-tooltip').each( (index: number, elem: Element) => {
			
			const $tipEl: any = $(elem),
			      title: string = $tipEl.data('tip') || $tipEl.attr('title');

			if (title) {
                $tipEl.tooltip({
                    html     : true,
                    title    : $tipEl.data('tip') || $tipEl.attr('title'),
                    container: 'body',
                });

            }
		});
		
	}

    /**
     * Destroy all the tooltips
     *
     * @param $wrapper JQuery Optional. The wrapper where the elements with tooltips are contained
     */
    destroyTooltips($wrapper?: JQuery) {

        if (!$wrapper) {
            $wrapper = $('body');
        }

        (<any>$wrapper.find('.tips, .atum-tooltip')).tooltip('destroy');

    }
	
}
