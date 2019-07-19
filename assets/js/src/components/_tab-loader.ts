/* =======================================
   TAB LOADER
   ======================================= */

import '../../vendor/jquery.address.min';

export default class TabLoader {
	
	navigationReady: boolean = false;
	numHashParameters: number = 0;
	
	constructor(
		private $tabsWrapper: JQuery,
		private $nav: JQuery
	) {
		
		if (typeof $.address === 'undefined') {
			return;
		}
		
		// Hash history navigation.
		$.address.change( (evt: JQueryEventObject) => {
			
			const pathNames: string[]      = $.address.pathNames(),
			      numCurrentParams: number = pathNames.length;
			
			if(this.navigationReady === true && (numCurrentParams || this.numHashParameters !== numCurrentParams)) {
				this.clickTab(pathNames[0]);
			}
			
			this.navigationReady = true;
			
		})
		.init( () => {
			
			const pathNames: string[] = $.address.pathNames();
			
			// When accessing externally or reloading the page, update the fields and the list.
			if (pathNames.length) {
				this.clickTab(pathNames[0]);
			}
			else {
				this.$tabsWrapper.trigger('atum-tab-loader-init');
			}
			
			const searchQuery: string = location.search.substr(1),
			      searchParams: any   = {};
			
			searchQuery.split('&').forEach( (part: string) => {
				const item: string[] = part.split('=');
				searchParams[item[0]] = decodeURIComponent(item[1]);
			});
			
			if (searchParams.hasOwnProperty('tab')) {
				this.$tabsWrapper.trigger('atum-tab-loader-page-loaded', [searchParams.tab]);
			}
			
		});
		
	}
	
	clickTab(tab: string) {
		
		const $navLink: JQuery = this.$nav.find(`.atum-nav-link[data-tab="${ tab }"]`);
		this.$tabsWrapper.trigger('atum-tab-loader-clicked-tab', [$navLink, tab]);
		
	}
	
	static getCurrentTab(): string {
		
		const pathNames: string[] = $.address.pathNames();
		return pathNames.length ? pathNames[0] : ''; // We understand that there is only one param here.
		
	}
	
}