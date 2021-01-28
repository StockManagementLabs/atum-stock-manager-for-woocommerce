<?php
/**
 * Extends Exception to provide additional data
 *
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2021 Stock Management Labs™
 *
 * @since          1.2.4
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;


class AtumException extends \Exception {

	/**
	 * Sanitized error code
	 *
	 * @var string
	 */
	protected $error_code;

	/**
	 * Error extra data
	 *
	 * @var array
	 */
	protected $error_data;

	/**
	 * Setup exception
	 *
	 * @since 1.2.4
	 *
	 * @param string $code             Machine-readable error code, e.g `atum_invalid_product_id`.
	 * @param string $message          User-friendly translated error message, e.g. 'Product ID is invalid'.
	 * @param int    $http_status_code Proper HTTP status code to respond with, e.g. 400.
	 * @param array  $data             Extra error data.
	 */
	public function __construct( $code, $message, $http_status_code = 400, $data = array() ) {
		$this->error_code = $code;
		$this->error_data = array_merge( array( 'status' => $http_status_code ), $data );

		parent::__construct( $message, $http_status_code );
	}

	/**
	 * Returns the error code
	 *
	 * @since 1.2.4
	 *
	 * @return string
	 */
	public function getErrorCode() {
		return $this->error_code;
	}

	/**
	 * Returns error data
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function getErrorData() {
		return $this->error_data;
	}

}
