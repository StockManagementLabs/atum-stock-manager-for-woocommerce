<?php
/**
 * The API endpoint resposible to handle the Atum's Products Data
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2019 Stock Management Labs™
 *
 * @package     Atum\Api
 * @subpackage  V3
 *
 * @deprecated WC 2.6.0
 *
 * TODO: DO WE REALLY NEED TO ADD SUPPORT FOR THE DEPRECATED LEGACY API?
 */

namespace Atum\Api\Legacy\V3;

defined( 'ABSPATH' ) || die;

class AtumProductData extends \WC_API_Resource {

	/**
	 * The route base
	 *
	 * @var string
	 */
	protected $base = '/atum/product-data';

	/**
	 * Register the routes for this class
	 *
	 * @since 1.6.2
	 *
	 * @param array $routes
	 *
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET/POST /atum/product-data
		$routes[ $this->base ] = array(
			array( array( $this, 'get_products_data' ), \WC_API_Server::READABLE ),
			array( array( $this, 'create_products_data' ), \WC_API_SERVER::CREATABLE | \WC_API_Server::ACCEPT_DATA ),
		);

		# GET/PUT/DELETE /atum/product-data/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_product_data' ), \WC_API_Server::READABLE ),
			array( array( $this, 'edit_product_data' ), \WC_API_Server::EDITABLE | \WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'delete_product_data' ), \WC_API_Server::DELETABLE ),
		);

		# GET /atum/product-data/<id>/supplier
		$routes[ $this->base . '/(?P<id>\d+)/supplier' ] = array(
			array( array( $this, 'get_product_supplier' ), \WC_API_Server::READABLE ),
		);

		# GET /atum/product-data/<id>/purchase-orders
		$routes[ $this->base . '/(?P<id>\d+)/purchase-orders' ] = array(
			array( array( $this, 'get_product_purchase_orders' ), \WC_API_Server::READABLE ),
		);

		# GET /atum/product-data/<id>/inventory-logs
		$routes[ $this->base . '/(?P<id>\d+)/inventory-logs' ] = array(
			array( array( $this, 'get_product_inventory_logs' ), \WC_API_Server::READABLE ),
		);

		# GET/POST /atum/product-data/locations
		$routes[ $this->base . '/locations' ] = array(
			array( array( $this, 'get_product_locations' ), \WC_API_Server::READABLE ),
			array( array( $this, 'create_product_location' ), \WC_API_Server::CREATABLE | \WC_API_Server::ACCEPT_DATA ),
		);

		# GET/PUT/DELETE /atum/product-data/locations/<id>
		$routes[ $this->base . '/locations/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_product_location' ), \WC_API_Server::READABLE ),
			array( array( $this, 'edit_product_location' ), \WC_API_Server::EDITABLE | \WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'delete_product_location' ), \WC_API_Server::DELETABLE ),
		);

		# POST|PUT /atum/product-data/bulk
		$routes[ $this->base . '/bulk' ] = array(
			array( array( $this, 'bulk' ), \WC_API_Server::EDITABLE | \WC_API_Server::ACCEPT_DATA ),
		);

		return $routes;

	}

}
