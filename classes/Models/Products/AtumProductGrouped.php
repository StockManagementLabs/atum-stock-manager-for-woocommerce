<?php
/**
 * An abstraction layer of WC Grouped Product to be able to handle ATUM's custom data.
 *
 * @package         Atum\Models
 * @subpackage      Products
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\Products;

defined( 'ABSPATH' ) || exit;

use Atum\Models\Interfaces\AtumProductInterface;


class AtumProductGrouped extends \WC_Product_Grouped implements AtumProductInterface {

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

		do_action( 'atum/model/product_grouped', $product );

	}

}
