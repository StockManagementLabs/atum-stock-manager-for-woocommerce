/* ====================
   SETTINGS
   ==================== */


export default class Settings {
	
	settings: any = {};
	
	constructor(varName:string, defaults = {}) {
		
		let localizedOpts: any = typeof window[varName] !== 'undefined' ? window[varName] : {};
	
		// Merge all the settings.
		this.settings = $.extend( this.settings, defaults, localizedOpts );
		
	}
	
	get(prop: string) {
		
		if (typeof this.settings[prop] !== 'undefined') {
			return this.settings[prop];
		}
		
		return undefined;
		
	}
	
	getAll() {
		return this.settings;
	}
	
	delete(prop: string) {
		
		if (this.settings.hasOwnProperty(prop)) {
			delete this.settings[prop];
		}
		
	}
	
}
