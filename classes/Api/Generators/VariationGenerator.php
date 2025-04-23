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
		$tax_class = NULL;
		if ( isset( $variation['tax_class'] ) || isset( $variation['tax_status'] ) ) {
			$tax_class = [
				'id'   => $variation['tax_class'] ?: 'standard',
				'name' => ucfirst( $variation['tax_class'] ?: 'standard' ) . ' Rate'
			];
		}

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

		// Prepare parent data
		$parent = [
			'id'  => (int) $variation['parent_id'],
			'_id' => NULL
		];

		// Prepare image data
		$image = NULL;
		if ( isset( $variation['image'] ) && ! empty( $variation['image'] ) ) {
			$image = [
				'id'    => (int) ($variation['image']['id'] ?? 0),
				'src'   => $variation['image']['src'] ?? '',
				'title' => $variation['image']['title'] ?? '',
				'alt'   => $variation['image']['alt'] ?? ''
			];
		}

		// Prepare dates
		$dates = [
			'dateCreated'       => $variation['date_created'] ?? NULL,
			'dateCreatedGMT'    => $variation['date_created_gmt'] ?? NULL,
			'dateModified'      => $variation['date_modified'] ?? NULL,
			'dateModifiedGMT'   => $variation['date_modified_gmt'] ?? NULL,
			'dateOnSaleFrom'    => $variation['date_on_sale_from'] ?? NULL,
			'dateOnSaleFromGMT' => $variation['date_on_sale_from_gmt'] ?? NULL,
			'dateOnSaleTo'      => $variation['date_on_sale_to'] ?? NULL,
			'dateOnSaleToGMT'   => $variation['date_on_sale_to_gmt'] ?? NULL,
			'outStockDate'      => $variation['out_stock_date'] ?? NULL,
			'outStockDateGMT'   => $variation['out_stock_date_gmt'] ?? NULL,
		];

		return [
		   '_id'                    => $this->schema_name . ':' . $this->generate_uuid(),
		   '_rev'                   => $this->revision,
		   '_deleted'               => FALSE,
		   '_meta'                  => [
			   'lwt' => $this->generate_timestamp(),
		   ],
		   '_attachments'           => new \stdClass(),
		   'id'                     => (int) $variation['id'],
		   'uid'                    => $variation['global_unique_id'] ?? NULL,
		   'parent'                 => $parent,
		   'parentType'             => NULL,
		   'parentName'             => NULL,
		   'parentSku'              => $variation['parent_sku'] ?? '',
		   'parentTaxClass'         => NULL,
		   'type'                   => $variation['type'],
		   'itemType'               => 'variation',
		   'default'                => (bool) ( $variation['default'] ?? FALSE ),
		   'name'                   => $variation['name'] ?? '',
		   'slug'                   => $variation['slug'] ?? NULL,
		   'status'                 => $variation['status'] ?? 'publish',
		   'sku'                    => $variation['sku'] ?? '',
		   'barcode'                => $variation['barcode'] ?? NULL,
		   'regularPrice'           => (float) ( $variation['regular_price'] ?? 0 ),
		   'salePrice'              => isset( $variation['sale_price'] ) && $variation['sale_price'] !== '' ? (float) $variation['sale_price'] : NULL,
		   'stockQuantity'          => (int) ( $variation['stock_quantity'] ?? 0 ),
		   'manageStock'            => (bool) ( $variation['manage_stock'] ?? FALSE ),
		   'stockStatus'            => $variation['stock_status'] ?? 'instock',
		   'stock'                  => $variation['stock'] ?? NULL,
		   'backorders'             => $variation['backorders'] ?? 'no',
		   'backordersAllowed'      => (bool) ( $variation['backorders_allowed'] ?? FALSE ),
		   'virtual'                => (bool) ( $variation['virtual'] ?? FALSE ),
		   'downloadable'           => (bool) ( $variation['downloadable'] ?? FALSE ),
		   'taxStatus'              => $variation['tax_status'] ?? 'taxable',
		   'taxClass'               => $tax_class,
		   'weight'                 => (float) ( $variation['weight'] ?? 0 ),
		   'dimensions'             => $dimensions,
		   'outStockThreshold'      => $variation['out_stock_threshold'] ?? NULL,
		   'lowStockThreshold'      => $variation['low_stock_amount'] ?? '',
		   'description'            => $variation['description'] ?? '',
		   'downloads'              => $variation['downloads'] ?? [],
		   'downloadLimit'          => $variation['download_limit'] ?? NULL,
		   'downloadExpiry'         => $variation['download_expiry'] ?? NULL,
		   'attributes'             => $attributes,
		   'menuOrder'              => (int) ( $variation['menu_order'] ?? 0 ),
		   'atumControlled'         => (bool) ( $variation['atum_controlled'] ?? FALSE ),
		   'minimumThreshold'       => isset( $variation['minimum_threshold'] ) ? (float) $variation['minimum_threshold'] : NULL,
		   'availableToPurchase'    => isset( $variation['available_to_purchase'] ) ? (float) $variation['available_to_purchase'] : NULL,
		   'sellingPriority'        => isset( $variation['selling_priority'] ) ? (int) $variation['selling_priority'] : NULL,
		   'purchasePrice'          => (float) ( $variation['purchase_price'] ?? 0 ),
		   'supplier'               => NULL,
		   'supplierSku'            => $variation['supplier_sku'] ?? '',
		   'shippingClass'          => NULL,
		   'image'                  => $image,
		   'inboundStock'           => $variation['inbound_stock'] ?? NULL,
		   'stockOnHold'            => $variation['stock_on_hold'] ?? NULL,
		   'soldToday'              => $variation['sold_today'] ?? NULL,
		   'salesLastDays'          => $variation['sales_last_days'] ?? NULL,
		   'reservedStock'          => $variation['reserved_stock'] ?? NULL,
		   'customerReturns'        => $variation['customer_returns'] ?? NULL,
		   'warehouseDamage'        => $variation['warehouse_damage'] ?? NULL,
		   'lostInPost'             => $variation['lost_in_post'] ?? NULL,
		   'otherLogs'              => $variation['other_logs'] ?? NULL,
		   'outStockDays'           => $variation['out_stock_days'] ?? NULL,
		   'lostSales'              => $variation['lost_sales'] ?? NULL,
		   'calcBackOrders'         => $variation['calc_backorders'] ?? NULL,
		   'calcStockIndicator'     => NULL,
		   'calcWillLast'           => NULL,
		   'miInventories'          => $variation['mi_inventories'] ?? [],
		   'inventoryStock'         => NULL,
		   'inventoryMainStock'     => NULL,
		   'multiInventory'         => (bool) ( $variation['multi_inventory'] ?? FALSE ),
		   'inventorySortingMode'   => $variation['inventory_sorting_mode'] ?? NULL,
		   'inventoryIteration'     => $variation['inventory_iteration'] ?? NULL,
		   'expirableInventories'   => $variation['expirable_inventories'] ?? NULL,
		   'pricePerInventory'      => $variation['price_per_inventory'] ?? NULL,
		   'selectableInventories'  => $variation['selectable_inventories'] ?? NULL,
		   'inventorySelectionMode' => $variation['selectable_inventories_mode'] ?? NULL,
		   'atumLocations'          => [],
		   'hasLocation'            => FALSE,
		   'categories'             => [],
		   'linkedBoms'             => $variation['linked_bom'] ?? [],
		   'syncPurchasePrice'      => (bool) ( $variation['sync_purchase_price'] ?? FALSE ),
		   'isBom'                  => (bool) ( $variation['is_bom'] ?? FALSE ),
		   'isUsedBom'              => FALSE,
		   'bomSellable'            => $variation['bom_sellable'] ?? NULL,
		   'calculatedStock'        => $variation['calculated_stock'] ?? NULL,
		   'bomStock'               => NULL,
		   'trash'                  => FALSE,
		   'deleted'                => FALSE,
		   'metaData'               => $meta_data,
		   'conflict'               => FALSE,
	   ] + $dates;
	}
} 