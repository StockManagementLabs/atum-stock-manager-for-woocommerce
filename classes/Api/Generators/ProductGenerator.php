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

		$base_fields = $this->get_base_fields();
		
		return array_merge( $base_fields, [
			// Product specific fields.
			'id'                  => (string) $product['id'],
			'uid'                 => $product['uid'] ?? NULL,
			'itemType'            => $product['itemType'] ?? 'product',
			'name'                => $product['name'],
			'slug'                => $product['slug'],
			'permalink'           => $product['permalink'] ?? NULL,
			'type'                => $product['type'],
			'status'              => $product['status'],
			'description'         => $product['description'],
			'shortDescription'    => $product['short_description'] ?? NULL,
			'sku'                 => $product['sku'],
			'barcode'             => $product['barcode'] ?? NULL,
			'price'               => $product['price'] !== NULL ? (float) $product['price'] : NULL,
			'regularPrice'        => (float) $product['regular_price'],
			'salePrice'           => $product['sale_price'] ? (float) $product['sale_price'] : NULL,
			'purchasePrice'       => (float) ( $product['purchase_price'] ?? 0 ),
			'featured'            => (bool) $product['featured'],
			'catalogVisibility'   => $product['catalog_visibility'],
			'virtual'             => (bool) $product['virtual'],
			'downloadable'        => (bool) $product['downloadable'],
			'externalUrl'         => $product['external_url'] ?? NULL,
			'buttonText'          => $product['button_text'] ?? NULL,
			'manageStock'         => (bool) $product['manage_stock'],
			'stockQuantity'       => $product['stock_quantity'] ? (int) $product['stock_quantity'] : NULL,
			'stock'               => $product['stock'] ?? NULL,
			'stockStatus'         => $product['stock_status'],
			'backorders'          => $product['backorders'] ?? 'no',
			'lowStockThreshold'   => $product['low_stock_threshold'] ?? '',
			'lowStockAmount'      => $product['low_stock_amount'] ?? NULL,
			'outStockThreshold'   => $product['out_stock_threshold'] ?? NULL,
			'outStockDate'        => $product['out_stock_date'] ?? NULL,
			'outStockDateGMT'     => $product['out_stock_date_gmt'] ?? NULL,
			'outStockDays'        => $product['out_stock_days'] ?? NULL,
			'parent'              => $this->prepare_ids( $product['parent'] ?? NULL ),
			'parentSku'           => $product['parent_sku'] ?? '',
			'soldIndividually'    => (bool) ( $product['sold_individually'] ?? FALSE ),
			'weight'              => $product['weight'] ?? NULL,
			'menuOrder'           => (int) ( $product['menu_order'] ?? 0 ),
			'reviewsAllowed'      => (bool) ( $product['reviews_allowed'] ?? FALSE ),
			'purchaseNote'        => $product['purchase_note'] ?? '',
			
			// Date fields 
			'dateCreated'         => $product['date_created'] ?? NULL,
			'dateCreatedGMT'      => $product['date_created_gmt'] ?? NULL,
			'dateModified'        => $product['date_modified'] ?? NULL,
			'dateModifiedGMT'     => $product['date_modified_gmt'] ?? NULL,
			'dateOnSaleFrom'      => $product['date_on_sale_from'] ?? NULL,
			'dateOnSaleFromGMT'   => $product['date_on_sale_from_gmt'] ?? NULL,
			'dateOnSaleTo'        => $product['date_on_sale_to'] ?? NULL,
			'dateOnSaleToGMT'     => $product['date_on_sale_to_gmt'] ?? NULL,

			// Arrays and objects.
			'categories'          => $this->prepare_taxonomies( $product['categories'] ?? [] ),
			'tags'                => $this->prepare_taxonomies( $product['tags'] ?? [] ),
			'attributes'          => $this->prepare_attributes( $product['attributes'] ?? [] ),
			'defaultAttributes'   => $product['default_attributes'] ?? [],
			'variations'          => $product['variations'] ?? [],
			'image'               => $this->prepare_image( $product['images'][0] ?? NULL ),
			'gallery'             => $this->prepare_gallery( $product['images'] ?? [] ),
			'dimensions'          => $this->prepare_dimensions( $product['dimensions'] ?? ['length' => NULL, 'width' => NULL, 'height' => NULL] ),
			'metaData'            => $this->prepare_meta_data( $product['meta_data'] ?? [] ),
			'atumLocations'       => $this->prepare_taxonomies( $product['atum_locations'] ?? [] ),
			'downloads'           => $product['downloads'] ?? [],
			'downloadLimit'       => $product['download_limit'] ?? NULL,
			'downloadExpiry'      => $product['download_expiry'] ?? NULL,
			'shippingClass'       => $product['shipping_class'] ?? NULL,
			'taxClass'            => $product['tax_class'] ?? ['slug' => 'standard', '_id' => '', 'itemType' => 'tax-class'],
			'groupedProducts'     => $product['grouped_products'] ?? [],
			'upsells'             => $product['upsells'] ?? [],
			'crossSells'          => $product['cross_sells'] ?? [],
			'supplier'            => $product['supplier'] ?? NULL,
			'supplierSku'         => $product['supplier_sku'] ?? NULL,

			// ATUM specific fields.
			'hasLocation'         => isset($product['has_location']) ? (bool) $product['has_location'] : NULL,
			'atumControlled'      => (bool) ( $product['atum_controlled'] ?? FALSE ),
			'miInventories'       => $product['mi_inventories'] ?? [],
			'inventoryStock'      => $product['inventory_stock'] ?? NULL,
			'inventoryMainStock'  => $product['inventory_main_stock'] ?? NULL,
			'multiInventory'      => (bool) ( $product['multi_inventory'] ?? FALSE ),
			'linkedBoms'          => $product['linked_boms'] ?? [],
			'isBom'               => (bool) ( $product['is_bom'] ?? FALSE ),
			'isUsedBom'           => (bool) ( $product['is_used_bom'] ?? FALSE ),
			'calculatedStock'     => $product['calculated_stock'] ?? NULL,
			'bomStock'            => $product['bom_stock'] ?? NULL,
			'syncPurchasePrice'   => (bool) ( $product['sync_purchase_price'] ?? FALSE ),
			'calcBackOrders'      => (int) ( $product['calc_back_orders'] ?? 0 ),
			'calcStockIndicator'  => $product['calc_stock_indicator'] ?? NULL,
			'calcWillLast'        => $product['calc_will_last'] ?? NULL,
			'customerReturns'     => (int) ( $product['customer_returns'] ?? 0 ),
			'warehouseDamage'     => (int) ( $product['warehouse_damage'] ?? 0 ),
			'inboundStock'        => $product['inbound_stock'] ?? NULL,
			'lostInPost'          => (int) ( $product['lost_in_post'] ?? 0 ),
			'lostSales'           => $product['lost_sales'] ?? NULL,
			'otherLogs'           => (int) ( $product['other_logs'] ?? 0 ),
			'reservedStock'       => (int) ( $product['reserved_stock'] ?? 0 ),
			'salesLastDays'       => $product['sales_last_days'] ?? NULL,
			'soldToday'           => $product['sold_today'] ?? NULL,
			'stockOnHold'         => $product['stock_on_hold'] ?? NULL,
			
			// Required by schema
			'trash'               => FALSE,
			'conflict'            => FALSE,
			'deleted'             => FALSE,
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
				'_id'  => NULL,
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
			'id'        => (int) $image['id'],
			'src'       => $image['src'],
			'alt'       => $image['alt'] ?? '',
			'_id'       => NULL,
			'uid'       => NULL,
			'file'      => NULL,
			'name'      => $image['name'] ?? '',
			'_deleted'  => NULL,
			'_rev'      => NULL,
			'conflict'  => FALSE,
			'deleted'   => FALSE,
			'itemType'  => 'media',
			'trash'     => FALSE,
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
			'length' => $dimensions['length'] ?? NULL,
			'width'  => $dimensions['width'] ?? NULL,
			'height' => $dimensions['height'] ?? NULL,
		];
	}

} 