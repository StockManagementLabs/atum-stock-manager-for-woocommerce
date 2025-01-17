<?php
/**
 * Product generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class ProductGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'product';

	/**
	 * Transform product data to schema format
	 *
	 * @since 1.9.44
	 *
	 * @param array $product Raw product data.
	 *
	 * @return array Prepared product data.
	 */
	protected function prepare_data( array $product ): array {

		return array_merge( $this->get_base_fields(), [
			// Product specific fields.
			'id'                => (int) $product['id'],
			'name'              => $product['name'],
			'slug'              => $product['slug'],
			'type'              => $product['type'],
			'status'            => $product['status'],
			'description'       => $product['description'],
			'sku'               => $product['sku'],
			'price'             => (float) $product['price'],
			'regularPrice'      => (float) $product['regular_price'],
			'salePrice'         => (float) ( $product['sale_price'] ?: 0 ),
			'featured'          => (bool) $product['featured'],
			'catalogVisibility' => $product['catalog_visibility'],
			'virtual'           => (bool) $product['virtual'],
			'downloadable'      => (bool) $product['downloadable'],
			'manageStock'       => (bool) $product['manage_stock'],
			'stockQuantity'     => $product['stock_quantity'] ? (int) $product['stock_quantity'] : NULL,
			'stockStatus'       => $product['stock_status'],
			'parent'            => $this->prepare_ids( $product['parent'] ?? NULL ),
			'parentSku'         => $product['parent_sku'] ?? '',

			// Date fields (required by schema).
			'dateCreated'       => $product['date_created'] ?? '',
			'dateCreatedGMT'    => $product['date_created_gmt'] ?? '',
			'dateModified'      => $product['date_modified'] ?? '',
			'dateModifiedGMT'   => $product['date_modified_gmt'] ?? '',

			// Arrays and objects.
			'categories'        => $this->prepare_taxonomies( $product['categories'] ),
			'tags'              => $this->prepare_taxonomies( $product['tags'] ),
			'attributes'        => $this->prepare_attributes( $product['attributes'] ),
			'image'             => $this->prepare_image( $product['images'][0] ?? NULL ),
			'gallery'           => $this->prepare_gallery( $product['images'] ),
			'dimensions'        => $this->prepare_dimensions( $product['dimensions'] ),
			'metaData'          => $this->prepare_meta_data( $product['meta_data'] ),
			'atumLocations'     => $this->prepare_taxonomies( $product['atum_locations'] ),

			// ATUM specific fields.
			'hasLocation'       => (bool) $product['has_location'],
			'atumControlled'    => (bool) $product['atum_controlled'],
			'barcode'           => $product['barcode'] ?? '',
		] );

	}

	/**
	 * Prepare taxonomy data
	 *
	 * @since 1.9.44
	 *
	 * @param array $taxonomies Raw taxonomy data
	 *
	 * @return array Prepared taxonomy data
	 */
	private function prepare_taxonomies( array $taxonomies ): array {

		return array_map( function ( $tax ) {

			return [
				'id'   => (int) $tax['id'],
				'name' => $tax['name'],
				'slug' => $tax['slug'],
			];

		}, $taxonomies );
	}

	/**
	 * Prepare attributes data
	 *
	 * @since 1.9.44
	 *
	 * @param array $attributes Raw attributes data
	 *
	 * @return array Prepared attributes data
	 */
	private function prepare_attributes( array $attributes ): array {

		return array_map( function ( $attr ) {

			return [
				'_id'       => NULL,
				'id'        => (int) $attr['id'],
				'name'      => $attr['name'],
				'options'   => array_map( function ( $option_name = '', $option_id = NULL ) {

					return [
						'_id'  => NULL,
						'id'   => $option_id,
						'name' => $option_name,
					];

				}, $attr['options'] ?? [], $attr['option_ids'] ?? [] ),
				'position'  => (int) ( $attr['position'] ?? 0 ),
				'visible'   => (bool) ( $attr['visible'] ?? TRUE ),
				'variation' => (bool) ( $attr['variation'] ?? FALSE ),
			];

		}, $attributes );
	}

	/**
	 * Prepare image data
	 *
	 * @since 1.9.44
	 *
	 * @param array|null $image Raw image data.
	 *
	 * @return array|null Prepared image data.
	 */
	private function prepare_image( ?array $image ): ?array {

		if ( ! $image ) {
			return NULL;
		}

		return [
			'id'  => (int) $image['id'],
			'src' => $image['src'],
			'alt' => $image['alt'] ?? '',
		];

	}

	/**
	 * Prepare gallery images
	 *
	 * @since 1.9.44
	 *
	 * @param array $images Raw gallery images data.
	 *
	 * @return array Prepared gallery images data.
	 */
	private function prepare_gallery( array $images ): array {

		// Skip first image as it's the main image
		$gallery_images = array_slice( $images, 1 );

		return array_map( [ $this, 'prepare_image' ], $gallery_images );

	}

	/**
	 * Prepare dimensions data
	 *
	 * @since 1.9.44
	 *
	 * @param array $dimensions Raw dimensions data.
	 *
	 * @return array Prepared dimensions data.
	 */
	private function prepare_dimensions( array $dimensions ): array {

		return [
			'length' => (string) $dimensions['length'],
			'width'  => (string) $dimensions['width'],
			'height' => (string) $dimensions['height'],
		];
	}

} 