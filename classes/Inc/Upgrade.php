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

use Atum\Components\AtumLogs\AtumLogs;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\InventoryLogs\Models\Log;
use Atum\InventoryLogs\InventoryLogs;
use Atum\StockCentral\StockCentral;
use Atum\StockCentral\Lists\ListTable;


class Upgrade {

	/**
	 * Upgrade constructor
	 *
	 * @since 1.2.4
	 *
	 * @param string $db_version  The ATUM version saved in db as an option.
	 */
	public function __construct( $db_version ) {

		// Delete transients if there after every version change.
		Helpers::delete_transients();

		/************************
		 * UPGRADE ACTIONS START
		 **********************!*/

		// ** version 1.2.4 ** The Inventory Logs was introduced.
		if ( version_compare( $db_version, '1.2.4', '<' ) ) {
			$this->create_inventory_log_tables();
			add_action( 'admin_init', array( $this, 'create_inventory_log_types' ) );
		}

		// ** version 1.2.9 ** Refactory to change the log table names to something more generic.
		if ( version_compare( $db_version, '1.2.9', '<' ) ) {
			$this->alter_order_item_tables();
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

		// ** version 1.4.15 ** New table for ATUM Logs component.
		/*if ( version_compare( $db_version, '1.4.15', '<' ) ) {
			$this->create_atum_log_table();
		}*/

		/**********************
		 * UPGRADE ACTIONS END
		 ********************!*/

		// Update the db version to the current ATUM version.
		update_option( ATUM_PREFIX . 'version', ATUM_VERSION );

		do_action( 'atum/after_upgrade', $db_version );

	}

	/**
	 * Create the tables for the Inventory Log items
	 *
	 * @since 1.2.4
	 */
	private function create_inventory_log_tables() {

		global $wpdb;

		// Create DB tables for the log items.
		$items_table    = $wpdb->prefix . ATUM_PREFIX . 'log_items';
		$itemmeta_table = $wpdb->prefix . ATUM_PREFIX . 'log_itemmeta';

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$items_table';" ) ) { // WPCS: unprepared SQL ok.

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$sql = "
			CREATE TABLE $items_table (
				log_item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		  		log_item_name TEXT NOT NULL,
			  	log_item_type varchar(200) NOT NULL DEFAULT '',
			  	log_id BIGINT UNSIGNED NOT NULL,
			  	PRIMARY KEY  (log_item_id),
			  	KEY log_id (log_id)
			) $collate;
			CREATE TABLE $itemmeta_table (
				meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  	log_item_id BIGINT UNSIGNED NOT NULL,
			  	meta_key varchar(255) default NULL,
			  	meta_value longtext NULL,
			  	PRIMARY KEY  (meta_id),
			  	KEY log_item_id (log_item_id),
			  	KEY meta_key (meta_key(191))
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

			if ( ! get_term_by( 'slug', $log_type_slug, $log_type_taxonomy ) ) {
				wp_insert_term( $log_type_name, $log_type_taxonomy, array( 'slug' => $log_type_slug ) );
			}

		}

	}

	/**
	 * Alter the log item table names introduced in 1.2.4 to something more generic
	 * to be used by other components across ATUM and its add-ons
	 *
	 * @since 1.2.9
	 */
	private function alter_order_item_tables() {

		global $wpdb;

		// The old table names.
		$old_items_table    = $wpdb->prefix . ATUM_PREFIX . 'log_items';
		$old_itemmeta_table = $wpdb->prefix . ATUM_PREFIX . 'log_itemmeta';

		// Check whether the old tables exist.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$old_items_table';" ) && $wpdb->get_var( "SHOW TABLES LIKE '$old_itemmeta_table';" ) ) { // WPCS: unprepared SQL ok.

			$items_table    = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
			$itemmeta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

			// Change the table names.
			$wpdb->query( "RENAME TABLE $old_items_table TO $items_table, $old_itemmeta_table TO $itemmeta_table;" ); // WPCS: unprepared SQL ok.

			// Change the column names.
			$wpdb->query( "ALTER TABLE $items_table CHANGE `log_item_id` `order_item_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;" ); // WPCS: unprepared SQL ok.
			$wpdb->query( "ALTER TABLE $items_table CHANGE `log_item_name` `order_item_name` TEXT NOT NULL;" ); // WPCS: unprepared SQL ok.
			$wpdb->query( "ALTER TABLE $items_table CHANGE `log_item_type` `order_item_type` varchar(200) NOT NULL DEFAULT '';" ); // WPCS: unprepared SQL ok.
			$wpdb->query( "ALTER TABLE $items_table CHANGE `log_id` `order_id` BIGINT UNSIGNED NOT NULL;" ); // WPCS: unprepared SQL ok.
			$wpdb->query( "ALTER TABLE $itemmeta_table CHANGE `log_item_id` `order_item_id` BIGINT UNSIGNED NOT NULL;" ); // WPCS: unprepared SQL ok.
			$wpdb->query( "ALTER TABLE $itemmeta_table DROP KEY `log_item_id`, ADD KEY order_item_id (order_item_id);" ); // WPCS: unprepared SQL ok.

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
		$prefix = $wpdb->prefix;

		$sql = 'SELECT postmeta.post_id, postmeta.meta_value 
				FROM ' . $prefix . 'postmeta AS postmeta, ' . $prefix . 'posts AS post
				WHERE postmeta.post_id = post.ID AND
				postmeta.meta_key = "_out_of_stock_date" AND
				post.post_type = "product";
				  ';

		$post_metas = $wpdb->get_results( $sql ); // WPCS: unprepared SQL ok.
		foreach ( $post_metas as $post_meta ) {
			$not_latin_character = preg_match( '/[^\\p{Common}\\p{Latin}]/u', $post_meta->meta_value );

			if ( $not_latin_character ) {
				delete_post_meta( $post_meta->post_id, '_out_of_stock_date' );
			}
		}
	}

	/**
	 * Create the table for the ATUM Logs
	 *
	 * @since 1.4.15
	 */
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
