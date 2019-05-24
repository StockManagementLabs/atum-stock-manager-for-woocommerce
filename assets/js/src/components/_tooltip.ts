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
			
			const $tipEl: any = $(elem);
			let doTooltip: boolean = false;

			// Only if the container is smaller than the content.
			if ( typeof $tipEl.data('tooltip') !== 'undefined' && $tipEl.data('tooltip') === 'overflown' ) {

			    if ( $tipEl[0].scrollWidth > $tipEl.innerWidth()) {
			        doTooltip = true;
                }
            }
			else {
			    doTooltip = true;
            }

			if (doTooltip) {
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
