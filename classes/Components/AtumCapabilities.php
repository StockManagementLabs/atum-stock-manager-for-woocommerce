<?php
/**
 * Add capabilities to WP user roles
 *
 * @package        Atum
 * @subpackage     Components
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          1.3.1
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;

class AtumCapabilities {

	/**
	 * List of custom ATUM capabilities
	 *
	 * @var array
	 */
	private static $capabilities = array(

		// Purchase price caps.
		'atum_edit_purchase_price',
		'atum_view_purchase_price',

		// Purchase Orders caps.
		'atum_edit_purchase_order',
		'atum_read_purchase_order',
		'atum_read_purchase_orders',
		'atum_delete_purchase_order',
		'atum_read_private_purchase_orders',
		'atum_publish_purchase_orders',
		'atum_edit_purchase_orders',
		'atum_edit_others_purchase_orders',
		'atum_create_purchase_orders',
		'atum_delete_purchase_orders',
		'atum_delete_other_purchase_orders',

		// Inventory Logs caps.
		'atum_edit_inventory_log',
		'atum_read_inventory_log',
		'atum_read_inventory_logs',
		'atum_delete_inventory_log',
		'atum_read_private_inventory_logs',
		'atum_publish_inventory_logs',
		'atum_edit_inventory_logs',
		'atum_edit_others_inventory_logs',
		'atum_create_inventory_logs',
		'atum_delete_inventory_logs',
		'atum_delete_other_inventory_logs',

		// Inbound Stock caps.
		'atum_read_inbound_stock',

		// Out Stock Threshold caps.
		'atum_edit_out_stock_threshold',
		'atum_edit_low_stock_threshold',

		// Suppliers caps.
		'atum_edit_supplier',
		'atum_read_supplier',
		'atum_read_suppliers',
		'atum_delete_supplier',
		'atum_edit_suppliers',
		'atum_edit_others_suppliers',
		'atum_publish_suppliers',
		'atum_read_private_suppliers',
		'atum_create_suppliers',
		'atum_delete_suppliers',
		'atum_delete_private_suppliers',
		'atum_delete_published_suppliers',
		'atum_delete_other_suppliers',
		'atum_edit_private_suppliers',
		'atum_edit_published_suppliers',

		// Settings caps.
		'atum_manage_settings',
		'atum_edit_visual_settings',

		// ATUM menus caps.
		'atum_view_admin_menu',
		'atum_view_admin_bar_menu',

		// ATUM Order notes caps.
		'atum_read_order_notes',
		'atum_create_order_notes',
		'atum_delete_order_notes',

		// ATUM Locations caps.
		'atum_manage_location_terms',
		'atum_edit_location_terms',
		'atum_delete_location_terms',
		'atum_assign_location_terms',

		// Barcode caps.
		'atum_edit_barcode',
		'atum_view_barcode',

		// Other caps.
		'atum_export_data',
		'atum_view_statistics',

		// WP capabilities (used in WP_Query when searching for more than one post type not included by default in WordPress).
		// Do not have the atum_ prefix.
		'edit_others_multiple_post_types',
		'read_private_multiple_post_types',
	);

	/**
	 * Register all the ATUM capabilities
	 *
	 * @since 1.9.27
	 *
	 * @param array $capabilities
	 */
	public static function register_atum_capabilities( $capabilities = [] ) {

		$capabilities = (array) ( empty( $capabilities ) ? apply_filters( 'atum/capabilities/caps', self::$capabilities ) : $capabilities );
		$admin_roles  = (array) apply_filters( 'atum/capabilities/admin_roles', [ get_role( 'administrator' ) ] );

		foreach ( $admin_roles as $admin_role ) {

			if ( $admin_role instanceof \WP_Role ) {
				foreach ( $capabilities as $cap ) {
					$admin_role->add_cap( $cap );
				}
			}

		}

	}

	/**
	 * Check whether the current user has ATUM capabilities
	 *
	 * @since 1.3.6
	 *
	 * @param string $capability The capability name without the ATUM prefix.
	 * @param int    $object_id  Optional. Check "current_user_can" function.
	 *
	 * @return bool
	 */
	public static function current_user_can( $capability, $object_id = NULL ) {
		// NOTE: allow the WP cron and WP CLI to bypass this to avoid problems when running internal background jobs when not logged in.
		// TODO: WE SHOULD FIND A BETTER WAY TO MAKE CRON JOBS AND WP CLI WORK WITHOUT BYPASSING THEM HERE.
		return ( defined( 'DOING_CRON' ) && DOING_CRON ) || Helpers::is_running_cli() || ( $object_id ? current_user_can( ATUM_PREFIX . $capability, $object_id ) : current_user_can( ATUM_PREFIX . $capability ) );
	}

}
