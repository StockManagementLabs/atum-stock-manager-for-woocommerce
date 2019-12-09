<?php
/**
 * Class AtumCacheTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use Atum\Components\AtumCache;
use TestHelpers\TestHelpers;

/**
 * Sample test case.
 */
class AtumCacheTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumCache::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_get_cache_key() {
		$name = 'foo';
		$key = AtumCache::get_cache_key( $name );
		$this->assertEquals( 'atum_foo', $key );
	}

	public function test_set_cache() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_delete_cache() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_get_cache() {
		$name = 'foo';
		$key = AtumCache::get_cache_key( $name );

		AtumCache::set_cache( $key, 'var' );

		$actual = AtumCache::get_cache( $key );
		$this->assertEquals( $actual, 'var' );

		AtumCache::delete_cache( $key );

		$actual = AtumCache::get_cache( $key );
		$this->assertFalse( $actual );
	}

	public function test_delete_group_cache() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_delete_all_atum_caches() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_group_cache() {
		$vars = [
			'names'  => [ 'foo1', 'foo2', 'foo3', 'foo4', 'foo5' ],
			'keys'   => [],
			'groups' => [ 'group1', 'group1', 'group2', 'group2', 'group2' ],
		];

		foreach($vars['names'] as $n)
			$vars['keys'][] = AtumCache::get_cache_key( $n );

		for($i=0; $i<count($vars['names']); $i++) {
			AtumCache::set_cache( $vars['keys'][$i], 'val' . ( $i+1 ), $vars['groups'][$i], 60 );
		}

		for($i=0; $i<count($vars['names']); $i++) {
			$this->assertEquals( 'val'. ( $i+1 ), AtumCache::get_cache( $vars['keys'][$i], $vars['groups'][$i] ) );
		}

		AtumCache::delete_group_cache( 'group1' );

		$this->assertFalse( AtumCache::get_cache( $vars['keys'][0], $vars['groups'][0] ) );
		$this->assertFalse( AtumCache::get_cache( $vars['keys'][1], $vars['groups'][1] ) );
		$this->assertEquals( AtumCache::get_cache( $vars['keys'][2], $vars['groups'][2] ), 'val3' );
		$this->assertEquals( AtumCache::get_cache( $vars['keys'][3], $vars['groups'][3] ), 'val4' );
		$this->assertEquals( AtumCache::get_cache( $vars['keys'][4], $vars['groups'][4] ), 'val5' );

		AtumCache::delete_all_atum_caches();

		$this->assertFalse( AtumCache::get_cache( $vars['keys'][2], $vars['groups'][2] ) );
		$this->assertFalse( AtumCache::get_cache( $vars['keys'][3], $vars['groups'][3] ) );
		$this->assertFalse( AtumCache::get_cache( $vars['keys'][4], $vars['groups'][4] ) );
	}

	public function test_get_transient_key() {
		$name = 'foo';
		$key  = AtumCache::get_transient_key( $name );
		$this->assertEquals( 'atum_foo', $key );
	}

	public function test_delete_transients() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_set_transient() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_get_transient() {
		$name = 'foo';
		$key = AtumCache::get_transient_key( $name );

		AtumCache::set_transient( $key, 'val', 1, true );
		$this->assertEquals( 'val', AtumCache::get_transient( $key, TRUE ) );

		sleep(2);
		AtumCache::delete_transients( $key );
		AtumCache::delete_transients();
		$this->assertFalse( AtumCache::get_transient( $key, TRUE ) );
	}

	public function test_is_cache_disabled() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_disable_cache() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_set_disable_cache() {
		//Tested in next method
		$this->assertTrue( TRUE );
	}

	public function test_enable_cache() {
		AtumCache::disable_cache();
		$this->assertTrue( AtumCache::is_cache_disabled() );

		AtumCache::enable_cache();
		$this->assertFalse( AtumCache::is_cache_disabled() );

		AtumCache::set_disable_cache( TRUE );
		$this->assertTrue( AtumCache::is_cache_disabled() );
	}
}
