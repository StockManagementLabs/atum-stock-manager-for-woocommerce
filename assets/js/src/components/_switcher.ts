/* =======================================
   SWITCHERS
   ======================================= */

import Switchery from 'switchery-npm/index';

interface SwitcherOptions {
	size?: string;
	color?: string;
	secondaryColor?: string;
	jackColor?: string;
	jackSecondaryColor?: string;
}

export let Switcher = {
	
	/**
	 * Add the switchery to the specified selectors
	 *
	 * @param string selectorClass
	 * @param Object options
	 */
	doSwitchers(selectorClass: string = '.js-switch', options?: SwitcherOptions) {
		
		options = Object.assign( {
			size               : 'small',
			color              : '#d5f5ba',
			secondaryColor     : '#e9ecef',
			jackColor          : '#69c61d',
			jackSecondaryColor : '#adb5bd'
		}, options || {});
		
		$(selectorClass).each( (index: number, elem: Element) => {
			
			new Switchery(elem, options);
			$(elem).removeClass( selectorClass.replace('.', '') );
			
		} );
		
	}
	
}
