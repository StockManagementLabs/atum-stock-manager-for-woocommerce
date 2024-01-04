<?php
/**
 * WC Product data store
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2024 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

class AtumProductDataStoreCPT extends \WC_Product_Data_Store_CPT {
	
	use AtumDataStoreCPTTrait, AtumDataStoreCommonTrait;
	
}
