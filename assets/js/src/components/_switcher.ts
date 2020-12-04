/* =======================================
   SWITCHERS
   ======================================= */

import Switchery from 'switchery-npm/index';

interface SwitcherOptions {
	className?: string;
	color?: string;
	disabled?: boolean;
	disabledOpacity?: number;
	jackColor?: string;
	jackSecondaryColor?: string;
	secondaryColor?: string;
	size?: string;
	speed?: string;
}

const Switcher = {
	
	/**
	 * Add the switchery to the specified selectors
	 *
	 * @param string selectorClass
	 * @param Object options
	 * @param JQuery $wrapper
	 */
	doSwitchers(selectorClass: string = '.js-switch', options?: SwitcherOptions, $wrapper?: JQuery) {
		
		options = Object.assign( {
			size               : 'small',
			color              : 'var(--green-light)',
			secondaryColor     : 'var(--gray-200)',
			jackColor          : 'var(--tertiary-var)',
			jackSecondaryColor : 'var(--gray-500)'
		}, options || {});
		
		const $selector : JQuery = $wrapper ? $wrapper.find(selectorClass) : $(selectorClass);
		
		$selector.each( (index: number, elem: Element) => {
			const switchery: any = new Switchery(elem, options);
			$(elem).removeClass( selectorClass.replace('.', '') ).data('switchery-instance', switchery);
		} );
		
	}
	
}

export default Switcher;
