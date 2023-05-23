/* =======================================
   BLOCKUI WRAPPER
   ======================================= */

const Blocker = {

	block( $selector: any, opts?: any ) {

		opts = Object.assign( {
			message   : null,
			overlayCSS: {
				background: 'rgba(0, 0, 0, 0.5)',
				opacity   : 1,
			},
		}, opts );

		$selector.block( opts );

	},

	unblock( $selector: any ) {

		$selector.unblock();

		// In case there were some changes on the DOM, and it's not able to completely remove the block UI, do it manually
		if ( $selector.find( '.blockUI' ).length ) {
			$selector.find( '.blockUI' ).remove();
		}

	},
	
}

export default Blocker;