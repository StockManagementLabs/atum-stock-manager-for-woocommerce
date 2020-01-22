<?php
/**
* Class AtumCommentsTest
*
* @package Atum_Stock_Manager_For_Woocommerce
*/

use Atum\Components\AtumOrders\AtumComments;
use TestHelpers\TestHelpers;

class AtumCommentsTest extends WP_UnitTestCase {

	public function test_methods() {
		$data = TestHelpers::count_public_methods( AtumComments::class );

		foreach( $data['methods'] as $method) {
			$this->assertTrue( method_exists( $this, 'test_'.$method ), "Method `test_$method` doesn't exist in class ".self::class );
		}
	}

	public function test_instance() {
		$this->assertInstanceOf( AtumComments::class, AtumComments::get_instance() );

		$this->assertEquals( 10, TestHelpers::has_action( 'comments_clauses', [ AtumComments::class, 'exclude_atum_order_notes' ] ) );
		$this->assertEquals( 10, TestHelpers::has_action( 'comment_feed_where', [ AtumComments::class, 'exclude_atum_order_notes_from_feed_where' ] ) );
		$this->assertEquals( 11, TestHelpers::has_action( 'wp_count_comments', [ AtumComments::class, 'wp_count_comments' ] ) );
	}

	public function test_exclude_atum_order_notes() {
		$ac = AtumComments::get_instance();
		$data = $ac->exclude_atum_order_notes( [ 'where' => '' ] );
		$this->assertIsArray( $data );
		$this->assertContains( 'comment_type NOT IN', $data['where'] );
	}

	public function test_exclude_atum_order_notes_from_feed_where() {
		$ac = AtumComments::get_instance();
		$data = $ac->exclude_atum_order_notes_from_feed_where( '' );
		$this->assertContains( 'comment_type NOT IN', $data );
	}

	public function test_wp_count_comments() {
		$ac = AtumComments::get_instance();
		$product = TestHelpers::create_product();
		$data = $ac->wp_count_comments( $product, $product->get_id() );
		$this->assertEquals( $product, $data );
	}

	public function test_delete_comments_count_cache() {
		$ac = AtumComments::get_instance();
		$ac->delete_comments_count_cache();
		$this->expectNotToPerformAssertions();
	}

}