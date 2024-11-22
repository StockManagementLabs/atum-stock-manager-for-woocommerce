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
	 * Table name
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor
	 *
	 * @since 1.9.44
	 *
	 * @param string $store_id   Store ID part of the table name
	 * @param string $user_id    User ID part of the table name
	 * @param string $table_name Table name part
	 */
	public function __construct( string $store_id, string $user_id, string $table_name ) {
		$this->store_id   = $store_id;
		$this->user_id    = $user_id;
		$this->table_name = $table_name;
	}

	/**
	 * Generate SQL statements based on the generator schema and input data
	 *
	 * @since 1.9.44
	 *
	 * @param string $schema_name The generator schema to use (e.g., 'attribute')
	 * @param array  $json_data   The JSON data to transform
	 *
	 * @return string The generated SQL statements
	 * @throws \InvalidArgumentException If generator type is not supported
	 */
	public function generate( string $schema_name, array $json_data ): string {

		$schema_name = strtolower( $schema_name );

		if ( ! isset( self::$available_generators[ $schema_name ] ) ) {
			throw new \InvalidArgumentException( "Unsupported generator type: $schema_name" );
		}

		$generator_class = self::$available_generators[ $schema_name ];
		$table_name      = $this->add_table_prefix();

		$generator = new $generator_class( $table_name );

		// This table must have only one record, so we must update it instead of inserting a new one.
		// TODO...
		/*if ( 'store-settings' === $generator_type ) {
			return $generator->generate_sql_update( $json_data );
		}*/

		return $generator->generate_sql_inserts( $json_data );

	}

	/**
	 * Add the table prefix from the components
	 *
	 * @since 1.9.44
	 *
	 * @return string The complete table prefix
	 */
	private function add_table_prefix(): string {
		return sprintf( '%s:%s:%s.db-0', $this->store_id, $this->user_id, $this->table_name );
	}

	/**
	 * Get the schema for a given endpoint (used by the full export)
	 *
	 * @since 1.9.44
	 *
	 * @param string $endpoint_key
	 *
	 * @return string
	 */
	public static function get_schema( $endpoint_key ) {

		$schema = '';

		switch ( $endpoint_key ) {
			case 'attributes':
				$schema = array_search( AttributeGenerator::class, self::$available_generators );
				break;

			case 'atum-locations':
				$schema = array_search( LocationGenerator::class, self::$available_generators );
				break;

			case 'atum-order-notes':
			case 'comments':
				$schema = array_search( CommentGenerator::class, self::$available_generators );
				break;

			case 'categories':
				$schema = array_search( CategoryGenerator::class, self::$available_generators );
				break;

			case 'classes':
				$schema = array_search( TaxClassGenerator::class, self::$available_generators );
				break;

			case 'coupons':
				$schema = array_search( CouponGenerator::class, self::$available_generators );
				break;

			case 'customers':
				$schema = array_search( CustomerGenerator::class, self::$available_generators );
				break;

			case 'inventories':
				$schema = array_search( InventoryGenerator::class, self::$available_generators );
				break;

			case 'inventory-logs':
				$schema = array_search( InventoryLogGenerator::class, self::$available_generators );
				break;

			case 'media':
				$schema = array_search( MediaGenerator::class, self::$available_generators );
				break;

			case 'orders':
				$schema = array_search( OrderGenerator::class, self::$available_generators );
				break;

			case 'order-refunds':
				$schema = array_search( RefundGenerator::class, self::$available_generators );
				break;

			case 'payment-gateways':
		}

		return $schema;

	}

}
