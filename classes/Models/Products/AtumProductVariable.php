<?php
/**
 * An abstraction layer of WC Variable Product to be able to handle ATUM's custom data.
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


class AtumProductVariable extends \WC_Product_Variable implements AtumProductInterface {

	// Import the shared stuff.
	use AtumProductTrait;

	/**
	 * Initialize ATUM variable product
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product|int $product Product instance or ID.
	 */
	public function __construct( $product = 0 ) {

		$this->data = apply_filters( 'atum/model/product_variable/data', array_merge( $this->data, $this->atum_data ) );
		parent::__construct( $product );

		do_action( 'atum/model/product_variable', $product );

	}

}
