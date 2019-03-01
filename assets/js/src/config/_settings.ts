/* ====================
   SETTINGS
   ==================== */


export default class Settings {
	
	settings = {};
	
	constructor(varName:string, defaults = {}) {
		
		let localizedOpts = typeof window[varName] !== 'undefined' ? window[varName] : {};
	
		// Merge all the settings.
		this.settings = $.extend( this.settings, defaults, localizedOpts );
		
	}
	
	get(prop) {
		
		if (typeof this.settings[prop] !== 'undefined') {
			return this.settings[prop];
		}
		
		return undefined;
		
	}
	
	getAll() {
		return this.settings;
	}
	
	delete(prop) {
		
		if ( this.settings.hasOwnProperty(prop)) {
			delete this.settings[prop];
		}
		
	}
	
}
