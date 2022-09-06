<?php
/**
 * Upgrade tasks class
 *
 * @package         Atum
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumCalculatedProps;
use Atum\Components\AtumQueues;
use Atum\InboundStock\InboundStock;
use Atum\InventoryLogs\Models\Log;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\StockCentral\StockCentral;
use Atum\StockCentral\Lists\ListTable;
use Atum\Suppliers\Supplier;
use Atum\Suppliers\Suppliers;


class Upgrade {

	/**
	 * The current ATUM version
	 *
	 * @var string
	 */
	private $current_atum_version = '';

	/**
	 * Whether ATUM is being installed for the first time
	 *
	 * @var bool
	 */
	private $is_fresh_install = FALSE;

	/**
	 * Upgrade constructor
	 *
	 * @since 1.2.4
	 *
	 * @param string $db_version  The ATUM version saved in db as an option.
	 */
	public function __construct( $db_version ) {

		$this->current_atum_version = $db_version;

		if ( ! $db_version || version_compare( $db_version, '0.0.1', '<=' ) ) {
			$this->is_fresh_install = TRUE;
		}

		// Update the db version to the current ATUM version before upgrade to prevent various executions.
		update_option( 'atum_version', ATUM_VERSION );

		// Delete transients if there after every version change.
		AtumCache::delete_transients();

		/************************
		 * UPGRADE ACTIONS START
		 **********************!*/

		// ** version 1.2.4 ** The Inventory Logs was introduced.
		if ( version_compare( $db_version, '1.2.4', '<' ) ) {
			$this->create_atum_order_items_tables();
			add_action( 'admin_init', array( $this, 'create_inventory_log_types' ) );
		}

		// ** version 1.4.6 ** New hidden column: weight.
		if ( version_compare( $db_version, '1.4.6', '<' ) && ! $this->is_fresh_install ) {
			$this->add_default_hidden_columns();
		}

		// ** version 1.5.0 ** New tables to store ATUM data for products.
		if ( version_compare( $db_version, '1.5.0', '<' ) ) {
			$this->create_product_data_table();

			if ( ! $this->is_fresh_install ) {
				$this->update_po_status();
			}
		}

		// ** version 1.5.8 ** New tables to store ATUM data for products.
		if ( version_compare( $db_version, '1.5.8', '<' ) ) {
			$this->create_list_table_columns();
		}

		// ** version 1.6.1.1 ** Change field types in ATUM data for products.
		if ( version_compare( $db_version, '1.6.1.1', '<' ) ) {
			$this->alter_list_table_columns();
		}

		// ** version 1.6.3.2 ** Set the default for atum_controlled and disallow NULL.
		if ( version_compare( $db_version, '1.6.3.2', '<' ) && ! $this->is_fresh_install ) {
			$this->alter_atum_controlled_column();
		}

		// ** version 1.6.6 ** Add stock status and low stock calculated fields to ATUM data.
		if ( version_compare( $db_version, '1.6.6', '<' ) ) {
			$this->add_atum_stock_fields();
			add_action( 'atum/after_init', array( $this, 'fill_new_fields_values' ) );
		}

		// ** version 1.6.8 ** Change the supplier's meta key names.
		if ( version_compare( $db_version, '1.6.8', '<' ) && ! $this->is_fresh_install ) {
			$this->change_supplier_meta_key_names();
		}

		// ** version 1.7.1 ** Change the POs date_expected's meta key names.
		if ( version_compare( $db_version, '1.7.1', '<' ) && ! $this->is_fresh_install ) {
			$this->change_date_expected_meta_key_names();
		}

		// ** version 1.7.2 ** Update the calculated props for variable products.
		if ( version_compare( $db_version, '1.7.2', '<' ) && ! $this->is_fresh_install ) {
			// Run the method asynchronously.
			AtumQueues::add_async_action( 'update_atum_product_calc_props', array( get_class(), 'update_variable_calc_props' ) );
		}

		// ** version 1.7.3 ** Delete the comments count transient, so the unapproved and spam comments are counted.
		if ( version_compare( $db_version, '1.7.3', '<' ) ) {
			delete_transient( ATUM_PREFIX . 'count_comments' );
		}

		// ** version 1.7.8 ** Add the is_bom column
		if ( version_compare( $db_version, '1.7.8', '<' ) ) {
			$this->create_is_bom_column();
		}

		// ** version 1.8.9.2 ** Update in variation products the atum_stock_status column
		if ( version_compare( $db_version, '1.8.9.2', '<' ) ) {
			$this->update_atum_stock_status();
		}

		// ** version 1.9.6.1 ** Create sales_update_date in ATUM product data table
		if ( version_compare( $db_version, '1.9.6.1', '<' ) ) {
			$this->create_sales_update_date();
		}

		// ** version 1.9.7 ** Changes to the ATUM ListTables "entries per page" meta keys
		if ( version_compare( $db_version, '1.9.7', '<' ) ) {
			$this->change_entries_per_page_meta_keys();
		}

		// ** version 1.9.15 ** Alter the low_stock column on product data table
		if ( version_compare( $db_version, '1.9.15', '<' ) ) {
			$this->alter_low_stock_column();
		}

		// Control all the products by default when installing ATUM for the first time.
		if ( $this->is_fresh_install ) {
			$this->maybe_control_all_products();
		}

		// ** version 1.9.18 ** Add the new barcodes field to the product data table
		if ( version_compare( $db_version, '1.9.18', '<' ) ) {
			$this->add_barcode_column();
		}

		// ** version 1.9.19 ** Regenerate WC product lookup tables to ensure our queries work correctly.
		if ( version_compare( $db_version, '1.9.19', '<' ) ) {
			$this->regenerate_lookup_tables();
		}

		// ** version 1.9.19.1 ** Set the use default checkbox values to the right suppliers.
		if ( version_compare( $db_version, '1.9.19.1', '<' ) ) {
			$this->set_supplier_default_checkboxes();
		}

		// ** version 1.9.20.3 ** Add the committed to WC Orders column to APD.
		if ( version_compare( $db_version, '1.9.20.3', '<' ) ) {
			$this->add_committed_stock_to_wc_orders_column();
		}

		// ** version 1.9.20.4 ** Add the calc_backorders column to APD.
		if ( version_compare( $db_version, '1.9.20.4', '<' ) ) {
			$this->add_calc_backorders_column();
		}

		// ** version 1.9.20.5 ** Update the calc_backorders column data.
		if ( version_compare( $db_version, '1.9.20.4', '<' ) ) {
			$this->update_calc_backorders();
		}

		/**********************
		 * UPGRADE ACTIONS END
		 ********************!*/
		$this->remove_duplicated_scheduled_actions();
		do_action( 'atum/after_upgrade', $db_version );

	}

	/**
	 * Create the tables for the ATUM Order items
	 *
	 * @since 1.2.4
	 */
	private function create_atum_order_items_tables() {

		global $wpdb;

		// Create DB tables for the ATUM Order items.
		$items_table    = $wpdb->prefix . 'atum_order_items';
		$itemmeta_table = $wpdb->prefix . 'atum_order_itemmeta';

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$items_table';" ) ) { // phpcs:ignore WordPress.DB.PreparedSQL

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$sql = "
				CREATE TABLE $items_table (
				    `order_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`order_item_name` text NOT NULL,
					`order_item_type` varchar(200) NOT NULL DEFAULT '',
					`order_id` bigint(20) unsigned NOT NULL,
					PRIMARY KEY (`order_item_id`),
					KEY `order_id` (`order_id`)
				) $collate;
				CREATE TABLE $itemmeta_table (
					`meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	                `order_item_id` bigint(20) unsigned NOT NULL,
					`meta_key` varchar(255) DEFAULT NULL,
					`meta_value` longtext,
					PRIMARY KEY (`meta_id`),
					KEY `meta_key` (`meta_key`(191)),
					KEY `order_item_id` (`order_item_id`)
				) $collate;
			";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

		}

	}

	/**
	 * Create the default types for the Inventory Logs
	 *
	 * @since 1.2.4
	 */
	public function create_inventory_log_types() {

		// Create terms for the log types.
		$log_type_taxonomy = InventoryLogs::get_type_taxonomy();
		foreach ( Log::get_log_types() as $log_type_slug => $log_type_name ) {

			if ( empty( term_exists( $log_type_slug, $log_type_taxonomy ) ) ) {
				wp_insert_term( $log_type_name, $log_type_taxonomy, array( 'slug' => $log_type_slug ) );
			}

		}

	}

	/**
	 * Add default_hidden_columns to hidden columns on SC (in all users with hidden columns set)
	 *
	 * @since 1.4.6
	 */
	private function add_default_hidden_columns() {

		$hidden_columns = ListTable::hidden_columns();

		if ( empty( $hidden_columns ) ) {
			return;
		}

		global $wpdb;

		$meta_key_sc = 'manage' . Globals::ATUM_UI_HOOK . '_page_' . StockCentral::UI_SLUG . 'columnshidden';

		foreach ( $hidden_columns as $hidden_column ) {

			$user_ids = $wpdb->get_col( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '$meta_key_sc' AND meta_value NOT LIKE '%{$hidden_column}%' AND meta_value <> ''" ); // phpcs:ignore WordPress.DB.PreparedSQL

			foreach ( $user_ids as $user_id ) {

				$meta = get_user_meta( $user_id, $meta_key_sc, TRUE );

				if ( ! array( $meta ) ) {
					$meta = array();
				}

				$meta[] = $hidden_column;
				update_user_meta( $user_id, $meta_key_sc, $meta );

			}
		}

	}

	/**
	 * Create the table for the product data related to ATUM
	 *
	 * @since 1.5.0
	 */
	private function create_product_data_table() {

		global $wpdb;

		$product_meta_table = $wpdb->prefix . 'atum_product_data';

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$product_meta_table';" ) ) { // phpcs:ignore WordPress.DB.PreparedSQL

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$sql = "
				CREATE TABLE $product_meta_table (
				`product_id` BIGINT(20) NOT NULL,
		  		`purchase_price` DOUBLE NULL DEFAULT NULL,
			  	`supplier_id` BIGINT(20) NULL DEFAULT NULL,
			  	`supplier_sku` VARCHAR(100) NULL DEFAULT '',
			  	`atum_controlled` TINYINT(1) NULL DEFAULT 1,
			  	`out_stock_date` DATETIME NULL DEFAULT NULL,
			  	`out_stock_threshold` DOUBLE NULL DEFAULT NULL,
			  	`inheritable` TINYINT(1) NULL DEFAULT 0,	
			  	PRIMARY KEY  (`product_id`),
				KEY `supplier_id` (`supplier_id`),
				KEY `atum_controlled` (`atum_controlled`)
			) $collate;
			";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// We only need to migrate meta keys if is not a fresh install.
			if ( ! $this->is_fresh_install ) {
				$this->migrate_atum_products_data();
			}

		}

	}

	/**
	 * Migrate from the old metas to the new data tables
	 *
	 * @since 1.5.0
	 */
	private function migrate_atum_products_data() {

		global $wpdb;

		$meta_keys_to_migrate = array(
			'_purchase_price'      => 'purchase_price',
			'_supplier'            => 'supplier_id',
			'_supplier_sku'        => 'supplier_sku',
			'_atum_manage_stock'   => 'atum_controlled',
			'_out_of_stock_date'   => 'out_stock_date',
			'_out_stock_threshold' => 'out_stock_threshold',
			'_inheritable'         => 'inheritable',
		);

		$products = array();

		if ( Helpers::is_using_new_wc_tables() ) {
			$products = $wpdb->get_results( "SELECT `product_id` AS ID, `type` FROM {$wpdb->prefix}wc_products ORDER BY `product_id`" );
		}

		if ( empty( $products ) ) {
			$products = $wpdb->get_results(
				"SELECT `ID`, `post_type` AS type FROM {$wpdb->posts} WHERE `post_type` IN ('product', 'product_variation') ORDER BY `ID`"
			);
		}

		foreach ( $products as $product ) {

			$metas      = get_post_meta( $product->ID );
			$meta_value = NULL;

			$new_data = array(
				'product_id' => $product->ID,
			);

			foreach ( $meta_keys_to_migrate as $meta_key => $new_field_name ) {

				switch ( $meta_key ) {

					// Yes/No metas.
					case '_atum_manage_stock':
					case '_inheritable':
						if ( isset( $metas[ $meta_key ] ) ) {
							$meta_value = 'yes' === $metas[ $meta_key ][0] ? 1 : 0;
						}
						else {
							$meta_value = 0;
						}
						break;

					// Date metas.
					case '_out_of_stock_date':
						$meta_value = ( isset( $metas[ $meta_key ] ) && ! empty( $metas[ $meta_key ][0] ) ) ? $metas[ $meta_key ][0] : NULL;
						break;

					// Other metas.
					default:
						$meta_value = isset( $metas[ $meta_key ] ) ? $metas[ $meta_key ][0] : NULL;
						break;

				}

				$new_data[ $new_field_name ] = $meta_value;

			}

			// Insert a new row of data.
			$inserted_row = $wpdb->insert( $wpdb->prefix . 'atum_product_data', $new_data );

			// TODO: Move meta deletion to ATUM settings -> Tools
			// If the row was inserted, delete the old meta.
			// phpcs:ignore Squiz.Commenting.BlockComment.NoNewLine
			/*if ( $inserted_row ) {

				foreach ( array_keys( $meta_keys_to_migrate ) as $meta_key ) {
					delete_post_meta( $product->ID, $meta_key );
				}

			}*/

		}

	}

	/**
	 * Update PO status completed to received.
	 *
	 * @since 1.5.0
	 */
	private function update_po_status() {

		global $wpdb;

		$wpdb->update( $wpdb->posts, [ 'post_status' => 'atum_received' ], [
			'post_status' => 'atum_completed',
			'post_type'   => PurchaseOrders::get_post_type(),
		] );

		$sql = "
			UPDATE $wpdb->postmeta pm
			INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
			SET pm.meta_value = 'received'
			WHERE p.post_type = 'atum_purchase_order' AND
			pm.meta_key = '_status' AND pm.meta_value = 'completed'
		";

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Create the new columns for the ATUM product data table
	 *
	 * @since 1.5.8
	 */
	private function create_list_table_columns() {

		global $wpdb;

		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$columns         = array(
			'inbound_stock'    => 'DOUBLE',
			'stock_on_hold'    => 'DOUBLE',
			'sold_today'       => 'BIGINT(20)',
			'sales_last_days'  => 'BIGINT(20)',
			'reserved_stock'   => 'BIGINT(20)',
			'customer_returns' => 'BIGINT(20)',
			'warehouse_damage' => 'BIGINT(20)',
			'lost_in_post'     => 'BIGINT(20)',
			'other_logs'       => 'BIGINT(20)',
			'out_stock_days'   => 'INT(11)',
			'lost_sales'       => 'BIGINT(20)',
			'has_location'     => 'TINYINT(1)',
			'update_date'      => 'DATETIME',
		);

		foreach ( array_keys( $columns ) as $column_name ) {

			// Avoid adding the column if was already added.
			$column_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = %s
			', $db_name, $atum_data_table, $column_name );

			// Add the new column to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $column_exist ) ) {
				$wpdb->query( "ALTER TABLE $atum_data_table ADD `$column_name` {$columns[ $column_name ]} DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

		}

		// Add extra key indexes to ATUM tables to improve performance.
		$indexes = array(
			'inheritable',
		);

		foreach ( $indexes as $index ) {

			// Avoid adding the index if was already added.
			$index_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
				WHERE table_schema = %s AND TABLE_NAME = %s AND index_name = %s;
			', $db_name, $atum_data_table, $index );

			// Add the new index to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $index_exist ) ) {
				$wpdb->query( "ALTER TABLE $atum_data_table ADD INDEX `$index` (`$index`)" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

		}

	}

	/**
	 * Modify the stock count inventory fields from bigint to double
	 *
	 * @since 1.6.1.1
	 */
	private function alter_list_table_columns() {

		global $wpdb;

		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$columns         = array(
			'sold_today'       => 'DOUBLE',
			'sales_last_days'  => 'DOUBLE',
			'reserved_stock'   => 'DOUBLE',
			'customer_returns' => 'DOUBLE',
			'warehouse_damage' => 'DOUBLE',
			'lost_in_post'     => 'DOUBLE',
			'other_logs'       => 'DOUBLE',
			'lost_sales'       => 'DOUBLE',
		);

		foreach ( array_keys( $columns ) as $column_name ) {

			// Avoid adding the column if was already added.
			$column_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = %s
			', $db_name, $atum_data_table, $column_name );

			// Add the new column to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $column_exist ) ) {
				$wpdb->query( "ALTER TABLE $atum_data_table ADD `$column_name` {$columns[ $column_name ]} DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
			else {
				$wpdb->query( "ALTER TABLE $atum_data_table MODIFY `$column_name` {$columns[ $column_name ]} DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

		}

	}

	/**
	 * Set the default value for atum_controlled and disallow NULL
	 *
	 * @since 1.6.3.2
	 */
	private function alter_atum_controlled_column() {

		global $wpdb;

		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// Make sure that there are no NULL values currently.
		$wpdb->update(
			$atum_data_table,
			array(
				'atum_controlled' => 1, // All those that were incorrectly set to NULL should be enabled by default.
			),
			array(
				'atum_controlled' => NULL,
			),
			array(
				'%d',
			),
			array(
				NULL,
			)
		);

		$wpdb->query( "ALTER TABLE $atum_data_table MODIFY `atum_controlled` TINYINT(1) NOT NULL DEFAULT '1';" ); // phpcs:ignore WordPress.DB.PreparedSQL

	}

	/**
	 * Add atum_stock_status and lowstock columns to ATUM Product data
	 *
	 * @since 1.6.6
	 */
	private function add_atum_stock_fields() {

		global $wpdb;

		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$columns         = array(
			'atum_stock_status' => array(
				'type'    => 'VARCHAR(15)',
				'default' => "'instock'",
			),
			'restock_status'    => array(
				'type'    => 'TINYINT(1)',
				'default' => '0',
			),
		);

		foreach ( $columns as $column_name => $props ) {

			// Avoid adding the column if was already added.
			$column_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = %s
			', $db_name, $atum_data_table, $column_name );

			// Add the new column to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $column_exist ) ) {
				$wpdb->query( "ALTER TABLE $atum_data_table ADD `$column_name` {$props[ 'type' ]} NOT NULL DEFAULT {$props[ 'default' ]};" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
			else {
				$wpdb->query( "ALTER TABLE $atum_data_table MODIFY `$column_name` {$props[ 'type' ]} NOT NULL DEFAULT {$props[ 'default' ]};" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

		}

	}

	/**
	 * Update the ATUM Stock Status and Low Stock fields.
	 *
	 * @since 1.6.6
	 */
	public function fill_new_fields_values() {

		global $wpdb;

		$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		$product_statuses = array(
			'instock'     => [],
			'outofstock'  => [],
			'onbackorder' => [],
		);

		$ids = $wpdb->get_col( "SELECT product_id FROM $atum_product_data_table;" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $ids ) {

			foreach ( $ids as $id ) {

				$product = Helpers::get_atum_product( $id );

				if ( $product instanceof \WC_Product ) {

					$product_statuses[ $product->get_stock_status() ][] = $id;

					$restock_status = Helpers::is_product_restock_status( $product );

					$wpdb->update(
						$atum_product_data_table,
						array(
							'restock_status' => $restock_status,
						),
						array(
							'product_id' => $id,
						),
						array(
							'%d',
						),
						array(
							'%d',
						)
					);

				}
			}

		}

		foreach ( $product_statuses as $status => $products_ids ) {

			if ( $products_ids ) {

				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( $wpdb->prepare( "
					UPDATE $atum_product_data_table SET atum_stock_status = %s
					WHERE product_id IN(" . implode( ',', $products_ids ) . ')',
					$status
				) );
				// phpcs:enable
			}
		}

	}

	/**
	 * Change the supplier meta key names to be compatible with the new model
	 *
	 * @since 1.6.8
	 */
	private function change_supplier_meta_key_names() {

		$group_keys = array(
			'_supplier_details',
			'_default_settings',
			'_billing_information',
		);

		global $wpdb;

		foreach ( $group_keys as $group_key ) {

			$wpdb->query( $wpdb->prepare( "
				UPDATE $wpdb->postmeta SET meta_key = REPLACE(meta_key, %s, '')
				WHERE post_id IN (
					SELECT ID FROM $wpdb->posts WHERE post_type = %s
				)
			", $group_key, Suppliers::POST_TYPE ) );

		}

	}

	/**
	 * Change the POs date expected meta key names to be compatible with the new model
	 *
	 * @since 1.7.1
	 */
	private function change_date_expected_meta_key_names() {

		global $wpdb;

		$wpdb->query( "
			UPDATE $wpdb->postmeta SET meta_key = '_date_expected'
			WHERE meta_key = '_expected_at_location_date'
		" );

	}

	/**
	 * Update the calculated props for all the site variables
	 *
	 * @since 1.7.2
	 */
	public static function update_variable_calc_props() {

		$variation_products = get_posts( array(
			'posts_per_page' => - 1,
			'post_type'      => 'product_variation',
		) );

		$processed_variables = [];

		if ( ! empty( $variation_products ) ) {

			foreach ( $variation_products as $variation_product ) {

				if ( in_array( $variation_product->post_parent, $processed_variables ) ) {
					continue;
				}

				$processed_variables[] = $variation_product->post_parent;
				$variation_product     = Helpers::get_atum_product( $variation_product->ID );

				foreach (
					[
						'inbound_stock',
						'stock_on_hold',
						'sold_today',
						'sales_last_days',
						'reserved_stock',
						'customer_returns',
						'warehouse_damage',
						'lost_in_post',
						'other_logs',
						'lost_sales',
					] as $prop
				) {

					AtumCalculatedProps::maybe_update_variable_calc_prop( $variation_product, $prop, call_user_func( array( $variation_product, "get_$prop" ) ) );

				}

			}

		}

	}

	/**
	 * Alter the the ATUM product data table to add the is_bom column.
	 *
	 * @since 1.7.8
	 */
	private function create_is_bom_column() {

		global $wpdb;

		// Avoid adding the column if was already added.
		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'is_bom'
		", $db_name, $atum_data_table );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $atum_data_table ADD `is_bom` TINYINT(1) NULL DEFAULT 0;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Update atum_stock_status for managed variations product.
	 *
	 * @since 1.8.9.1
	 */
	private function update_atum_stock_status() {

		global $wpdb;

		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		$sql = "
			UPDATE $atum_data_table apd
			INNER JOIN $wpdb->posts p ON apd.product_id = p.ID
			INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
			INNER JOIN $wpdb->postmeta mpm ON p.ID = mpm.post_id
			SET apd.atum_stock_status = pm.meta_value
			WHERE p.post_type = 'product_variation' AND
			pm.meta_key = '_stock_status' AND mpm.meta_key = '_manage_stock' AND
	      	mpm.meta_value = 'yes' AND pm.meta_value <> apd.atum_stock_status
		";

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Create the sales_update_date in the ATUM Product table
	 *
	 * @since 1.9.6.1
	 */
	private function create_sales_update_date() {

		global $wpdb;

		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'sales_update_date'
		", $db_name, $atum_data_table );

		// Add the new column to the table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $atum_data_table ADD `sales_update_date` DATETIME DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Remove the ATUM duplicated Scheduled Actions from the Action Scheduler Queue.
	 *
	 * @since 1.9.7
	 */
	private function remove_duplicated_scheduled_actions() {

		global $wpdb;
		$wc = WC();

		// Ensure that the current WC version supports queues.
		if ( ! is_callable( array( $wc, 'queue' ) ) ) {
			return;
		}

		$wc_queue = $wc->queue();
		$actions  = $wc_queue->search( [
			'status'   => \ActionScheduler_Store::STATUS_PENDING,
			'per_page' => - 1,
		] );

		$processed = [];

		foreach ( $actions as $action_id => $action ) {

			/**
			 * Variable declaration.
			 *
			 * @var \ActionScheduler_Action $action
			 */
			$hook = $action->get_hook();

			// Only check hooks beginning with 'atum'.
			if ( 0 !== strpos( $hook, 'atum' ) && 'update_product_expiring_props' !== $hook ) {
				continue;
			}

			$next = $action->get_schedule()->get_date()->format( 'Y-m-d H:i:s' );

			if ( array_key_exists( $hook, $processed ) ) {

				if ( $next > $processed[ $hook ]['next'] ) {
					$duplicated_id = $action_id;
				}
				else {

					$duplicated_id      = $processed[ $hook ]['action_id'];
					$processed[ $hook ] = [
						'action_id' => $action_id,
						'next'      => $next,
					];
				}

				// The schedulded actions only can be deleted by id this way.
				$wpdb->delete( $wpdb->actionscheduler_actions, [ 'action_id' => $duplicated_id ], [ '%d' ] );

			}
			elseif ( 'update_product_expiring_props' !== $hook ) {

				// Delete all old tasks.
				$wpdb->delete( $wpdb->actionscheduler_actions, [ 'action_id' => $action_id ], [ '%d' ] );

			}
			else {
				$processed[ $hook ] = [
					'action_id' => $action_id,
					'next'      => $next,
				];
			}
		}

	}

	/**
	 * Change the user meta key names for the "entries per page" option in ATUM List Tables
	 *
	 * @since 1.9.7
	 */
	private function change_entries_per_page_meta_keys() {

		global $wpdb;

		// Stock Central.
		$wpdb->update(
			$wpdb->usermeta,
			[
				'meta_key' => StockCentral::UI_SLUG . '_entries_per_page',
			],
			[
				'meta_key' => ATUM_PREFIX . 'stock_central_products_per_page',
			]
		);

		// Inbound Stock.
		$wpdb->update(
			$wpdb->usermeta,
			[
				'meta_key' => InboundStock::UI_SLUG . '_entries_per_page',
			],
			[
				'meta_key' => ATUM_PREFIX . 'inbound_stock_products_per_page',
			]
		);

	}

	/**
	 * Alter the product data table to change the low_stock column name
	 *
	 * @since 1.9.15
	 */
	public function alter_low_stock_column() {

		global $wpdb;

		// Avoid changing the column if was already changed.
		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'low_stock'
		", $db_name, $atum_data_table );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $atum_data_table CHANGE `low_stock` `restock_status` TINYINT(1) NOT NULL DEFAULT 0;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Fill in the ATUM product data table and control all the products by default when installing ATUM for the first time
	 *
	 * @since 1.9.17
	 */
	public function maybe_control_all_products() {

		global $wpdb;

		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// Run this only if the product data table is empty.
		if ( ! $wpdb->get_var( "SELECT COUNT(*) FROM $atum_data_table" ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			AtumQueues::add_async_action( 'control_all_products_initial_setup', array( get_class(), 'control_all_products_deferred' ) );
		}

	}

	/**
	 * Deferred action to control all your products by default when installing ATUM for the first time
	 *
	 * @since 1.9.16
	 */
	public static function control_all_products_deferred() {
		Helpers::change_status_meta( Globals::ATUM_CONTROL_STOCK_KEY, 'yes', TRUE );
	}

	/**
	 * Add the barcode column to the product data table
	 *
	 * @since 1.9.18
	 */
	public function add_barcode_column() {

		global $wpdb;

		// Avoid changing the column if was already changed.
		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'barcode'
		", $db_name, $atum_data_table );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $atum_data_table ADD `barcode` VARCHAR(256) DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Regenerate WC product lookup tables to ensure our queries work correctly
	 *
	 * @since 1.9.19
	 */
	public function regenerate_lookup_tables() {

		if ( ! wc_update_product_lookup_tables_is_running() ) {
			wc_update_product_lookup_tables();
		}

	}

	/**
	 * Set the supplier's default checkboxes to the existing suppliers (if needed)
	 *
	 * @since 1.9.19.1
	 */
	public function set_supplier_default_checkboxes() {

		global $wpdb;

		$supplier_ids = $wpdb->get_col( "
			SELECT ID FROM $wpdb->posts 
            WHERE post_type = 'atum_supplier'
			AND post_status NOT IN ( 'auto-draft', 'trash' ) 
		" );

		foreach ( $supplier_ids as $supplier_id ) {
			$supplier = new Supplier( $supplier_id );
			update_post_meta( $supplier_id, '_use_default_description', ! $supplier->description ? 'yes' : 'no' );
			update_post_meta( $supplier_id, '_use_default_terms', ! $supplier->delivery_terms ? 'yes' : 'no' );
		}

	}

	/**
	 * Add committed to WC Orders column to ATUM Product data
	 *
	 * @since 1.9.20.3
	 */
	public function add_committed_stock_to_wc_orders_column() {

		global $wpdb;

		// Avoid adding the column if was already added.
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// Avoid adding the column if was already added.
		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'committed_to_wc'
		", DB_NAME, $atum_data_table );

		// Add the new column to the table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $atum_data_table ADD `committed_to_wc` DOUBLE DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Add the calculated backorders column to ATUM Product data
	 *
	 * @since 1.9.20.4
	 */
	public function add_calc_backorders_column() {

		global $wpdb;

		// Avoid adding the column if was already added.
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// Avoid adding the column if was already added.
		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'calc_backorders'
		", DB_NAME, $atum_data_table );

		// Add the new column to the table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $atum_data_table ADD `calc_backorders` DOUBLE DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Update the calculated backorders data when necessary
	 *
	 * @since 1.9.20.5
	 */
	public function update_calc_backorders() {

		global $wpdb;

		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "
			UPDATE $atum_data_table apd
			INNER JOIN $wpdb->postmeta pms ON (apd.product_id = pms.post_id AND pms.meta_key = '_stock')
			INNER JOIN $wpdb->postmeta pmb ON (apd.product_id = pmb.post_id AND pmb.meta_key = '_backorders')
			SET apd.calc_backorders = pms.meta_value
			WHERE pmb.meta_value != 'no' AND pms.meta_value <= 0 
		" );
		// phpcs:enable

	}

}
