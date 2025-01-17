<?php
/**
 * Variation generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class VariationGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'variation';

	/**
	 * Transform variation data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $variation Raw variation data
	 *
	 * @return array Prepared variation data
	 */
	protected function prepare_data( array $variation ): array {

		// Prepare attributes data.
		$attributes = array_map( function ( $attr ) {

			return [
				'name'   => $attr['name'],
				'option' => [
					'name' => $attr['option'],
					'_id'  => NULL,
					'id'   => NULL
				],
				'_id'    => NULL,
				'id'     => (int) $attr['id']
			];

		}, $variation['attributes'] ?? [] );

		// Prepare tax class data.
		$tax_class = [
			'id'   => $variation['tax_class'] ?: 'standard',
			'name' => ucfirst( $variation['tax_class'] ?: 'standard' ) . ' Rate'
		];

		// Prepare dimensions with proper defaults.
		$dimensions = [
			'length' => (float) ($variation['dimensions']['length'] ?? 0),
			'width'  => (float) ($variation['dimensions']['width'] ?? 0),
			'height' => (float) ($variation['dimensions']['height'] ?? 0)
		];

		// Prepare meta data.
		$meta_data = array_map( function ( $meta ) {

			return [
				'key'   => $meta['key'],
				'value' => (string) $meta['value']
			];

		}, $variation['meta_data'] ?? [] );

		return [
			'_id'                 => $this->schema_name . ':' . $this->generate_uuid(),
			'_rev'                => $this->revision,
			'_deleted'            => FALSE,
			'_meta'               => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments'        => new \stdClass(),
			'id'                  => (int) $variation['id'],
			'parent'              => $this->prepare_ids( $variation['parent_id'] ),
			'type'                => $variation['type'],
			'name'                => $variation['name'] ?? '',
			'status'              => $variation['status'] ?? 'publish',
			'sku'                 => $variation['sku'] ?? '',
			'barcode'             => $variation['barcode'] ?? '',
			'regularPrice'        => (float) ( $variation['regular_price'] ?? 0 ),
			'salePrice'           => (float) ( $variation['sale_price'] ?? 0 ),
			'stockQuantity'       => (int) ( $variation['stock_quantity'] ?? 0 ),
			'manageStock'         => (bool) ( $variation['manage_stock'] ?? FALSE ),
			'stockStatus'         => $variation['stock_status'] ?? 'instock',
			'backorders'          => $variation['backorders'] ?? 'no',
			'virtual'             => (bool) ( $variation['virtual'] ?? FALSE ),
			'downloadable'        => (bool) ( $variation['downloadable'] ?? FALSE ),
			'taxStatus'           => $variation['tax_status'] ?? 'taxable',
			'taxClass'            => $tax_class,
			'weight'              => (float) ( $variation['weight'] ?? 0 ),
			'dimensions'          => $dimensions,
			'attributes'          => $attributes,
			'menuOrder'           => (int) ( $variation['menu_order'] ?? 0 ),
			'atumControlled'      => (bool) ( $variation['atum_controlled'] ?? FALSE ),
			'minimumThreshold'    => isset( $variation['minimum_threshold'] ) ? (float) $variation['minimum_threshold'] : NULL,
			'availableToPurchase' => isset( $variation['available_to_purchase'] ) ? (float) $variation['available_to_purchase'] : NULL,
			'sellingPriority'     => isset( $variation['selling_priority'] ) ? (int) $variation['selling_priority'] : NULL,
			'metaData'            => $meta_data,
			'conflict'            => FALSE,
		];

	}

} 