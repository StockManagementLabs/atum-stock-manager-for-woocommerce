/* ==========================================================
   SHOW/HIDE TABLE FILTERS BUTTON BEHAVIOR FOR MOBILE SCREENS
   ========================================================== */

import Settings from '../../config/_settings';

export default class ShowFilters {
	
	constructor(
		private $wrapper: JQuery,
		private settings:Settings
	) {
		
		this.$wrapper.on('click', '.show-filters', (evt: JQueryEventObject) => {
			
			const button: HTMLElement = <HTMLElement>evt.target,
			      $parent: JQuery = $(button).parent() ;
			
			if ( 'show' === button.dataset.action ) {
				button.dataset.action = 'hide';
				button.innerText = this.settings.get('hideFilters');
				$parent.next().slideDown()
					.closest('.filters-container-box').next().children('.btn').removeClass('hidden-sm');
				
			}
			else {
				button.dataset.action = 'show';
				button.innerText = this.settings.get('showFilters');
				$parent.next().slideUp()
					.closest('.filters-container-box').next().children('.btn').addClass('hidden-sm');
			}
		})
	}
	
}