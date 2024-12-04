<?php
/**
 * Generator base class
 *
 * @since        1.9.44
 * @author       BE REBEL - https://berebel.studio
 * @copyright    ©2024 BE REBEL Studio
 *
 * @package      Atum\Api\Generators
 */

namespace Atum\Api\Generators;

use Atum\Api\AtumApi;


defined( 'ABSPATH' ) || exit;

class Generator {

	/**
	 * Available generators
	 *
	 * @var array
	 */
	private static array $available_generators = [
		'attribute'       => AttributeGenerator::class,
		'category'        => CategoryGenerator::class,
		'comment'         => CommentGenerator::class,
		'coupon'          => CouponGenerator::class,
		'customer'        => CustomerGenerator::class,
		'inbound-stock'   => InboundStockGenerator::class,
		'inventory'       => InventoryGenerator::class,
		'inventory-log'   => InventoryLogGenerator::class,
		'location'        => LocationGenerator::class,
		'media'           => MediaGenerator::class,
		'order'           => OrderGenerator::class,
		'payment-method'  => PaymentMethodGenerator::class,
		'product'         => ProductGenerator::class,
		'purchase-order'  => PurchaseOrderGenerator::class,
		'refund'          => RefundGenerator::class,
		'shipping-method' => ShippingMethodGenerator::class,
		'store-settings'  => StoreSettingsGenerator::class,
		'supplier'        => SupplierGenerator::class,
		'tag'             => TagGenerator::class,
		'tax-class'       => TaxClassGenerator::class,
		'tax-rate'        => TaxRateGenerator::class,
		'variation'       => VariationGenerator::class,
	];

	/**
	 * The exported records counter. For the sqlite dump only.
	 *
	 * @var array
	 */
	private static $exported_records_counters = array(
		'attribute'       => 0,
		'category'        => 0,
		'comment'         => 0,
		'coupon'          => 0,
		'customer'        => 0,
		'inbound-stock'   => 0,
		'inventory'       => 0,
		'inventory-log'   => 0,
		'location'        => 0,
		'media'           => 0,
		'order'           => 0,
		'payment-method'  => 0,
		'product'         => 0,
		'purchase-order'  => 0,
		'refund'          => 0,
		'shipping-method' => 0,
		'store-settings'  => 0,
		'supplier'        => 0,
		'tag'             => 0,
		'tax-class'       => 0,
		'tax-rate'        => 0,
		'variation'       => 0,
	);

	/**
	 * Store ID for the table name prefix
	 *
	 * @var string
	 */
	private string $store_id;

	/**
	 * User ID for the table name prefix
	 *
	 * @var string
	 */
	private string $user_id;

	/**
	 * Revision code
	 *
	 * @var string
	 */
	private string $revision;

	/**
	 * Store settings ID
	 *
	 * @var string
	 */
	private string $store_settings_id;

	/**
	 * Store settings' app group
	 *
	 * @var array
	 */
	private array $store_settings_app_group;

	/**
	 * The schema name.
	 * Must match the key assigned to one of the available generators
	 *
	 * @var string
	 */
	private string $schema_name;

	/**
	 * Constructor
	 *
	 * @since 1.9.44
	 *
	 * @param string $schema_name The schema name.
	 * @param array  $body_data   The request body data.
	 *
	 * @throws \Exception If generator type is not supported.
	 */
	public function __construct( string $schema_name, array $body_data ) {

		if ( ! isset( self::$available_generators[ $schema_name ] ) ) {
			throw new \Exception( "Unsupported generator type: $schema_name" );
		}

		$this->schema_name              = $schema_name;
		$this->store_id                 = $body_data['storeId'] ?? '';
		$this->user_id                  = $body_data['userId'] ?? '';
		$this->revision                 = $body_data['revision'] ?? '';
		$this->store_settings_id        = $body_data['storeSettingsId'] ?? '';
		$this->store_settings_app_group = $body_data['appStoreSettings'] ?? '';

		if ( empty( $this->store_id ) || empty( $this->user_id ) || empty( $this->revision ) ) {
			throw new \Exception( 'The body data has missing info' );
		}

	}

	/**
	 * Generate SQL statements based on the generator schema and input data
	 *
	 * @since 1.9.44
	 *
	 * @param array $json_data The JSON data to transform.
	 *
	 * @return string The generated SQL statements.
	 * @throws \InvalidArgumentException If generator type is not supported.
	 */
	public function generate( array $json_data ): string {

		if ( empty( $json_data ) ) {
			return '';
		}

		$generator_class = self::$available_generators[ $this->schema_name ];
		$table_name      = $this->add_table_prefix();

		// Special case for Store Settings.
		// This table must have only one record, so we must update it instead of inserting a new one.
		if ( 'store-settings' === $this->schema_name ) {

			$generator = new StoreSettingsGenerator( $table_name, $this->revision, $this->store_settings_id, $this->store_settings_app_group );

			$exportable_endpoints = AtumApi::get_exportable_endpoints();
			$endpoint_key 	      = array_search( $json_data['endpoint'], (array) $exportable_endpoints['store-settings'] );

			if ( ! $endpoint_key ) {
				return '';
			}

			[ $main_group, $subgroup ] = explode( '.', $endpoint_key );

			return $generator->generate_sql_update( $json_data, $main_group, $subgroup );

		}

		/**
		 * @var GeneratorBase $generator
		 */
		$generator = new $generator_class( $table_name, $this->revision );

		if ( ! empty( $json_data['results'] ) ) {
			return $generator->generate_sql_inserts( $json_data['results'] );
		}

		return '';

	}

	/**
	 * Add the table prefix from the components
	 *
	 * @since 1.9.44
	 *
	 * @return string The complete table prefix
	 */
	private function add_table_prefix(): string {
		return sprintf( '%s:%s:%s.db-0', $this->store_id, $this->user_id, $this->schema_name );
	}

	/**
	 * Increase the counter for the exported records and return the current value.
	 *
	 * @since 1.9.44
	 *
	 * @param string $schema
	 *
	 * @return int|\WP_Error
	 */
	public static function get_current_counter( $schema ) {

		if ( ! isset( self::$exported_records_counters[ $schema ] ) ) {
			return new \WP_Error( 'atum_rest_no_counter', __( 'The counter for the requested schema was not found.', ATUM_TEXT_DOMAIN ) );
		}

		return ++self::$exported_records_counters[ $schema ];

	}

}
