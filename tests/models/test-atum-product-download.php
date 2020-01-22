<?php
/**
 * Class AtumProductDownloadTest
 *
 * @package Atum_Stock_Manager_For_Woocommerce
 */

use TestHelpers\TestHelpers;
use Atum\Models\Products\AtumProductDownload;

class AtumProductDownloadTest extends WP_UnitTestCase { //PHPUnit_Framework_TestCase {

	public function test_product_download() {
		//include dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/woocommerce/includes/class-wc-product-download.php';
		//$product = TestHelpers::create_product();
		//$obj = new AtumProductDownload( $product );
		//$this->assertInstanceOf( AtumProductDownload::class, $obj );
		$this->expectNotToPerformAssertions();
	}

}
