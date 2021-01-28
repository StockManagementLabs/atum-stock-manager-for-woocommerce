<?php
/**
 * WC Subscription Product data store: using legacy tables
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.5.4
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

class AtumProductSubscriptionDataStoreCPT extends \WCS_Subscription_Data_Store_CPT {
	
	use AtumDataStoreCPTTrait, AtumDataStoreCommonTrait;
	
}
