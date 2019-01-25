/* ====================
   SETTINGS
   ==================== */


let Settings = {
	
	settings: {},
	
	init(varName, defaults) {
	
		// Get the settings from the localized var.
		this.settings = $.extend( this.settings, defaults || {}, window[varName] || {})
		
	},
	
	get(prop) {
		
		if (typeof this.settings[prop] !== 'undefined') {
			return this.settings[prop]
		}
		
		return undefined
		
	},
	
	getAll() {
		return this.settings
	}
	
}

module.exports = Settings