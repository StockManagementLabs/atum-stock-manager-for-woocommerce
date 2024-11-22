<?php
/**
 * Variation generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
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

		// Prepare parent data
		$parent = [
			'id'   => (string) $variation['parent_id'],
			'name' => $variation['parent_name'] ?? ''
		];

		// Prepare attributes data
		$attributes = array_map( function ( $attr ) {

			return [
				'name'   => $attr['name'],
				'option' => [
					'name' => $attr['option'],
					'_id'  => 'attribute_option:' . $this->generate_uuid(),
					'id'   => (int) $attr['id']
				],
				'_id'    => 'attribute:' . $this->generate_uuid(),
				'id'     => (int) $attr['id']
			];
		}, $variation['attributes'] ?? [] );

		// Prepare tax class data
		$tax_class = [
			'id'   => $variation['tax_class'] ?: 'standard',
			'name' => ucfirst( $variation['tax_class'] ?: 'standard' ) . ' Rate'
		];

		// Prepare dimensions with proper defaults
		$dimensions = [
			'length' => (float) ($variation['dimensions']['length'] ?? 0),
			'width'  => (float) ($variation['dimensions']['width'] ?? 0),
			'height' => (float) ($variation['dimensions']['height'] ?? 0)
		];

		return [
			'_id'                 => 'variation:' . $this->generate_uuid(),
			'_rev'                => '1-' . $this->generate_revision_id(),
			'_deleted'            => FALSE,
			'_meta'               => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments'        => new \stdClass(),
			'id'                  => (int) $variation['id'],
			'parent'              => $parent,
			'type'                => $variation['type'],
			'name'                => $variation['name'] ?? '',
			'status'              => $variation['status'] ?? 'publish',
			'sku'                 => $variation['sku'] ?? '',
			'barcode'             => $variation['barcode'] ?? '',
			'regularPrice'        => (float) ($variation['regular_price'] ?? 0),
			'salePrice'           => (float) ($variation['sale_price'] ?? 0),
			'stockQuantity'       => (int) ($variation['stock_quantity'] ?? 0),
			'manageStock'         => (bool) ($variation['manage_stock'] ?? false),
			'stockStatus'         => $variation['stock_status'] ?? 'instock',
			'backorders'          => $variation['backorders'] ?? 'no',
			'virtual'             => (bool) ($variation['virtual'] ?? false),
			'downloadable'        => (bool) ($variation['downloadable'] ?? false),
			'taxStatus'           => $variation['tax_status'] ?? 'taxable',
			'taxClass'            => $tax_class,
			'weight'              => (float) ($variation['weight'] ?? 0),
			'dimensions'          => $dimensions,
			'attributes'          => $attributes,
			'menuOrder'           => (int) ($variation['menu_order'] ?? 0),
			'atumControlled'      => (bool) ($variation['atum_controlled'] ?? false),
			'minimumThreshold'    => $variation['minimum_threshold'] ? (float) $variation['minimum_threshold'] : NULL,
			'availableToPurchase' => $variation['available_to_purchase'] ? (float) $variation['available_to_purchase'] : NULL,
			'sellingPriority'     => $variation['selling_priority'] ? (int) $variation['selling_priority'] : NULL,
			'metaData'            => array_map( function ( $meta ) {

				return [
					'key'   => $meta['key'],
					'value' => (string) $meta['value']
				];
			}, $variation['meta_data'] ?? [] ),
			'conflict'            => FALSE,
		];
	}

} 