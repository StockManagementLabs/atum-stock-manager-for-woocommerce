<?php
/**
 * Shared trait for Atum Products
 *
 * @package         Atum\Models
 * @subpackage      Products
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Models\Products;

defined( 'ABSPATH' ) || die;

use Atum\Suppliers\Suppliers;


trait AtumProductTrait {

	/**
	 * Add the ATUM data to the ATUM
	 *
	 * @var bool
	 */
	protected $atum_data = array(
		'purchase_price'        => '',
		'supplier_id'           => 0,
		'supplier_sku'          => '',
		'atum_controlled'       => FALSE,
		'out_stock_date'        => NULL,
		'out_stock_threshold'   => '',
		'inheritable'           => FALSE,
		// Extra props (from ATUM add-ons).
		'minimum_threshold'     => NULL,
		'available_to_purchase' => NULL,
		'selling_priority'      => NULL,
	);


	/*
	|--------------------------------------------------
	| GETTERS
	|--------------------------------------------------
	|
	| Methods for getting data from the product object.
	*/

	/**
	 * Returns the product's purchase price.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_purchase_price( $context = 'view' ) {
		return $this->get_prop( 'purchase_price', $context );
	}

	/**
	 * Returns the product's supplier ID.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_supplier_id( $context = 'view' ) {
		return $this->get_prop( 'supplier_id', $context );
	}

	/**
	 * Returns the product's supplier SKU.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_supplier_sku( $context = 'view' ) {
		return $this->get_prop( 'supplier_sku', $context );
	}

	/**
	 * Returns the ATUM's control status.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string 'yes' or 'no'
	 */
	public function get_atum_controlled( $context = 'view' ) {
		return wc_bool_to_string( $this->get_prop( 'atum_controlled', $context ) );
	}

	/**
	 * Returns the product's out of stock date.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_out_stock_date( $context = 'view' ) {
		return $this->get_prop( 'out_stock_date', $context );
	}

	/**
	 * Returns the product's out of stock threshold.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_out_stock_threshold( $context = 'view' ) {
		return $this->get_prop( 'out_stock_threshold', $context );
	}

	/**
	 * Returns the product's inheritable prop.
	 *
	 * @since 1.5.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string 'yes' or 'no'
	 */
	public function get_inheritable( $context = 'view' ) {
		return wc_bool_to_string( $this->get_prop( 'inheritable', $context ) );
	}


	/****************************************
	 * EXTRA GETTERS USED BY PREMIUM ADD-ONS
	 ****************************************/


	/**
	 * Returns the product's minimum threshold prop.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_minimum_threshold( $context = 'view' ) {
		return $this->get_prop( 'minimum_threshold', $context );
	}

	/**
	 * Returns the product's available to purchase per user prop.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float|null
	 */
	public function get_available_to_purchase( $context = 'view' ) {
		return $this->get_prop( 'available_to_purchase', $context );
	}

	/**
	 * Returns the product's selling priority prop.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int|null
	 */
	public function get_selling_priority( $context = 'view' ) {
		return $this->get_prop( 'selling_priority', $context );
	}


	/*
	|----------------------------------------------------------------------------
	| SETTERS
	|----------------------------------------------------------------------------
	|
	| Functions for setting product data. These should not update anything in the
	| database itself and should only change what is stored in the class object.
	*/

	/**
	 * Set the product's purchase price.
	 *
	 * @since 1.5.0
	 *
	 * @param string $purchase_price
	 */
	public function set_purchase_price( $purchase_price ) {
		$this->set_prop( 'purchase_price', '' === $purchase_price ? '' : wc_format_decimal( $purchase_price ) );
	}

	/**
	 * Set the product's supplier ID.
	 *
	 * @since 1.5.0
	 *
	 * @param int $supplier_id
	 */
	public function set_supplier_id( $supplier_id ) {
		$this->set_prop( 'supplier_id', absint( $supplier_id ) );
	}

	/**
	 * Set product supplier's SKU.
	 *
	 * @since 1.5.0
	 *
	 * @param string $supplier_sku
	 */
	public function set_supplier_sku( $supplier_sku ) {

		$supplier_sku = (string) $supplier_sku;

		if ( $supplier_sku ) {

			$supplier_sku_found = apply_filters( 'atum/model/product/supplier_sku_found', Suppliers::get_product_id_by_supplier_sku( $this->get_id(), $supplier_sku ), $supplier_sku, $this );

			if ( $this->get_object_read() && $supplier_sku_found ) {
				$this->error( 'product_invalid_supplier_sku', __( 'Invalid or duplicated Supplier SKU.', ATUM_TEXT_DOMAIN ), 400, array( 'resource_id' => $supplier_sku_found ) );
			}

		}

		$this->set_prop( 'supplier_sku', $supplier_sku );

	}

	/**
	 * Set if the product is controlled by ATUM.
	 *
	 * @since 1.5.0
	 *
	 * @param string|bool $atum_controlled Whether or not the ATUM control switch is enabled.
	 */
	public function set_atum_controlled( $atum_controlled ) {
		$this->set_prop( 'atum_controlled', wc_string_to_bool( $atum_controlled ) );
	}

	/**
	 * Set out of stock date.
	 *
	 * @since 1.5.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 *                                  If the DateTime string has no timezone or offset, WordPress site timezone will be assumed.
	 *                                  Null if there is no date.
	 */
	public function set_out_stock_date( $date = NULL ) {
		$this->set_date_prop( 'out_stock_date', $date );
	}

	/**
	 * Set out of stock threshold for the current product.
	 *
	 * @since 1.5.0
	 *
	 * @param int|string $amount Empty string if value not set.
	 */
	public function set_out_stock_threshold( $amount ) {
		$this->set_prop( 'out_stock_threshold', is_null( $amount ) || '' === $amount ? '' : wc_stock_amount( $amount ) );
	}

	/**
	 * Set if the product is from an inheriable type.
	 *
	 * @since 1.5.0
	 *
	 * @param string|bool $inheritable Whether or not the product is inheritable by others.
	 */
	public function set_inheritable( $inheritable ) {
		$this->set_prop( 'inheritable', wc_string_to_bool( $inheritable ) );
	}


	/****************************************
	 * EXTRA SETTERS USED BY PREMIUM ADD-ONS
	 ****************************************/

	/**
	 * Set minimum threshold for the current product.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param int|string $amount Empty string if value not set.
	 */
	public function set_minimum_threshold( $amount ) {
		$this->set_prop( 'minimum_threshold', is_null( $amount ) || '' === $amount ? '' : wc_stock_amount( $amount ) );
	}

	/**
	 * Set available to purchase per user for the current product.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param int|string $amount Empty string if value not set.
	 */
	public function set_available_to_purchase( $amount ) {
		$this->set_prop( 'available_to_purchase', is_null( $amount ) || '' === $amount ? '' : wc_stock_amount( $amount ) );
	}

	/**
	 * Set selling priority for the current product.
	 *
	 * @since   1.5.4
	 * @package Product Levels
	 *
	 * @param int|string $amount Empty string if value not set.
	 */
	public function set_selling_priority( $amount ) {
		$this->set_prop( 'selling_priority', is_null( $amount ) || '' === $amount ? '' : absint( $amount ) );
	}


	/**
	 * Save the ATUM prodcut data
	 *
	 * @since 1.5.0
	 */
	public function save_atum_data() {

		$data_store = $this->get_data_store();
		/* @noinspection PhpUndefinedMethodInspection */
		$data_store->update_atum_product_data( $this );

	}

}
