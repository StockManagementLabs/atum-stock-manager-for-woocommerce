/* ====================
   SETTINGS
   ==================== */


export default class Settings {

	settings: any = {};

	constructor(
		private varName: string,
		private defaults: any = {},
	) {

		const localizedOpts: any = typeof window[ varName ] !== 'undefined' ? window[ varName ] : {};

		// Merge all the settings.
		Object.assign( this.settings, defaults, localizedOpts );

	}

	get( prop: string ) {

		if ( typeof this.settings[ prop ] !== 'undefined' ) {
			return this.settings[ prop ];
		}

		return undefined;

	}

	getAll() {
		return this.settings;
	}

	delete( prop: string ) {

		if ( this.settings.hasOwnProperty( prop ) ) {
			delete this.settings[ prop ];
		}

	}
	
}
