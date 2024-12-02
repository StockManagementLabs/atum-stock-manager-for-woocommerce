<?php
/**
 * Store Settings generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

class StoreSettingsGenerator extends GeneratorBase {

	/**
	 * The schema name
	 *
	 * @var string
	 */
	protected string $schema_name = 'store-settings';

	/**
	 * Store settings ID
	 *
	 * @var string
	 */
	private string $store_settings_id;

	/**
	 * Accumulated store settings (saved statically)
	 *
	 * @var array
	 */
	public static array $accumulated_store_settings = [
		'app'  => [],
		'atum' => [
			'general'        => [],
			'storeDetails'   => [],
			'moduleManager'  => [],
			'actionLogs'     => [],
			'multiInventory' => [],
			'productLevels'  => [],
		],
		'wc'   => [
			'admin'    => [],
			'general'  => [],
			'products' => [],
			'tax'      => [],
		],
	];

	/**
	 * StoreSettingsGenerator constructor.
	 *
	 * @since 1.9.44
	 *
	 * @param string $table_name               The table name with prefix for the SQL statements.
	 * @param string $revision                 The revision code.
	 * @param string $store_settings_id        The store settings ID in the SQLite db.
	 * @param array  $store_settings_app_group The store settings app group data.
	 */
	public function __construct( string $table_name, string $revision, string $store_settings_id, array $store_settings_app_group ) {

		if ( empty( $store_settings_id ) || empty( $store_settings_app_group ) ) {
			throw new \Exception( 'The body data has missing store settings info' );
		}

		parent::__construct( $table_name, $revision );

		$this->store_settings_id                 = $store_settings_id;
		self::$accumulated_store_settings['app'] = $store_settings_app_group;

	}

	/**
	 * Update accumulated settings
	 *
	 * @since 1.9.44
	 */
	private function update_accumulated_settings( string $main_group, string $sub_group, array $json_data ) {

		// Validate main group and subgroup.
		if (
			! isset( self::$accumulated_store_settings[ $main_group ] ) ||
			! isset( self::$accumulated_store_settings[ $main_group ][ $sub_group ] )
		) {
			throw new \InvalidArgumentException( "Invalid group or subgroup: $main_group.$sub_group" );
		}

		// Transform settings based on main group and subgroup.
		$transformed_settings = $this->transform_settings( $main_group, $sub_group, $json_data );

		// Merge transformed settings.
		self::$accumulated_store_settings[ $main_group ][ $sub_group ] = array_merge(
			self::$accumulated_store_settings[ $main_group ][ $sub_group ],
			$transformed_settings
		);
	}

	/**
	 * Generate SQL update for store settings
	 *
	 * @since 1.9.44
	 *
	 * @param array  $json_data
	 * @param string $main_group
	 * @param string $sub_group
	 *
	 * @return string
	 */
	public function generate_sql_update( array $json_data, string $main_group, string $sub_group ): string {

		// Update the accumulated settings.
		$this->update_accumulated_settings( $main_group, $sub_group, $json_data['results'] );

		// Check if all settings are populated.
		if ( $this->are_all_settings_populated() ) {

			$existing_data = array(
				'_id'  => $this->store_settings_id,
				'_rev' => $this->revision,
			);
			$prepared_data = $this->prepare_data( $existing_data );
			$this->validate_data( $prepared_data );

			// Prepare SQL update statement.
			return sprintf(
				"UPDATE store_settings SET data = '%s', lastWriteTime = '%s' WHERE id = '%s'",
				$this->sanitize_value( json_encode( $prepared_data ) ),
				$this->sanitize_value( $this->generate_timestamp() ),
				$this->sanitize_value( $this->store_settings_id )
			);

		}

		return '';

	}

	/**
	 * Validate the final data
	 *
	 * @since 1.9.44
	 *
	 * @param array $prepared_data
	 */
	private function are_all_settings_populated(): bool {

		// Check if all subgroups are populated.
		return ! empty( self::$accumulated_store_settings['atum']['general'] ) &&
			   ! empty( self::$accumulated_store_settings['atum']['storeDetails'] ) &&
			   ! empty( self::$accumulated_store_settings['atum']['moduleManager'] ) &&
			   ! empty( self::$accumulated_store_settings['atum']['multiInventory'] ) &&
			   ! empty( self::$accumulated_store_settings['atum']['productLevels'] ) &&
			   ! empty( self::$accumulated_store_settings['wc']['admin'] ) &&
			   ! empty( self::$accumulated_store_settings['wc']['general'] ) &&
			   ! empty( self::$accumulated_store_settings['wc']['products'] ) &&
			   ! empty( self::$accumulated_store_settings['wc']['tax'] );
	}

	/**
	 * Prepare the final data
	 *
	 * @since 1.9.44
	 *
	 * @param array $existing_data
	 */
	protected function prepare_data( $existing_data ): array {

		return [
			'_id'          => $existing_data['_id'],
			'_rev'         => $existing_data['_rev'],
			'_deleted'     => FALSE,
			'_meta'        => [
				'lwt' => $this->generate_timestamp(),
			],
			'_attachments' => new \stdClass(),
			'conflict'     => FALSE,
			'app'          => self::$accumulated_store_settings['app'],
			'atum'         => self::$accumulated_store_settings['atum'],
			'wc'           => self::$accumulated_store_settings['wc'],
		];

	}

	/**
	 * Map ATUM general settings
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return array
	 */
	private function map_atum_general_settings( array $json_data ): array {

		return [
			'enableAdminBarMenu'     => $this->get_boolean_setting( $json_data, 'enable_admin_bar_menu' ),
			'outStockThreshold'      => $this->get_boolean_setting( $json_data, 'out_stock_threshold' ),
			'stockQuantityDecimals'  => $this->get_int_setting( $json_data, 'stock_quantity_decimals' ),
			'salesLastNDays'         => $this->get_int_setting( $json_data, 'sales_last_ndays', 14 ),
			'enableCheckOrderPrices' => $this->get_boolean_setting( $json_data, 'enable_check_order_prices' ),
		];
	}

	/**
	 * Map ATUM store details settings
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return array
	 */
	private function map_atum_store_details_settings( array $json_data ): array {

		return [
			'company' => [
				'name'      => $this->get_string_setting( $json_data, 'company_name' ),
				'taxNumber' => $this->get_string_setting( $json_data, 'tax_number' ),
				'siteIcon'  => $this->get_int_setting( $json_data, 'site_icon' ),
			],
			'address' => [
				'addressLine1' => $this->get_string_setting( $json_data, 'address_1' ),
				'addressLine2' => $this->get_string_setting( $json_data, 'address_2' ),
				'city'         => $this->get_string_setting( $json_data, 'city' ),
				'state'        => $this->get_string_setting( $json_data, 'state' ),
				'postcode'     => $this->get_string_setting( $json_data, 'ship_zip' ),
				'country'      => $this->get_string_setting( $json_data, 'ship_country' ),
			],
			'contact' => [
				'phone' => $this->get_string_setting( $json_data, 'phone' ),
				'email' => $this->get_string_setting( $json_data, 'email' ),
			],
		];
	}

	/**
	 * Map ATUM module manager settings
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return array
	 */
	private function map_atum_module_manager_settings( array $json_data ): array {

		return [
			'dashboardModule'      => $this->get_boolean_setting( $json_data, 'dashboard_module', TRUE ),
			'stockCentralModule'   => $this->get_boolean_setting( $json_data, 'stock_central_module', TRUE ),
			'inventoryLogsModule'  => $this->get_boolean_setting( $json_data, 'inventory_logs_module', TRUE ),
			'visualSettingsModule' => $this->get_boolean_setting( $json_data, 'visual_settings_module', TRUE ),
			'apiModule'            => $this->get_boolean_setting( $json_data, 'api_module', TRUE ),
			'barcodesModule'       => $this->get_boolean_setting( $json_data, 'barcodes_module', TRUE ),
		];
	}

	/**
	 * Map ATUM Multi Inventory settings
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return array
	 */
	private function map_atum_multi_inventory_settings( array $json_data ): array {

		return [
			'defaultMultiInventory' => $this->get_boolean_setting( $json_data, 'mi_default_multi_inventory' ),
			'regionRestrictionMode' => $this->get_string_setting( $json_data, 'mi_region_restriction_mode', 'no-restriction' ),
			'detailsColumnExpanded' => $this->get_boolean_setting( $json_data, 'mi_details_column_expanded' ),
			'totalFilteredProduct'  => $this->get_boolean_setting( $json_data, 'mi_total_filtered_product' ),
			'geopromptPrivacyText'  => $this->get_string_setting( $json_data, 'mi_geoprompt_privacy_text', 'I accept the [link]privacy policy[/link]' ),
		];
	}

	/**
	 * Map ATUM Product Levels settings
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return array
	 */
	private function map_atum_product_levels_settings( array $json_data ): array {

		return [
			'bomStockControl'           => $this->get_boolean_setting( $json_data, 'pl_bom_stock_control' ),
			'defaultBomSelling'         => $this->get_boolean_setting( $json_data, 'pl_default_bom_selling' ),
			'displayBomSellableInSc'    => $this->get_boolean_setting( $json_data, 'pl_display_bom_sellable_in_sc', TRUE ),
			'bomItemRealCost'           => $this->get_boolean_setting( $json_data, 'pl_bom_item_real_cost' ),
			'bomItemCostDecimals'       => $this->get_int_setting( $json_data, 'pl_bom_item_cost_decimals', 2 ),
			'manufacturingPostsPerPage' => $this->get_int_setting( $json_data, 'pl_manufacturing_posts_per_page', 20 ),
			'manufacturingSaleDays'     => $this->get_int_setting( $json_data, 'pl_manufacturing_sale_days', 14 ),
		];
	}

	/**
	 * Map WC Admin settings
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return array
	 */
	private function map_wc_admin_settings( array $json_data ): array {

		return [
			'excludedReportOrderStatuses' => $this->get_array_setting( $json_data, 'woocommerce_excluded_report_order_statuses', [
				'pending',
				'cancelled',
				'failed',
			] ),
			'actionableOrderStatuses'     => $this->get_array_setting( $json_data, 'woocommerce_actionable_order_statuses', [
				'processing',
				'on-hold',
			] ),
			'defaultDateRange'            => $this->get_string_setting( $json_data, 'woocommerce_default_date_range', 'period=month&compare=previous_year' ),
			'dateType'                    => $this->get_string_setting( $json_data, 'woocommerce_date_type' ),
		];
	}

	/**
	 * Map WC General settings
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return array
	 */
	private function map_wc_general_settings( array $json_data ): array {

		return [
			'address' => [
				'line1' => $this->get_string_setting( $json_data, 'woocommerce_store_address' ),
				'line2' => $this->get_string_setting( $json_data, 'woocommerce_store_address_2' ),
				'city'  => $this->get_string_setting( $json_data, 'woocommerce_store_city' ),
			],
			'pricing' => [
				'thousandSeparator' => $this->get_string_setting( $json_data, 'woocommerce_price_thousand_sep', ',' ),
				'decimalSeparator'  => $this->get_string_setting( $json_data, 'woocommerce_price_decimal_sep', '.' ),
				'numberOfDecimals'  => $this->get_int_setting( $json_data, 'woocommerce_price_num_decimals', 2 ),
			],
		];
	}

	/**
	 * Map WC Product settings
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return array
	 */
	private function map_wc_product_settings( array $json_data ): array {

		return [
			'shopPage'                => $this->get_int_setting( $json_data, 'woocommerce_shop_page_id' ),
			'cartRedirectAfterAdd'    => $this->get_boolean_setting( $json_data, 'woocommerce_cart_redirect_after_add' ),
			'enableAjaxAddToCart'     => $this->get_boolean_setting( $json_data, 'woocommerce_enable_ajax_add_to_cart', TRUE ),
			'matchFeaturedImageBySku' => $this->get_boolean_setting( $json_data, 'woocommerce_product_match_featured_image_by_sku' ),
			'attributeLookup'         => [
				'directUpdates'    => $this->get_boolean_setting( $json_data, 'woocommerce_attribute_lookup_direct_updates' ),
				'optimizedUpdates' => $this->get_boolean_setting( $json_data, 'woocommerce_attribute_lookup_optimized_updates' ),
			],
		];
	}

	/**
	 * Map WC Tax settings
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return array
	 */
	private function map_wc_tax_settings( array $json_data ): array {

		return [
			'pricesIncludeTax'    => $this->get_boolean_setting( $json_data, 'woocommerce_prices_include_tax' ),
			'taxBasedOn'          => $this->get_string_setting( $json_data, 'woocommerce_tax_based_on', 'shipping' ),
			'shippingTaxClass'    => [
				'id'   => $this->get_string_setting( $json_data, 'woocommerce_shipping_tax_class', 'inherit' ),
				'name' => $this->get_shipping_tax_class_name( $json_data ),
			],
			'displayPricesInCart' => $this->get_string_setting( $json_data, 'woocommerce_tax_display_cart', 'excl' ),
			'priceDisplaySuffix'  => $this->get_string_setting( $json_data, 'woocommerce_price_display_suffix' ),
			'taxTotalDisplay'     => $this->get_string_setting( $json_data, 'woocommerce_tax_total_display', 'itemized' ),
		];
	}

	/**
	 * Get the shipping tax class name
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data
	 *
	 * @return string
	 */
	private function get_shipping_tax_class_name( array $json_data ): string {

		$shipping_tax_class = $this->get_string_setting( $json_data, 'woocommerce_shipping_tax_class', 'inherit' );

		$tax_class_names = [
			'inherit'      => 'Inherit from cart items',
			''             => 'Standard',
			'reduced-rate' => 'Reduced rate',
			'zero-rate'    => 'Zero rate',
		];

		return $tax_class_names[ $shipping_tax_class ] ?? 'Standard';
	}

	/**
	 * Get the array setting
	 *
	 * @since 1.9.44
	 *
	 * @param array  $json_data
	 * @param string $id
	 * @param array  $default
	 *
	 * @return array
	 */
	private function get_array_setting( array $json_data, string $id, array $default = [] ): array {

		$setting = $this->find_setting_by_id( $json_data, $id );

		return $setting ? (array) $setting['value'] : $default;
	}

	/**
	 * Get the boolean setting
	 *
	 * @since 1.9.44
	 *
	 * @param array  $json_data
	 * @param string $id
	 * @param bool   $default
	 *
	 * @return bool
	 */
	private function get_boolean_setting( array $json_data, string $id, bool $default = FALSE ): bool {

		$setting = $this->find_setting_by_id( $json_data, $id );

		return $setting ? ( $setting['value'] === 'yes' ) : $default;
	}

	/**
	 * Get the string setting
	 *
	 * @since 1.9.44
	 *
	 * @param array  $json_data
	 * @param string $id
	 * @param string $default
	 *
	 * @return string
	 */
	private function get_string_setting( array $json_data, string $id, string $default = '' ): string {

		$setting = $this->find_setting_by_id( $json_data, $id );

		return $setting ? (string) $setting['value'] : $default;
	}

	/**
	 * Get the int setting
	 *
	 * @since 1.9.44
	 *
	 * @param array  $json_data
	 * @param string $id
	 * @param int    $default
	 *
	 * @return int
	 */
	private function get_int_setting( array $json_data, string $id, int $default = 0 ): int {

		$setting = $this->find_setting_by_id( $json_data, $id );

		return $setting ? (int) $setting['value'] : $default;
	}

	/**
	 * Find a setting by ID
	 *
	 * @since 1.9.44
	 *
	 * @param array  $json_data
	 * @param string $id
	 *
	 * @return array|null
	 */
	private function find_setting_by_id( array $json_data, string $id ): ?array {

		foreach ( $json_data as $setting ) {
			if ( $setting['id'] === $id ) {
				return $setting;
			}
		}

		return NULL;
	}

	/**
	 * Transform settings based on main group and sub group
	 *
	 * @since 1.9.44
	 *
	 * @param string $main_group
	 * @param string $sub_group
	 * @param array  $json_data
	 *
	 * @return array
	 */
	private function transform_settings( string $main_group, string $sub_group, array $json_data ): array {

		$transformations = [
			// ATUM transformations
			'atum' => [
				'general'        => fn( $data ) => $this->map_atum_general_settings( $data ),
				'storeDetails'   => fn( $data ) => $this->map_atum_store_details_settings( $data ),
				'moduleManager'  => fn( $data ) => $this->map_atum_module_manager_settings( $data ),
				'multiInventory' => fn( $data ) => $this->map_atum_multi_inventory_settings( $data ),
				'productLevels'  => fn( $data ) => $this->map_atum_product_levels_settings( $data ),
			],
			// WC transformations
			'wc'   => [
				'admin'    => fn( $data ) => $this->map_wc_admin_settings( $data ),
				'general'  => fn( $data ) => $this->map_wc_general_settings( $data ),
				'products' => fn( $data ) => $this->map_wc_product_settings( $data ),
				'tax'      => fn( $data ) => $this->map_wc_tax_settings( $data ),
			],
		];

		// Validate transformation exists
		if ( ! isset( $transformations[ $main_group ][ $sub_group ] ) ) {
			throw new \InvalidArgumentException( "No transformation found for $main_group.$sub_group" );
		}

		return $transformations[ $main_group ][ $sub_group ]( $json_data );
	}

}
