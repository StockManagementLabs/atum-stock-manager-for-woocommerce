/* =======================================
   BEFORE UNLOAD PROMPT
   ======================================= */

const BeforeUnload = {

	addPrompt( checkCallback: Function ) {

		// Before unload alert.
		$( window ).on( 'beforeunload', (): boolean | void => {

			if ( checkCallback() ) {
				return;
			}

			// Prevent multiple prompts - seen on Chrome and IE.
			if ( navigator.userAgent.toLowerCase().match( /msie|chrome/ ) ) {

				if ( window[ 'aysHasPrompted' ] ) {
					return;
				}

				window[ 'aysHasPrompted' ] = true;
				window.setTimeout( () => window[ 'aysHasPrompted' ] = false, 900 );

			}

			return false;

		} );

	},
	
}

export default BeforeUnload;