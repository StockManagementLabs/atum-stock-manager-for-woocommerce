<?php
/**
 * Upgrade tasks class
 *
 * @package         Atum
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.2.4
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\InventoryLogs\Models\Log;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\StockCentral\StockCentral;
use Atum\StockCentral\Lists\ListTable;


class Upgrade {

	/**
	 * The current ATUM version
	 *
	 * @var string
	 */
	private $current_atum_version = '';

	/**
	 * Upgrade constructor
	 *
	 * @since 1.2.4
	 *
	 * @param string $db_version  The ATUM version saved in db as an option.
	 */
	public function __construct( $db_version ) {

		$this->current_atum_version = $db_version;
		
		// Update the db version to the current ATUM version before upgrade to prevent various executions.
		update_option( ATUM_PREFIX . 'version', ATUM_VERSION );

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

		// ** version 1.4.1 ** ATUM now uses its own way to manage the stock of the products.
		if ( version_compare( $db_version, '1.4.1', '<' ) ) {
			$this->set_individual_manage_stock();
			$this->add_inheritable_meta();
		}

		// ** version 1.4.1.2 ** Some inheritable products don't have the ATUM_CONTROL_STOCK_KEY meta.
		if ( version_compare( $db_version, '1.4.1.2', '<' ) ) {
			$this->add_inheritable_sock_meta();
		}

		// ** version 1.4.6 ** New hidden column: weight.
		if ( version_compare( $db_version, '1.4.6', '<' ) ) {
			$this->add_default_hidden_columns();
		}
		
		// ** version 1.4.18.2. Removed date_i18n function.Check if post meta values contains not latins characters.
		if ( version_compare( $db_version, '1.4.18.2', '<' ) ) {
			$this->check_post_meta_values();
		}

		// ** version 1.5.0 ** New tables to store ATUM data for products.
		if ( version_compare( $db_version, '1.5.0', '<' ) ) {
			$this->create_product_data_table();
			$this->update_po_status();
		}

		// ** version 1.6.0 ** New table for ATUM Logs component.
		/*if ( version_compare( $db_version, '1.6.0', '<' ) ) {
			$this->create_atum_log_table();
		}*/

		/**********************
		 * UPGRADE ACTIONS END
		 ********************!*/

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
		$items_table    = $wpdb->prefix . ATUM_PREFIX . 'order_items';
		$itemmeta_table = $wpdb->prefix . ATUM_PREFIX . 'order_itemmeta';

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$items_table';" ) ) { // WPCS: unprepared SQL ok.

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
	 * Set the ATUM's manage stock meta key to all the products
	 *
	 * @since 1.4.1
	 */
	private function set_individual_manage_stock() {

		global $wpdb;

		// Ensure that the meta keys were not added previously.
		$meta_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '" . Globals::ATUM_CONTROL_STOCK_KEY . "'" ); // WPCS: unprepared SQL ok.

		if ( $meta_count > 0 ) {
			return;
		}

		// Add the meta to all the products that had the WC's manage_stock enabled.
		$sql = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
				SELECT DISTINCT post_id, '" . Globals::ATUM_CONTROL_STOCK_KEY . "', meta_value FROM $wpdb->postmeta 
				WHERE meta_key = '_manage_stock' AND meta_value = 'yes'";

		$wpdb->query( $sql ); // WPCS: unprepared SQL ok.

	}

	/**
	 * Set the ATUM's inheritable meta key to all the inheritable products
	 *
	 * @since 1.4.1
	 */
	private function add_inheritable_meta() {

		global $wpdb;

		// Ensure that the meta keys were not added previously.
		$meta_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '" . Globals::IS_INHERITABLE_KEY . "'" ); // WPCS: unprepared SQL ok.

		if ( $meta_count > 0 ) {
			return;
		}

		foreach ( Globals::get_inheritable_product_types() as $inheritable_product_type ) {
			$term = get_term_by( 'slug', $inheritable_product_type, 'product_type' );

			if ( $term ) {

				// Add the meta to all the products that have the inheritable product type tax term set.
				$sql = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
				SELECT DISTINCT object_id, '" . Globals::IS_INHERITABLE_KEY . "', 'yes' FROM $wpdb->term_relationships 
				WHERE term_taxonomy_id = $term->term_taxonomy_id";

				$wpdb->query( $sql ); // WPCS: unprepared SQL ok.

			}

		}

	}

	/**
	 * Ensure that all inheritable products have set ATUM_CONTROL_STOCK_KEY
	 *
	 * @since 1.4.1.2
	 */
	private function add_inheritable_sock_meta() {

		global $wpdb;

		$inheritable_ids = $wpdb->get_col( "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = '" . Globals::IS_INHERITABLE_KEY . "'" ); // WPCS: unprepared SQL ok.

		if ( $inheritable_ids ) {
			foreach ( $inheritable_ids as $id ) {
				update_post_meta( $id, Globals::ATUM_CONTROL_STOCK_KEY, 'yes' );
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

			$user_ids = $wpdb->get_col( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '$meta_key_sc' AND meta_value NOT LIKE '%{$hidden_column}%' AND meta_value <> ''" ); // WPCS: unprepared SQL ok.

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
	 * Check if post meta vualues contains not latins characters.
	 *
	 * @since 1.4.18
	 */
	private function check_post_meta_values() {

		global $wpdb;

		$sql = "
			SELECT pm.post_id, pm.meta_value 
			FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE pm.meta_key = '" . Globals::OUT_OF_STOCK_DATE_KEY . "' 
			AND pm.meta_value <> ''AND p.post_type IN ('product', 'product_variation');
		";

		$post_metas = $wpdb->get_results( $sql ); // WPCS: unprepared SQL ok.
		foreach ( $post_metas as $post_meta ) {
			$non_latin_character = preg_match( '/[^\\p{Common}\\p{Latin}]/u', $post_meta->meta_value );

			if ( $non_latin_character ) {
				delete_post_meta( $post_meta->post_id, Globals::OUT_OF_STOCK_DATE_KEY );
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

		$product_meta_table = $wpdb->prefix . ATUM_PREFIX . 'product_data';

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$product_meta_table';" ) ) { // WPCS: unprepared SQL ok.

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
			  	`atum_controlled` TINYINT(1) NULL DEFAULT 0,
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

			$this->migrate_atum_products_data();

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
			$inserted_row = $wpdb->insert( $wpdb->prefix . ATUM_PREFIX . 'product_data', $new_data );

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
		
		$wpdb->update( $wpdb->posts, [ 'post_status' => ATUM_PREFIX . 'received' ], [
			'post_status' => ATUM_PREFIX . 'completed',
			'post_type'   => PurchaseOrders::get_post_type(),
		] );
		
		$sql = "
			UPDATE $wpdb->postmeta pm
			INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
			SET pm.meta_value = 'received'
			WHERE p.post_type='atum_purchase_order' AND
			pm.meta_key = '_status' AND pm.meta_value = 'completed'
		";
		
		$wpdb->query( $sql ); // WPCS: unprepared SQL ok.
		
	}
	
	/**
	 * Create the table for the ATUM Logs
	 *
	 * @since 1.4.15
	 */
	// phpcs:ignore Squiz.Commenting.BlockComment.NoNewLine
	/*private function create_atum_log_table() {

		global $wpdb;

		$log_table = $wpdb->prefix . AtumLogs::get_log_table();

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$log_table';" ) ) { // WPCS: unprepared SQL ok.

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$sql = "
			CREATE TABLE $log_table (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		  		ref VARCHAR(256) NOT NULL,			
			  	user_id BIGINT DEFAULT NULL,			  
				type VARCHAR(64) DEFAULT NULL,
				source VARCHAR(256) DEFAULT NULL,
				time BIGINT DEFAULT NULL,			  
				entry LONGTEXT,
				status INT(11) DEFAULT '0',
				data LONGTEXT,			
			  	PRIMARY KEY (id),
			  	UNIQUE KEY id (id)
			) $collate;
			";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

		}

	}*/

}
