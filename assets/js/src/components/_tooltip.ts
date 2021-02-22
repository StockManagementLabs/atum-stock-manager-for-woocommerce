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
			    if ( this.getInstance( $tipEl ) ) {
			    	return;
			    }

			    new BsTooltip( $tipEl.get( 0 ), {
				    html     : true,
				    title    : title,
				    container: 'body',
				    delay: {
				    	show: 100,
					    hide: 200
				    }, // The delay fixes an issue with tooltips breaking on some cases.
			    } );

			    /**
			     * NOTE: for some reason (probably a Boostrap bug), after destroying a tooltip and recreating it,
			     * it's showing ghost tooltips on the left top corner when hovering the recreated tooltips.
			     * So we are removing them all with this method.
			     * If this is fixed in a future version, it should be removed from here
			     */
			    $tipEl.on( 'inserted.bs.tooltip', ( evt: JQueryEventObject ) => {
				    const tooltipId: string = $( evt.currentTarget ).attr( 'aria-describedby' );
				    $( '.tooltip[class*="bs-tooltip-"]' ).not( `#${ tooltipId }` ).remove();
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

		    const tooltip: BsTooltip = this.getInstance( $( elem ) );

		    if ( tooltip ) {
			    tooltip.dispose();
		    }

	    } );

    }

	/**
	 * Get a tooltip instance from a specific element
	 *
	 * @param {JQuery} $tipEl
	 *
	 * @return {BsTooltip}
	 */
	getInstance( $tipEl: JQuery ): BsTooltip {
    	return BsTooltip.getInstance( $tipEl.get( 0 ) );
    }
	
}
