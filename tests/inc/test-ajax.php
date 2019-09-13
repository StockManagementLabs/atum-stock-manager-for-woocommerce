<?php
/**
 * Class AjaxTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Ajax;


/**
 * Sample test case.
 */
class AjaxTest extends PHPUnit_Framework_TestCase { //WP_UnitTestCase {

	public function test_get_instance() {
		/*
		$this->assertFalse( has_action( 'wp_ajax_atum_dashboard_save_layout' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_dashboard_restore_layout' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_dashboard_add_widget' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_statistics_widget_chart' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_videos_widget_sorting' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_current_stock_values' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_fetch_stock_central_list' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_fetch_inbound_stock_list' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_rated' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_update_data' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_apply_bulk_action' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_control_all_products' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_validate_license' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_activate_license' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_deactivate_license' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_install_addon' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_dismiss_notice' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_json_search_products' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_json_search_orders' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_json_search_suppliers' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_add_note' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_delete_note' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_load_items' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_add_item' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_add_fee' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_add_shipping' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_add_tax' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_remove_item' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_remove_tax' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_calc_line_taxes' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_save_items' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_increase_items_stock' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_decrease_items_stock' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_change_purchase_price' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_mark_status' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_order_import_items' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_set_variations_control_status' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_get_locations_tree' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_set_locations_tree' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_tool_manage_stock' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_tool_control_stock' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_tool_clear_out_stock_threshold' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_change_table_style_setting' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_get_marketing_popup_info' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_hide_marketing_popup' ) );
		$this->assertFalse( has_action( 'wp_ajax_atum_get_scheme_color' ) );
		*/
		$ajax = Ajax::get_instance();
		$this->assertInstanceOf( Ajax::class, $ajax );

		$this->assertEquals( 10, has_action( 'wp_ajax_atum_dashboard_save_layout', array( 'Ajax', 'save_dashboard_layout' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_dashboard_restore_layout', array( 'Ajax', 'restore_dashboard_layout' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_dashboard_add_widget', array( 'Ajax', 'add_new_widget' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_statistics_widget_chart', array( 'Ajax', 'statistics_widget_chart' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_videos_widget_sorting', array( 'Ajax', 'videos_widget_sorting' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_current_stock_values', array( 'Ajax', 'current_stock_values' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_fetch_stock_central_list', array( 'Ajax', 'fetch_stock_central_list' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_fetch_inbound_stock_list', array( 'Ajax', 'fetch_inbound_stock_list' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_rated', array( 'Ajax', 'rated' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_update_data', array( 'Ajax', 'update_list_data' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_apply_bulk_action', array( 'Ajax', 'apply_bulk_action' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_control_all_products', array( 'Ajax', 'control_all_products' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_validate_license', array( 'Ajax', 'validate_license' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_activate_license', array( 'Ajax', 'activate_license' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_deactivate_license', array( 'Ajax', 'deactivate_license' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_install_addon', array( 'Ajax', 'install_addon' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_dismiss_notice', array( 'Ajax', 'dismiss_notice' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_json_search_products', array( 'Ajax', 'search_products' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_json_search_orders', array( 'Ajax', 'search_wc_orders' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_json_search_suppliers', array( 'Ajax', 'search_suppliers' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_note', array( 'Ajax', 'add_atum_order_note' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_delete_note', array( 'Ajax', 'delete_atum_order_note' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_load_items', array( 'Ajax', 'load_atum_order_items' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_item', array( 'Ajax', 'add_atum_order_item' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_fee', array( 'Ajax', 'add_atum_order_fee' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_shipping', array( 'Ajax', 'add_atum_order_shipping' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_add_tax', array( 'Ajax', 'add_atum_order_tax' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_remove_item', array( 'Ajax', 'remove_atum_order_item' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_remove_tax', array( 'Ajax', 'remove_atum_order_tax' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_calc_line_taxes', array( 'Ajax', 'calc_atum_order_line_taxes' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_save_items', array( 'Ajax', 'save_atum_order_items' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_increase_items_stock', array( 'Ajax', 'increase_atum_order_items_stock' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_decrease_items_stock', array( 'Ajax', 'decrease_atum_order_items_stock' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_change_purchase_price', array( 'Ajax', 'change_atum_order_item_purchase_price' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_mark_status', array( 'Ajax', 'mark_atum_order_status' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_order_import_items', array( 'Ajax', 'import_wc_order_items' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_set_variations_control_status', array( 'Ajax', 'set_variations_control_status' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_get_locations_tree', array( 'Ajax', 'get_locations_tree' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_set_locations_tree', array( 'Ajax', 'set_locations_tree' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_tool_manage_stock', array( 'Ajax', 'change_manage_stock' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_tool_control_stock', array( 'Ajax', 'change_control_stock' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_tool_clear_out_stock_threshold', array( 'Ajax', 'clear_out_stock_threshold' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_change_table_style_setting', array( 'Ajax', 'change_table_style_user_meta' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_get_marketing_popup_info', array( 'Ajax', 'get_marketing_popup_info' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_hide_marketing_popup', array( 'Ajax', 'marketing_popup_state' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_atum_get_scheme_color', array( 'Ajax', 'get_scheme_color' ) ) );
	}

}
