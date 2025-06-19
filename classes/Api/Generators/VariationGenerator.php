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
					'id'   => NULL,
				],
				$this->prepare_ids( $attr['id'] ?? NULL ),
			];

		}, $variation['attributes'] ?? [] );

		// Prepare image data
		$image = NULL;
		if ( ! empty( $variation['image'] ) ) {
			$image = [
				'id'    => ! empty( $variation['image']['id'] ) ? (string) $variation['image']['id'] : NULL,
				'src'   => $variation['image']['src'] ?? '',
				'title' => $variation['image']['title'] ?? '',
				'alt'   => $variation['image']['alt'] ?? ''
			];
		}

		return array_merge( $this->get_base_fields(), [
			'id'                     => (string) $variation['id'],
			'uid'                    => $variation['global_unique_id'] ?? NULL,
			'parent'                 => $this->prepare_ids( $variation['parent_id'] ?? NULL ),
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
			'price'                  => ( isset( $variation['price'] ) && ! $this->is_null_value( $variation['price'] ) ) ? (float) $variation['price'] : NULL,
			'regularPrice'           => ( isset( $variation['regular_price'] ) && ! $this->is_null_value( $variation['regular_price'] ) ) ? (float) $variation['regular_price'] : NULL,
			'salePrice'              => ( isset( $variation['sale_price'] ) && ! $this->is_null_value( $variation['sale_price'] ) ) ? (float) $variation['sale_price'] : NULL,
			'stockQuantity'          => ( isset( $variation['stock_quantity'] ) && ! $this->is_null_value( $variation['stock_quantity'] ) ) ? (float) $variation['stock_quantity'] : NULL,
			'stock'                  => ( isset( $variation['stock'] ) && ! $this->is_null_value( $variation['stock'] ) ) ? (float) $variation['stock'] : NULL,
			'manageStock'            => (bool) ( $variation['manage_stock'] ?? FALSE ),
			'stockStatus'            => $variation['stock_status'] ?? 'instock',
			'backorders'             => $variation['backorders'] ?? 'no',
			'backordersAllowed'      => (bool) ( $variation['backorders_allowed'] ?? FALSE ),
			'virtual'                => (bool) ( $variation['virtual'] ?? FALSE ),
			'downloadable'           => (bool) ( $variation['downloadable'] ?? FALSE ),
			'taxStatus'              => $variation['tax_status'] ?? 'taxable',
			'taxClass'               => $this->prepare_tax_class( $variation['tax_class'] ?? NULL ),
			'weight'                 => (float) ( $variation['weight'] ?? 0 ),
			'dimensions'             => [
				'length' => (float) ( $variation['dimensions']['length'] ?? 0 ),
				'width'  => (float) ( $variation['dimensions']['width'] ?? 0 ),
				'height' => (float) ( $variation['dimensions']['height'] ?? 0 ),
			],
			'outStockThreshold'      => $variation['out_stock_threshold'] ?? NULL,
			'lowStockThreshold'      => $variation['low_stock_amount'] ?? '',
			'description'            => $variation['description'] ?? '',
			'downloads'              => $variation['downloads'] ?? [],
			'downloadLimit'          => $variation['download_limit'] ?? NULL,
			'downloadExpiry'         => $variation['download_expiry'] ?? NULL,
			'attributes'             => $attributes,
			'menuOrder'              => (int) ( $variation['menu_order'] ?? 0 ),
			'atumControlled'         => (bool) ( $variation['atum_controlled'] ?? FALSE ),
			'minimumThreshold'       => (float) ( $variation['minimum_threshold'] ?? 0 ),
			'availableToPurchase'    => (float) ( $variation['available_to_purchase'] ?? 0 ),
			'sellingPriority'        => (int) ( $variation['selling_priority'] ?? 0 ),
			'purchasePrice'          => (float) ( $variation['purchase_price'] ?? 0 ),
			'supplier'               => $variation['supplier'] ?? NULL,
			'supplierSku'            => $variation['supplier_sku'] ?? '',
			'shippingClass'          => $this->prepare_ids( $variation['shipping_class'] ?? NULL ),
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
			'calcStockIndicator'     => $variation['calc_stock_indicator'] ?? NULL,
			'calcWillLast'           => $variation['calc_will_last'] ?? NULL,
			'miInventories'          => $this->prepare_ids( $variation['mi_inventories'] ?? NULL ),
			'inventoryStock'         => ( isset( $variation['inventory_stock'] ) && ! $this->is_null_value( $variation['inventory_stock'] ) ) ? (int) $variation['inventory_stock'] : NULL,
			'inventoryMainStock'     => ( isset( $variation['inventory_main_stock'] ) && ! $this->is_null_value( $variation['inventory_main_stock'] ) ) ? (int) $variation['inventory_main_stock'] : NULL,
			'multiInventory'         => $this->string_to_bool( $variation['multi_inventory'] ?? FALSE ),
			'inventorySortingMode'   => $variation['inventory_sorting_mode'] ?? NULL,
			'inventoryIteration'     => $variation['inventory_iteration'] ?? NULL,
			'expirableInventories'   => $this->string_to_bool( $variation['expirable_inventories'] ?? FALSE ),
			'pricePerInventory'      => $this->string_to_bool( $variation['price_per_inventory'] ?? FALSE ),
			'selectableInventories'  => $this->string_to_bool( $variation['selectable_inventories'] ?? FALSE ),
			'inventorySelectionMode' => $variation['selectable_inventories_mode'] ?? NULL,
			'atumLocations'          => [],
			'hasLocation'            => FALSE,
			'categories'             => [],
			'linkedBoms'             => $this->prepare_ids( $variation['linked_boms'] ?? NULL ),
			'syncPurchasePrice'      => $this->string_to_bool( $variation['sync_purchase_price'] ?? FALSE ),
			'isBom'                  => (bool) ( $variation['is_bom'] ?? FALSE ),
			'isUsedBom'              => FALSE,
			'bomSellable'            => $this->string_to_bool( $variation['bom_sellable'] ?? FALSE ),
			'calculatedStock'        => $variation['calculated_stock'] ?? NULL,
			'bomStock'               => $variation['bom_stock'] ?? NULL,
			'metaData'               => $this->prepare_meta_data( $product['meta_data'] ?? [] ),
			'dateCreated'            => $variation['date_created'] ?? NULL,
			'dateCreatedGMT'         => $variation['date_created_gmt'] ?? NULL,
			'dateModified'           => $variation['date_modified'] ?? NULL,
			'dateModifiedGMT'        => $variation['date_modified_gmt'] ?? NULL,
			'dateOnSaleFrom'         => $variation['date_on_sale_from'] ?? NULL,
			'dateOnSaleFromGMT'      => $variation['date_on_sale_from_gmt'] ?? NULL,
			'dateOnSaleTo'           => $variation['date_on_sale_to'] ?? NULL,
			'dateOnSaleToGMT'        => $variation['date_on_sale_to_gmt'] ?? NULL,
			'outStockDate'           => $variation['out_stock_date'] ?? NULL,
			'outStockDateGMT'        => $variation['out_stock_date_gmt'] ?? NULL,
		] );

	}
} 