<?php
/**
 * Shipping Method generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class ShippingMethodGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'shipping-method';

	/**
	 * Transform shipping method data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $shipping_method Raw shipping method data.
	 *
	 * @return array Prepared shipping method data.
	 */
	protected function prepare_data( array $shipping_method ): array {

		// Validate shipping method type against allowed enum values.
		$allowed_types = [ 'flat_rate', 'free_shipping', 'local_pickup' ];
		$type          = in_array( $shipping_method['id'], $allowed_types ) ? $shipping_method['id'] : 'flat_rate';

		return array_merge( 
			$this->get_base_fields(), 
			[
				'itemType'     => 'shipping-method',
				'id'           => $shipping_method['id'] ?? null,
				'title'        => $shipping_method['title'] ?? null,
				'description'  => $shipping_method['description'] ?? null,
				'type'         => $type,
				'slug'         => isset($shipping_method['title']) ? sanitize_title( $shipping_method['title'] ) : null,
			] 
		);
	}

} 