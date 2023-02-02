<?php
/**
 * WC Variable Product data store
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2023 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

class AtumProductVariableDataStoreCPT extends \WC_Product_Variable_Data_Store_CPT {
	
	use AtumDataStoreCPTTrait, AtumDataStoreCommonTrait;
	
}
