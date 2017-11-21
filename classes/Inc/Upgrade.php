<?php
/**
 * @package         Atum
 * @subpackage      Inc
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.4
 *
 * Upgrade tasks class
 */

namespace Atum\Inc;

use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\InventoryLogs\Models\Log;
use Atum\InventoryLogs\InventoryLogs;


defined( 'ABSPATH' ) or die;


class Upgrade {

	/**
	 * Upgrade constructor
	 *
	 * @since 1.2.4
	 *
	 * @param string $db_version  The ATUM version saved in db as an option
	 */
	public function __construct($db_version) {

		// Delete transients if there after every version change
		Helpers::delete_transients();

		/************************
		 * UPGRADE ACTIONS START
		 ************************/

		// ** version 1.2.4 ** The Inventory Logs was introduced
		if ( version_compare($db_version, '1.2.4', '<') ) {
			$this->create_inventory_log_tables();
			add_action( 'admin_init', array( $this, 'create_inventory_log_types' ) );
		}

		// ** version 1.2.9 ** Refactory to change the log table names to something more generic
		if ( version_compare($db_version, '1.2.9', '<') ) {
			$this->alter_order_item_tables();
		}

		/**********************
		 * UPGRADE ACTIONS END
		 **********************/

		// Update the db version to the current ATUM version
		update_option( ATUM_PREFIX . 'version', ATUM_VERSION );

		do_action('atum/after_upgrade');

	}

	/**
	 * Create the tables for the Inventory Log items
	 *
	 * @since 1.2.4
	 */
	private function create_inventory_log_tables() {

		global $wpdb;

		// Create DB tables for the log items
		$items_table    = $wpdb->prefix . ATUM_PREFIX . 'log_items';
		$itemmeta_table = $wpdb->prefix . ATUM_PREFIX . 'log_itemmeta';

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$items_table';" ) ) {

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
			  	KEY meta_key (meta_key(32))
			) $collate;
			";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

		}

	}

	/**
	 * Create the default types for the Inventory Logs
	 *
	 * @since 1.2.4
	 */
	public function create_inventory_log_types() {

		// Create terms for the log types
		$log_type_taxonomy = InventoryLogs::get_type_taxonomy();
		foreach (Log::get_types() as $log_type_slug => $log_type_name) {

			if ( ! get_term_by( 'slug', $log_type_slug, $log_type_taxonomy ) ) {
				wp_insert_term( $log_type_name, $log_type_taxonomy, array('slug' => $log_type_slug) );
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

		// The old table names
		$old_items_table    = $wpdb->prefix . ATUM_PREFIX . 'log_items';
		$old_itemmeta_table = $wpdb->prefix . ATUM_PREFIX . 'log_itemmeta';

		// Check whether the old tables exist
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$old_items_table';" ) && $wpdb->get_var( "SHOW TABLES LIKE '$old_itemmeta_table';" ) ) {

			$items_table    = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
			$itemmeta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

			// Change the table names
			$wpdb->query( "RENAME TABLE $old_items_table TO $items_table, $old_itemmeta_table TO $itemmeta_table;" );

			// Change the column names
			$wpdb->query( "ALTER TABLE $items_table CHANGE `log_item_id` `order_item_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;" );
			$wpdb->query( "ALTER TABLE $items_table CHANGE `log_item_name` `order_item_name` TEXT NOT NULL;" );
			$wpdb->query( "ALTER TABLE $items_table CHANGE `log_item_type` `order_item_type` varchar(200) NOT NULL DEFAULT '';" );
			$wpdb->query( "ALTER TABLE $items_table CHANGE `log_id` `order_id` BIGINT UNSIGNED NOT NULL;" );
			$wpdb->query( "ALTER TABLE $itemmeta_table CHANGE `log_item_id` `order_item_id` BIGINT UNSIGNED NOT NULL;" );
			$wpdb->query( "ALTER TABLE $itemmeta_table DROP KEY `log_item_id`, ADD KEY order_item_id (order_item_id);" );

		}

	}

}