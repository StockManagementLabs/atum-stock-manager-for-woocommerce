<?php
/**
 * @package         Atum
 * @subpackage      Components
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * The abstract class for the ATUM data model
 */

namespace Atum\Components;


defined( 'ABSPATH' ) or die;


abstract class AtumModel {

	/**
	 * The object ID
	 * @var int
	 */
	protected $id;

	/**
	 * AtumModel constructor
	 *
	 * @param int $id   Optional. The object ID to initialize
	 */
	protected function __construct( $id = 0 ) {

		if ($id) {
			$this->id = absint($id);
		}

	}

	/**
	 * Getter for the id property
	 *
	 * @since 1.2.4
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns requested meta keys' values
	 *
	 * @since 1.2.4
	 *
	 * @param string $meta_key Optional. A string indicating which meta key to retrieve, or NULL to return all keys
	 * @param bool   $single   Optional. TRUE to return the first value, FALSE to return an array of values
	 *
	 * @return string|array
	 */
	abstract public function get_meta( $meta_key = NULL, $single = TRUE );

	/**
	 * Saves the given meta key/value pairs
	 *
	 * @since 1.2.4
	 *
	 * @param array $meta An associative array of meta keys and their values to save
	 * @param bool  $trim
	 *
	 * @return void
	 */
	abstract public function save_meta( $meta = array(), $trim = FALSE );

	/**
	 * Delete the given meta keys
	 *
	 * @since 1.2.4
	 *
	 * @param array $meta
	 */
	abstract public function delete_meta( $meta );

	/**
	 * Internal meta keys we don't want exposed as part of meta_data.
	 * This is in addition to all data props with _ prefix.
	 *
	 * @since 1.2.4
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function prefix_key( $key ) {
		return '_' === substr( $key, 0, 1 ) ? $key : '_' . $key;
	}

}