<?php
/**
 * Store Settings generator for SQLite
 *
 * @since       1.9.44
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 BE REBEL Studio
 *
 * @package     Atum\Api\Generators
 * TODO: REVIEW EVERYTHING
 */

namespace Atum\Api\Generators;

defined( 'ABSPATH' ) || exit;

use Atum\Addons\Addons;
use Atum\Components\AtumCache;

class StoreSettingsGenerator extends GeneratorBase {

	/**
	 * The transient key for the accumulated store settings.
	 */
	const SETTINGS_TRANSIENT = 'atum_store_settings';

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
			//'stockTakes'     => [],
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

		// As we need to persist the accumulated settings between different instances of the generator, we need to save then in a transient.
		$settings_transient_key = AtumCache::get_transient_key( self::SETTINGS_TRANSIENT, [ $store_settings_id ] );
		$accumulated_settings   = AtumCache::get_transient( $settings_transient_key, TRUE );

		if ( ! empty( $accumulated_settings ) && is_array( $accumulated_settings ) ) {
			self::$accumulated_store_settings = $accumulated_settings;
		}

		$this->store_settings_id = $store_settings_id;

		if ( empty( self::$accumulated_store_settings['app'] ) ) {
			self::$accumulated_store_settings['app'] = $store_settings_app_group;
		}

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
	 * @param array      $json_data
	 * @param string     $main_group
	 * @param string     $sub_group
	 * @param int[]|null $page
	 *
	 * @return string
	 */
	public function generate_sql_update( array $json_data, string $main_group, string $sub_group, $page = NULL ): string {

		// Update the accumulated settings.
		$this->update_accumulated_settings( $main_group, $sub_group, $json_data['results'] );

		$settings_transient_key = AtumCache::get_transient_key( self::SETTINGS_TRANSIENT, [ $this->store_settings_id ] );

		// Check if all settings are populated.
		if ( $this->are_all_settings_populated() ) {

			$prepared_data = $this->prepare_data( [ '_id'  => $this->store_settings_id, '_rev' => $this->revision, ] );
			$this->validate_data( $prepared_data );

			// Delete the transient (not needed anymore).
			AtumCache::delete_transients( $settings_transient_key );

			// Prepare SQL update statement.
			return $this->add_starting_comment() . sprintf(
				"UPDATE '$this->table_name' SET data = '%s', lastWriteTime = '%s' WHERE id = '%s';",
				$this->sanitize_value( json_encode( $prepared_data ) ),
				$this->sanitize_value( $this->generate_timestamp() ),
				$this->sanitize_value( $this->store_settings_id )
			) . "\n" . $this->add_ending_comment();

		}
		else {
			AtumCache::set_transient( $settings_transient_key, self::$accumulated_store_settings );
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

		/* Multi-Inventory support */
		if ( Addons::is_addon_active( 'multi_inventory' ) && empty( self::$accumulated_store_settings['atum']['multiInventory'] ) ) {
			return FALSE;
		}

		/* Product Levels support */
		if ( Addons::is_addon_active( 'product_levels' ) && empty( self::$accumulated_store_settings['atum']['productLevels'] ) ) {
			return FALSE;
		}

		/* Stock Takes support */
		// TODO: DISABLED UNTIL WE ADD FULL SUPPORT TO STOCK TAKES TO THE APP.
		/*if ( Addons::is_addon_active( 'stock_takes' ) && empty( self::$accumulated_store_settings['atum']['stockTakes'] ) ) {
			return FALSE;
		}*/

		// Check if all subgroups are populated.
		return ! empty( self::$accumulated_store_settings['atum']['general'] ) &&
			   ! empty( self::$accumulated_store_settings['atum']['storeDetails'] ) &&
			   ! empty( self::$accumulated_store_settings['atum']['moduleManager'] ) &&
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

		return array_merge( $this->get_base_fields(), [
			'_id'          => $existing_data['_id'], // Overwrite the _id.
			'_rev'         => $existing_data['_rev'], // Overwrite the _rev.
			'conflict'     => FALSE,
			'app'          => self::$accumulated_store_settings['app'],
			'atum'         => self::$accumulated_store_settings['atum'],
			'wc'           => self::$accumulated_store_settings['wc'],
		] );

	}

	/**
	 * Validate and sanitize enum setting
	 *
	 * @param mixed  $value
	 * @param array  $allowed_values
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	private function validate_enum_setting($value, array $allowed_values, $default) {
		return in_array($value, $allowed_values, true) ? $value : $default;
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
			'showTotals'             => $this->get_boolean_setting( $json_data, 'show_totals', false ),
			'grossProfit'            => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'gross_profit_type' ), 
				['percentage', 'monetary'], 
				'percentage'
			),
			'profitMargin'           => $this->get_int_setting( $json_data, 'profit_margin', 0 ),
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
			'siteIcon' => $this->prepare_ids( $this->get_int_setting( $json_data, 'site_icon_id' ) ),
			'company'  => [
				'name'            => $this->get_string_setting( $json_data, 'company_name' ),
				'taxNumber'       => $this->get_string_setting( $json_data, 'tax_number' ),
				'address1'        => $this->get_string_setting( $json_data, 'company_address_1' ),
				'address2'        => $this->get_string_setting( $json_data, 'company_address_2' ),
				'city'            => $this->get_string_setting( $json_data, 'company_city' ),
				'country'         => $this->get_string_setting( $json_data, 'company_country' ),
				'state'           => $this->get_string_setting( $json_data, 'company_state' ),
				'zip'             => $this->get_string_setting( $json_data, 'company_zip' ),
				'sameShipAddress' => $this->get_boolean_setting( $json_data, 'same_ship_address', TRUE ),
			],
			'shipping' => [
				'name'     => $this->get_string_setting( $json_data, 'shipping_name' ),
				'address1' => $this->get_string_setting( $json_data, 'shipping_address_1' ),
				'address2' => $this->get_string_setting( $json_data, 'shipping_address_2' ),
				'city'     => $this->get_string_setting( $json_data, 'shipping_city' ),
				'country'  => $this->get_string_setting( $json_data, 'shipping_country' ),
				'state'    => $this->get_string_setting( $json_data, 'shipping_state' ),
				'zip'      => $this->get_string_setting( $json_data, 'shipping_zip' ),
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
			'regionRestrictionMode' => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'mi_region_restriction_mode' ), 
				['none', 'shipping_zone', 'country'], 
				'none'
			),
			'detailsColumnExpanded' => $this->get_boolean_setting( $json_data, 'mi_details_column_expanded' ),
			'totalFilteredProduct'  => $this->get_boolean_setting( $json_data, 'mi_total_filtered_product' ),
			'geopromptPrivacyText'  => $this->get_string_setting( $json_data, 'mi_geoprompt_privacy_text', 'I accept the [link]privacy policy[/link]' ),
			'defaultShippingZone' => $this->get_string_setting( $json_data, 'mi_default_shipping_zone' ),
			'defaultZoneForEmptyRegions' => $this->get_boolean_setting( $json_data, 'mi_default_zone_for_empty_regions', false ),
			'defaultCountry' => $this->get_string_setting( $json_data, 'mi_default_country' ),
			'defaultCountryForEmptyRegions' => $this->get_boolean_setting( $json_data, 'mi_default_country_for_empty_regions', false ),
			'expiryDatesInCart'     => $this->get_boolean_setting( $json_data, 'mi_expiry_dates_in_cart', false ),
			'listTablesFilter'      => $this->get_boolean_setting( $json_data, 'mi_list_tables_filter', true ),
			'batchTracking'         => $this->get_boolean_setting( $json_data, 'mi_batch_tracking', false ),
			'inventorySortingMode'  => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'mi_inventory_sorting_mode' ),
				['fifo', 'lifo', 'bbe', 'manual'], 
				'fifo'
			),
			'inventoryIteration'    => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'mi_inventory_iteration' ),
				['global', 'shipping_zone', 'country'], 
				'global'
			),
			'expirableInventories'  => $this->get_boolean_setting( $json_data, 'mi_default_expirable_inventories', false ),
			'pricePerInventory'     => $this->get_boolean_setting( $json_data, 'mi_default_price_per_inventory', false ),
			'selectableInventories' => $this->get_boolean_setting( $json_data, 'mi_default_selectable_inventories', false ),
			'inventorySelectionMode'=> $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'mi_default_selectable_inventories_mode' ),
				['auto', 'manual'], 
				'auto'
			),
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
			'currency'                  => $this->get_string_setting( $json_data, 'woocommerce_currency' ),
			'currencySymbol'            => $this->get_string_setting( $json_data, 'woocommerce_currency_symbol' ),
			'currencyPosition'          => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'woocommerce_currency_pos' ), 
				['left', 'right', 'left_space', 'right_space'], 
				'left'
			),
			'numberOfDecimals'          => $this->get_int_setting( $json_data, 'woocommerce_price_num_decimals', 2 ),
			'allowedCountries'          => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'woocommerce_allowed_countries' ), 
				['all', 'specific', 'all_except'], 
				'all'
			),
			'allExceptCountries'        => $this->get_array_setting( $json_data, 'woocommerce_all_except_countries', [] ),
			'specificAllowedCountries'  => $this->get_array_setting( $json_data, 'woocommerce_specific_allowed_countries', [] ),
			'shipToCountries'           => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'woocommerce_ship_to_countries' ), 
				['all', 'specific', 'disabled'], 
				'all'
			),
			'specificShipToCountries'   => $this->get_array_setting( $json_data, 'woocommerce_specific_ship_to_countries', [] ),
			'defaultCustomerAddress'    => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'woocommerce_default_customer_address' ), 
				['base', 'geolocation', 'geolocation_ajax'], 
				'base'
			),
			'calcTaxes'                 => $this->get_boolean_setting( $json_data, 'woocommerce_calc_taxes', false ),
			'enableCoupons'             => $this->get_boolean_setting( $json_data, 'woocommerce_enable_coupons', true ),
			'calcDiscountsSequentially' => $this->get_boolean_setting( $json_data, 'woocommerce_calc_discounts_sequentially', false ),
			'priceThousandSep'          => $this->get_string_setting( $json_data, 'woocommerce_price_thousand_sep', ',' ),
			'priceDecimalSep'           => $this->get_string_setting( $json_data, 'woocommerce_price_decimal_sep', '.' ),
			'priceNumDecimals'          => $this->get_int_setting( $json_data, 'woocommerce_price_num_decimals', 2 ),
			'store'                     => [
				'address'  => $this->get_string_setting( $json_data, 'woocommerce_store_address' ),
				'city'     => $this->get_string_setting( $json_data, 'woocommerce_store_city' ),
				'postcode' => $this->get_string_setting( $json_data, 'woocommerce_store_postcode' ),
				'country'  => $this->get_string_setting( $json_data, 'woocommerce_store_country' ),
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
			'placeholderImage' => [
				'id'  => $this->get_int_setting( $json_data, 'woocommerce_placeholder_image_id' ),
				'src' => $this->get_string_setting( $json_data, 'woocommerce_placeholder_image_src' ),
			],
			'weightUnit'           => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'woocommerce_weight_unit' ), 
				['kg', 'g', 'lbs', 'oz'], 
				'kg'
			),
			'dimensionUnit'        => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'woocommerce_dimension_unit' ), 
				['m', 'cm', 'mm', 'in', 'yd'], 
				'cm'
			),
			'manageStock'          => $this->get_boolean_setting( $json_data, 'woocommerce_manage_stock', true ),
			'holdStockMinutes'     => $this->get_int_setting( $json_data, 'woocommerce_hold_stock_minutes', 60 ),
			'notifyLowStock'       => $this->get_boolean_setting( $json_data, 'woocommerce_notify_low_stock', true ),
			'notifyNoStock'        => $this->get_boolean_setting( $json_data, 'woocommerce_notify_no_stock', true ),
			'notifyLowStockAmount' => $this->get_int_setting( $json_data, 'woocommerce_notify_low_stock_amount', 2 ),
			'notifyNoStockAmount'  => $this->get_int_setting( $json_data, 'woocommerce_notify_no_stock_amount', 0 ),
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
			'taxBasedOn'          => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'woocommerce_tax_based_on' ), 
				['shipping', 'billing', 'base'], 
				'shipping'
			),
			'shippingTaxClass'    => [
				'id'   => $this->get_string_setting( $json_data, 'woocommerce_shipping_tax_class', 'inherit' ),
				'name' => $this->get_shipping_tax_class_name( $json_data ),
			],
			'displayPricesInCart' => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'woocommerce_tax_display_cart' ), 
				['excl', 'incl'], 
				'excl'
			),
			'priceDisplaySuffix'  => $this->get_string_setting( $json_data, 'woocommerce_price_display_suffix' ),
			'taxTotalDisplay'     => $this->validate_enum_setting(
				$this->get_string_setting( $json_data, 'woocommerce_tax_total_display' ), 
				['itemized', 'single'], 
				'itemized'
			),
			'taxRoundAtSubtotal' => $this->get_boolean_setting( $json_data, 'woocommerce_tax_round_at_subtotal', false ),
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
