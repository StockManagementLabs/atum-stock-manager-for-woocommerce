/* =======================================
   TOOLTIP
   ======================================= */

import BsTooltip from 'bootstrap/js/dist/tooltip';

export default class Tooltip {
	
	constructor( initialize: boolean = true ) {

		if ( initialize ) {
			this.addTooltips();
		}
		
	}

    /**
     * Enable tooltips
     *
     * @param {JQuery} $wrapper Optional. The wrapper where the elements with tooltips are contained,
     */
    addTooltips( $wrapper?: JQuery ) {

	    if ( ! $wrapper ) {
		    $wrapper = $( 'body' );
	    }

	    $wrapper.find( '.tips, .atum-tooltip' ).each( ( index: number, elem: Element ) => {

		    const $tipEl: JQuery = $( elem ),
		          title: string  = $tipEl.data( 'tip' ) || $tipEl.attr( 'title' );

		    if ( title ) {

		    	// Do not add the tooltip twice.
			    const tooltipInstance: BsTooltip = BsTooltip.getInstance( $tipEl.get( 0 ) );

			    if ( tooltipInstance ) {
			    	return;
			    }

			    new BsTooltip( $tipEl.get( 0 ), {
				    html     : true,
				    title    : title,
				    container: 'body',
				    trigger  : 'hover',
				    delay: {
				    	show: 100,
					    hide: 200
				    }, // The delay fixes an issue with tooltips breaking on some cases.
			    } );

		    }

	    } );

    }

    /**
     * Destroy all the tooltips
     *
     * @param {JQuery} $wrapper Optional. The wrapper where the elements with tooltips are contained
     */
    destroyTooltips( $wrapper?: JQuery ) {

	    if ( ! $wrapper ) {
		    $wrapper = $( 'body' );
	    }

	    $wrapper.find( '.tips, .atum-tooltip' ).each( ( index: number, elem: Element ) => {

	    	const $elem: JQuery = $( elem );

		    if ( typeof $elem.attr( 'aria-describedby' ) !== 'undefined' ) {
			    const tooltip: BsTooltip = BsTooltip.getInstance( $elem.get( 0 ) );

			    if ( tooltip ) {
				    tooltip.dispose();
			    }
		    }

	    } );

    }
	
}
