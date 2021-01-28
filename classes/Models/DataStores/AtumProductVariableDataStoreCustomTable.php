<?php
/**
 * WC Variable Product data store: using new custom tables
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

class AtumProductVariableDataStoreCustomTable extends \WC_Product_Variable_Data_Store_Custom_Table implements \WC_Object_Data_Store_Interface {

	// Just use the custom and overridden methods and leave the rest to parent class.
	use AtumDataStoreCustomTableTrait, AtumDataStoreCommonTrait;

}
