<?php
/**
 * WC Variation Product data store: using legacy tables
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

class AtumProductVariationDataStoreCPT extends \WC_Product_Variation_Data_Store_CPT implements \WC_Object_Data_Store_Interface {
	
	use AtumDataStoreCPTTrait, AtumDataStoreCommonTrait;
}
