/*
 * =======================================
 * BEFORE UNLOAD PROMPT
 * =======================================
 */

const BeforeUnload = {

    /**
     * Add a before unload prompt.
     *
     * @param checkCallback - Function to check if the prompt should not be shown.
     */
    addPrompt( checkCallback: Function ) {

        // Before unload alert.
        $( window ).on( 'beforeunload.atum', (): boolean | void => {

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
    
    /**
     * Remove the before unload prompt.
     */
    removePrompt() {
        $( window ).off( 'beforeunload.atum' );
    },
	
};

export default BeforeUnload;
