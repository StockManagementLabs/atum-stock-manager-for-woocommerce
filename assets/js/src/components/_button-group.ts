/* =======================================
   BUTTON GROUPS
   ======================================= */

/**
 * Third party plugins
 */
import 'bootstrap/js/dist/button';      // From node_modules


export let ButtonGroup = {

	doButtonGroups($container: JQuery) {
		
		// Force change event triggering on Bootstrap radio buttons (for some reason are not being triggered anymore).
		$container.on('click', '.btn-group .btn', (evt: JQueryEventObject) => {
			
			// Wait until Bootstrap manage to do its stuff.
			setTimeout( () => {

                const $button: JQuery = $(evt.target);
				$button.find('input').change();
				
			}, 100);
			
			
		});
	
	}

}