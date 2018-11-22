<?php
/**
 * WC Grouped Product data store: using legacy tables
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

class WCProductGroupedDataStoreCPT extends \WC_Product_Grouped_Data_Store_CPT {
	
	use AtumDataStoreLegacyCustomTableTrait, AtumDataStoreCommonCustomTableTrait;
	
}
