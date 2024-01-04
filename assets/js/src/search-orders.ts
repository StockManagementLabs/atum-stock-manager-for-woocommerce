/**
 * ATUM Search Orders by Column field
 *
 * @copyright Stock Management Labs ©2024
 *
 * @since 1.9.30
 */

/**
 * Components
 */
import SearchOrdersByColumn from './components/orders/_search-orders-by-column';
import Tooltip from './components/_tooltip';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {

	const tooltip: Tooltip = new Tooltip( false );
	new SearchOrdersByColumn( tooltip );

});