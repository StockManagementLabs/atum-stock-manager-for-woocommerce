<?php
/**
 * WC Subscription Product data store
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2024 Stock Management Labs™
 *
 * @since           1.5.4
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

class AtumProductSubscriptionDataStoreCPT extends \WCS_Subscription_Data_Store_CPT {
	
	use AtumDataStoreCPTTrait, AtumDataStoreCommonTrait;
	
}
