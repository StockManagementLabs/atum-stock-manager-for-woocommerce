/**
 * Atum Admin Modals
 *
 * @copyright Stock Management Labs ©2024
 *
 * @since 1.9.27
 */

/**
 * Components
 */
import Settings from './config/_settings';
import AdminModal from './components/_admin-modal';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {
	
	// Get the options from the localized var.
	const settings = new Settings( 'atumAdminModalVars' );
	new AdminModal( settings );
	
});