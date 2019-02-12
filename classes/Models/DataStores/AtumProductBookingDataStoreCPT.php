<?php
/**
 * WC Booking Product data store: using legacy tables
 *
 * @package         Atum\Models
 * @subpackage      DataStores
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2019 Stock Management Labs™
 *
 * @since           1.5.4
 */

namespace Atum\Models\DataStores;

defined( 'ABSPATH' ) || die;

class AtumProductBookingDataStoreCPT extends \WC_Product_Booking_Data_Store_CPT {
	
	use AtumDataStoreCPTTrait, AtumDataStoreCommonTrait;
	
}
