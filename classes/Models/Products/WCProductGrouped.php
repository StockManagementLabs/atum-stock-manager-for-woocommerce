<?php
/**
 * An abstraction layer of WC Grouped Product to be able to handle ATUM's custom data.
 *
 * @package         Atum\Models
 * @subpackage      Products
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\Products;

defined( 'ABSPATH' ) || exit;

class WCProductGrouped extends \WC_Product_Grouped {

	// Import the shared stuff.
	use AtumProductTrait;

	/**
	 * Initialize ATUM grouped product
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product|int $product Product instance or ID.
	 */
	public function __construct( $product = 0 ) {

		$this->data = apply_filters( 'atum/model/product_grouped/data', array_merge( $this->data, $this->atum_data ) );
		parent::__construct( $product );

	}


}
