<?php
/**
 * Class MainTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Inc\Globals;
use Atum\Inc\Main;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class PluginTest extends PHPUnit_Framework_TestCase { // WP_UnitTestCase {

	/**
	 * @param $class
	 * @param $path
	 * @dataProvider provideClassesList
	 */
	public function test_classes_checker( $class, $path ) {
		if( class_exists( 'HooksTest' ) ) {
			//Check if file exists
			$this->assertTrue( file_exists( $path ), 'File ' . $path . ' does not exist.' );

			$c = explode( '\\', $class );

			if ( 'Atum' === $c[0] ) {
				//Check if class exists
				if( 'Atum\Dashboard\Widgets\News_DISABLED' !== $class && strpos( $class, 'WC' ) != 0 && strpos( $class, '_Data_Store_' ) !== FALSE )
					$this->assertTrue( ( class_exists( $class ) || trait_exists( $class ) ), 'Class ' . $class . ' does not exist.' );

				//Check if test-class exists
				$top       = count( $c ) - 1;
				$testClass = $c[ $top ] . 'Test';
				//$testClass = __NAMESPACE__ . "\\" . $c[$top] . 'Test';
				$this->assertTrue( class_exists( $testClass ), 'Class ' . $testClass . ' does not exist.' );
			}
		} else {
			$this->expectNotToPerformAssertions();
		}
	}

	public function provideClassesList() {
		$class_list = TestHelpers::get_classes();
		$_classes = [];
		foreach ( $class_list as $class => $path )
			$_classes[] = [ $class, $path ];
		return $_classes;
	}

	/**
	 * @param $file
	 * @dataProvider provideFile
	 */
	public function test_scan_text_constant( $file ) {
		$data = TestHelpers::scan_file( $file );
		$this->assertIsArray( $data );
		$this->assertEquals( 0, $data['count'], $data['msg'] );
	}

	public function provideFile() {
		$_files = [];
		$files = TestHelpers::scan_dir_for_files();
		foreach ( $files as $file )
			$_files[] = [ $file ];
		return $_files;
	}

}
