<?php
/**
 * REST ATUM API Inventory Log Notes controller
 * Handles requests to the /atum/inventory-logs/<order_id>/notes endpoint.
 *
 * @since       1.6.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Api\Controllers
 * @subpackage  V3
 */

namespace Atum\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Models\Log;

class InventoryLogNotesController extends AtumOrderNotesController {

	/**
	 * Route base
	 *
	 * @var string
	 */
	protected $rest_base = 'atum/inventory-logs/(?P<order_id>[\d]+)/notes';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = InventoryLogs::POST_TYPE;

	/**
	 * Get the current order object
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return Log
	 */
	protected function get_atum_order( $request ) {

		if ( is_null( $this->order ) ) {
			$this->order = new Log( (int) $request['order_id'] );
		}

		return $this->order;

	}

	/**
	 * Prepare links for the request
	 *
	 * @since 1.6.2
	 *
	 * @param \WP_Comment $note Delivery order_note object.
	 *
	 * @return array Links for the given order note.
	 */
	protected function prepare_links( $note ) {

		$order_id    = (int) $note->comment_post_ID;
		$links       = parent::prepare_links( $note );
		$links['up'] = array(
			'href' => rest_url( sprintf( '/%s/atum/inventory-logs/%d', $this->namespace, $order_id ) ),
		);

		return $links;

	}
	
}
