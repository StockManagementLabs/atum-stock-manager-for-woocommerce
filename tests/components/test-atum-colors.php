<?php
/**
 * Class AtumColorsTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumColors;

/**
 * Sample test case.
 */
class AtumColorsTest extends WP_UnitTestCase {

	public function test_get_instance() {
		$this->assertInstanceOf( AtumColors::class, AtumColors::get_instance() );
	}

	public function test_convert_hexadecimal_to_rgb() {
		$instance = AtumColors::get_instance();
		$names = $instance->add_settings_defaults( [] );
		foreach($names as $name => $val) {
			if( 'theme' === $name ) continue;
			$color = $instance->get_default_color( $name );
			$this->assertEquals( 7, strlen( $color ) );
			$this->assertEquals( 0, strpos( $color, '#' ) );
			$hex = $instance->convert_hexadecimal_to_rgb( $color );
			$this->assertGreaterThanOrEqual( 8, strlen( $hex ) );
		}
	}

	public function test_get_high_contrast_mode_colors() {
		$instance = AtumColors::get_instance();
		$colors = $instance->get_branded_mode_colors();
		$this->assertGreaterThan( 100, strlen($colors) );
	}

	public function test_get_dark_mode_colors() {
		$instance = AtumColors::get_instance();
		$colors = $instance->get_branded_mode_colors();
		$this->assertGreaterThan( 100, strlen($colors) );
	}

	public function test_get_branded_mode_colors() {
		$instance = AtumColors::get_instance();
		$colors = $instance->get_branded_mode_colors();
		$this->assertGreaterThan( 100, strlen($colors) );
	}

	public function test_add_settings_tab() {
		$instance = AtumColors::get_instance();
		$tabs = [ 'other' => 'foo' ];
		$tabs = $instance->add_settings_tab( $tabs );

		$this->assertIsArray( $tabs );
		$this->assertArrayHasKey( 'other', $tabs );
		$this->assertArrayHasKey( 'visual_settings', $tabs );
	}

	public function test_add_settings_default() {
		$instance = AtumColors::get_instance();
		$defaults = [ 'other' => 'foo' ];
		$defaults = $instance->add_settings_defaults( $defaults );

		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'other', $defaults );
		$this->assertArrayHasKey( 'bm_primary_color', $defaults );
	}

	public function test_get_user_theme() {
		$user_id = get_current_user_id();
		$theme = AtumColors::get_user_theme( $user_id );
		$this->assertIsString( $theme );
		$this->assertGreaterThanOrEqual( 0, strpos( $theme, '_mode' ) );
	}

	public function test_get_user_color() {
		$instance = AtumColors::get_instance();
		$user_id = get_current_user_id();
		$names = $instance->add_settings_defaults( [] );
		foreach($names as $color_name => $val) {
			if( 'theme' === $color_name ) continue;
			$color = AtumColors::get_user_color( $color_name, $user_id );
			if( false === $color )
				$this->assertFalse( $color );
			else {
				$this->assertIsString( $color );
				$this->assertEquals( 0, strpos( $color, '#' ) );
			}
		}
	}

	public function test_get_default_color() {
		$instance = AtumColors::get_instance();
		$names = $instance->add_settings_defaults( [] );
		foreach($names as $name => $val) {
			if( 'theme' === $name ) continue;
			$color = $instance->get_default_color( $name );
			$this->assertEquals( 7, strlen( $color ) );
			$this->assertEquals( 0, strpos( $color, '#' ) );
		}
	}
}
