<?php
/**
 * Class SettingsTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumCapabilities;
use Atum\Inc\Helpers;
use Atum\Settings\Settings;
use TestHelpers\TestHelpers;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Sample test case.
 */
class SettingsTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( Settings::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		wp_set_current_user( 1 );
		$this->assertInstanceOf( Settings::class, Settings::get_instance() );
		$this->assertEquals( 10, TestHelpers::has_action( 'admin_init', array( Settings::class, 'register_settings' ) ) );
		//$this->assertEquals( 11, TestHelpers::has_action( 'admin_enqueue_scripts', array( Settings::class, 'enqueue_scripts' ) ) );
		//$this->assertEquals( Settings::MENU_ORDER, TestHelpers::has_action( 'atum/admin/menu_items', array( Settings::class, 'add_menu' ) ) );
	}

	public function test_add_menu() {
		$obj = Settings::get_instance();
		$menu = $obj->add_menu( [ 'others' => 'foo' ] );

		$this->assertIsArray( $menu );
		$this->assertArrayHasKey( 'settings', $menu );
		$this->assertArrayHasKey( 'slug', $menu['settings'] );
		$this->assertEquals( Settings::UI_SLUG, $menu['settings']['slug'] );
	}

	public function test_display() {
		$obj = Settings::get_instance();
		ob_start();
		$obj->register_settings();
		$obj->display();
		$data = ob_get_clean();
		$this->assertContains( '<div class="atum-settings-wrapper">', $data );
	}

	public function test_get_settings() {
		$obj = Settings::get_instance();
		$obj->register_settings();
		$settings = Helpers::get_options();
		$data = $obj->get_settings( $settings, [] );
		$this->assertIsArray( $data );
	}

	public function test_enqueue_scripts() {
		$obj = Settings::get_instance();
		$hook = 'toplevel_page_' . Settings::UI_SLUG;
		$obj->enqueue_scripts( $hook );
		$this->assertTrue( wp_script_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_script_is( 'atum-settings', 'registered' ) );
		$this->assertIsBool( wp_script_is( 'es6-promise', 'registered' ) );
		$this->assertTrue( wp_script_is( 'color-picker-alpha', 'registered' ) );
		$this->assertTrue( wp_style_is( 'switchery', 'registered' ) );
		$this->assertTrue( wp_style_is( 'sweetalert2', 'registered' ) );
		$this->assertTrue( wp_style_is( 'atum-settings', 'registered' ) );
		$this->assertIsBool( wp_style_is( 'woocommerce_admin_styles', 'registered' ) );
		$this->assertTrue( wp_style_is( 'wp-color-picker', 'registered' ) );
	}

	public function test_register_settings() {
		$obj = Settings::get_instance();
		try {
			$obj->register_settings();
		} catch ( Exception $e ) {
			unset( $e );
		}
		$this->expectNotToPerformAssertions();
	}

	public function test_sanitize() {
		$obj = Settings::get_instance();
		$input = array( 'show_totals' => array(
			'group'   => 'general',
			'section' => 'general',
			'name'    => __( 'Show Totals Row', ATUM_TEXT_DOMAIN ),
			'desc'    => __( 'When enabled, ATUM will display new row at the bottom of Stock Central. You will be able to preview page column totals of essential stock counters.', ATUM_TEXT_DOMAIN ),
			'type'    => 'switcher',
			'default' => 'yes',
		) );
		$data = $obj->sanitize( $input );
		$this->assertIsArray( $data );
		$this->arrayHasKey( 'enable_ajax_filter', $data );
		$this->arrayHasKey( 'show_totals', $data );
		$this->arrayHasKey( 'out_stock_threshold', $data );
		$this->arrayHasKey( 'stock_quantity_step', $data );
		$this->arrayHasKey( 'ship_country', $data );
	}

	public function test_sanitize_option() {
		//Tested in previous method
		$this->expectNotToPerformAssertions();
	}

	public function test_display_text() {
		wp_set_current_user( 1 );
		$obj  = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'options' => [
				'placeholder' => 'foo placeholder'
			],
			'default' => 'foo',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->register_settings();
		$obj->display_text( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("input[type=text][name='atum_settings[show_totals]'][data-default='foo']#atum_show_totals")->count() );
	}

	public function test_display_textarea() {
		wp_set_current_user( 1 );
		$obj  = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'rows' => 3,
			'cols' => 60,
			'options' => [
				'placeholder' => 'foo placeholder'
			],
			'default' => 'foo',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->register_settings();
		$obj->display_textarea( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("textarea[name='atum_settings[show_totals]'][data-default='foo']#atum_show_totals")->count() );
	}

	public function test_display_number() {
		$obj  = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'options' => [
				'placeholder' => 'foo placeholder'
			],
			'default' => 'foo',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->register_settings();
		$obj->display_number( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("input[type=number][name='atum_settings[show_totals]'][data-default='foo']#atum_show_totals")->count() );
	}

	public function test_display_wc_country() {
		$obj = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'options' => [
				'placeholder' => 'foo placeholder'
			],
			'default' => 'foo',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->display_wc_country( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("select[name='atum_settings[show_totals]'][data-default='foo']#atum_show_totals")->count() );
		$this->assertEquals( 1, $html->filter("option[value='ES:V']")->count() );
	}

	public function test_display_switcher() {
		$obj = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'options' => [
				'placeholder' => 'foo placeholder'
			],
			'default' => 'foo',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->display_switcher( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("input[type=checkbox][name='atum_settings[show_totals]'][data-default='foo']#atum_show_totals")->count() );
	}

	public function test_display_button_group() {
		$obj = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'options' => [
				'placeholder' => 'foo placeholder',
				'values' => [
					'always' => 1,
					'never' => 2,
					'some times' => 3,
				],
			],
			'default' => 'foo',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->display_button_group( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("div.btn-group#atum_show_totals")->count() );
		$this->assertEquals( 3, $html->filter("label.btn-secondary input[type=radio][name='atum_settings[show_totals]']")->count() );
	}

	public function test_display_select() {
		$obj = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'options' => [
				'placeholder' => 'foo placeholder',
				'values' => [
					'always' => 1,
					'never' => 2,
					'some times' => 3,
				],
			],
			'default' => 'foo',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->display_select( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("select[name='atum_settings[show_totals]'][data-default='foo']#atum_show_totals")->count() );
		$this->assertEquals( 3, $html->filter("select option")->count() );
	}

	public function test_display_script_runner() {
		$obj = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'options' => [
				'placeholder' => 'foo placeholder',
				'script_action' => 'foo-action',
				'button_text' => 'Foo Button',
				'values' => [
					'always' => 1,
					'never' => 2,
					'some times' => 3,
				],
			],
			'default' => 'foo',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->display_script_runner( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("div.script-runner button.tool-runner")->count() );
	}

	public function test_display_color() {
		$obj = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'default' => 'foo',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->display_color( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("input.atum-color[name='atum_settings[show_totals]'][type=text]#atum_show_totals")->count() );
	}

	public function test_display_html() {
		$obj = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->display_html( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("div.atum-settings-html[id='atum_[show_totals]']")->count() );
		$this->assertEquals( 'yes', trim( $html->filter("div.atum-settings-html[id='atum_[show_totals]']")->text() ) );
	}

	public function test_display_theme_selector() {
		$obj = Settings::get_instance();
		$args = [
			'id' => 'theme',
			'options' => [
				'values' => [
					[ 'key' => 'branded_mode', 'name' => 'Branded Mode', 'thumb' => '', 'desc' => '' ],
					[ 'key' => 'highcontrast_mode', 'name' => 'High Contrast Mode', 'thumb' => '', 'desc' => '' ],
					[ 'key' => 'dark_mode', 'name' => 'Dark Mode', 'thumb' => '', 'desc' => '' ],
				]
			]
		];
		ob_start();
		$obj->display();
		ob_get_clean();

		ob_start();
		$obj->display_theme_selector( $args );
		$data = ob_get_clean();
		$html = new Crawler( $data );
		$this->assertEquals( 3, $html->filter("div.theme-selector-wrapper input[type=radio][name='atum_settings[theme]']")->count() );
	}

	public function test_get_description() {
		$obj = Settings::get_instance();
		$args = [
			'id' => 'show_totals',
			'desc' => 'foo'
		];
		$data = $obj->get_description( $args );
		$html = new Crawler( $data );
		$this->assertEquals( 1, $html->filter("div.atum-setting-info")->count() );
		$this->assertEquals( 'foo', trim( $html->filter("div.atum-setting-info")->text() ) );
	}

	public function test_get_dependency() {
		$obj = Settings::get_instance();
		$data = $obj->get_dependency( [ 'dependency' => 'WC' ] );
		$this->assertEquals( " data-dependency='\"WC\"'", $data );
	}

	public function test_get_groups() {
		$obj = Settings::get_instance();
		$data = $obj->get_groups();
		$expected = [ 'general', 'store_details', 'module_manager' ];
		$this->assertIsArray( $data );
		foreach( $expected as $e ) {
			$this->assertArrayHasKey( $e, $data );
			$this->assertArrayHasKey( 'label', $data[ $e ] );
			$this->assertArrayHasKey( 'icon', $data[ $e ] );
			$this->assertArrayHasKey( 'sections', $data[ $e ] );
		}
	}

	public function test_get_default_settings() {
		$obj = Settings::get_instance();
		$data = $obj->get_default_settings();
		$expected = [
			'enable_ajax_filter',
			'enhanced_suppliers_filter',
			'show_totals',
			'enable_admin_bar_menu',
			'show_variations_stock',
			'out_stock_threshold',
			'unmanaged_counters',
			'stock_quantity_decimals',
			'stock_quantity_step',
			'sales_last_ndays',
			'delete_data',
			'company_name',
			'tax_number',
			'address_1',
			'address_2',
			'city',
			'country',
			'zip',
			'same_ship_address',
			'ship_to',
			'ship_address_1',
			'ship_address_2',
			'ship_city',
			'ship_country',
			'ship_zip',
		];
		$this->assertIsArray( $data );
		foreach( $expected as $e ) {
			$this->assertArrayHasKey( $e, $data );
			if( 'city' !== $e )
				$this->assertArrayHasKey( 'group', $data[ $e ], "$e has not a group key" );
			$this->assertArrayHasKey( 'section', $data[ $e ] );
			$this->assertArrayHasKey( 'name', $data[ $e ] );
			$this->assertArrayHasKey( 'desc', $data[ $e ] );
			$this->assertArrayHasKey( 'type', $data[ $e ] );
			$this->assertArrayHasKey( 'default', $data[ $e ] );
		}
	}

	public function test_set_user_meta_options() {
		//Tested in next method
		$this->expectNotToPerformAssertions();
	}

	public function test_get_user_meta_options() {
		$obj = Settings::get_instance();
		$obj->set_user_meta_options( [ 'meta' => [ 'test' => 'foo' ] ] );
		$data = $obj->get_user_meta_options();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'meta', $data );
		$this->assertArrayHasKey( 'test', $data['meta'] );
		$this->assertEquals( 'foo', $data['meta']['test'] );
	}

	public function test_find_option_value() {
		$obj = Settings::get_instance();
		$obj->register_settings();
		$data = $obj->find_option_value( 'show_totals' );
		$this->assertEquals( 'yes', $data );
	}

}