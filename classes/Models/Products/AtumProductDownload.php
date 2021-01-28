<?php
/**
 * An abstraction layer of WC Download Product to be able to handle ATUM's custom data.
 *
 * @package         Atum\Models
 * @subpackage      Products
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\Products;

defined( 'ABSPATH' ) || exit;

class AtumProductDownload extends \WC_Product_Download {

	// Import the shared stuff.
	use AtumProductTrait;

	/**
	 * Initialize ATUM simple product
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Product|int $product Product instance or ID.
	 */
	public function __construct( $product = 0 ) {

		$this->data = apply_filters( 'atum/model/product_download/data', array_merge( $this->data, $this->atum_data ) );
		/* @noinspection PhpPossiblePolymorphicInvocationInspection */
		parent::__construct( $product );

		do_action( 'atum/model/product_download', $product );

	}

}
